<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttemptLog;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\StudentProgress;
use App\Models\StudentResponse;
use App\Models\User;
use App\Services\GamificationService;
use App\Support\AnswerNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProgressController extends Controller
{
    public function levels(): JsonResponse
    {
        $user = $this->activeUser();

        $lessonModels = Lesson::query()
            ->with([
                'listeningLessons.questionnaire.questions',
                'questionnaires.questions',
            ])
            ->orderBy('lesson_cefr_level')
            ->orderBy('lesson_sub_level')
            ->get(['lesson_id', 'lesson_cefr_level', 'lesson_sub_level', 'lesson_prompt_payload'])
            ->values();
        $lessons = $lessonModels
            ->groupBy('lesson_cefr_level')
            ->map(fn ($levelLessons) => $levelLessons->map(function (Lesson $lesson): array {
                $topic = $lesson->lesson_prompt_payload['topic'] ?? null;

                return [
                    'lesson_id' => $lesson->lesson_id,
                    'lesson_cefr_level' => $lesson->lesson_cefr_level,
                    'lesson_sub_level' => $lesson->lesson_sub_level,
                    'topic' => is_string($topic) ? $topic : null,
                ];
            })->values())
            ->all();

        $masteredByLesson = $user->progress()->get()->groupBy('lesson_id');
        $completedLessonIds = StudentProgress::completedLessonIds(
            $lessonModels,
            $masteredByLesson->flatten(1),
        )->all();

        return response()->json([
            'levels' => $lessons,
            'unlocked_levels' => StudentProgress::unlockedCefrLevels($user),
            'completed_lesson_ids' => $completedLessonIds,
            'total_xp' => (int) ($user->xp ?? 0),
        ]);
    }

    public function completeSublevel(Lesson $lesson): JsonResponse
    {
        $user = $this->activeUser();
        $this->assertLessonUnlocked($user, $lesson);

        $currentProgress = $user->progress()->where('lesson_id', $lesson->lesson_id)->get();

        if (StudentProgress::lessonIsComplete($lesson, $currentProgress)) {
            return response()->json([
                'message' => 'La leccion ya estaba completada.',
                'completed' => true,
                'total_xp' => (int) ($user->xp ?? 0),
            ]);
        }

        $requiredSkills = StudentProgress::requiredSkillsForLesson($lesson);

        if ($requiredSkills === []) {
            throw ValidationException::withMessages([
                'lesson' => ['La leccion no tiene preguntas calificables que acrediten su finalizacion.'],
            ]);
        }

        $xpBefore = (int) ($user->xp ?? 0);
        DB::transaction(function () use ($user, $lesson, $requiredSkills): void {
            $this->syncEligibleSkills($user, $lesson, $requiredSkills);
        });
        $currentProgress = $user->progress()->where('lesson_id', $lesson->lesson_id)->get();

        if (! StudentProgress::lessonIsComplete($lesson, $currentProgress)) {
            throw ValidationException::withMessages([
                'lesson' => ['Cada actividad evaluable requiere un intento completo y aprobado antes de completar la leccion.'],
            ]);
        }

        app(GamificationService::class)->recordActivity($user);

        $topic = $lesson->lesson_prompt_payload['topic'] ?? 'Lección';
        $freshUser = $user->fresh();

        return response()->json([
            'message' => "Lección '{$topic}' completada.",
            'completed' => true,
            'evidence_attempt_id' => AttemptLog::query()
                ->where('user_id', $user->user_id)
                ->where('lesson_id', $lesson->lesson_id)
                ->where('passed', true)
                ->latest('attempted_at')
                ->value('attempt_id'),
            'xp_awarded' => max(0, (int) $freshUser->xp - $xpBefore),
            'total_xp' => (int) ($freshUser->xp ?? 0),
        ]);
    }

    public function submitAttempt(Request $request, Lesson $lesson): JsonResponse
    {
        $validated = $request->validate([
            'question_id' => ['required', 'uuid', 'exists:questions,question_id'],
            'answer' => ['present', 'nullable', 'string', 'max:5000'],
            'correct' => ['prohibited'],
        ]);

        $user = $this->activeUser();
        $this->assertLessonUnlocked($user, $lesson);
        $question = $this->questionForLesson($validated['question_id'], $lesson);
        $isCorrect = $this->gradeQuestion($question, $validated['answer'], 'answer');
        $metadata = StudentProgress::attemptMetadataForQuestion($question);
        $xpBefore = (int) ($user->xp ?? 0);

        [$attempt, $response] = DB::transaction(function () use ($user, $lesson, $question, $validated, $isCorrect, $metadata): array {
            $attempt = AttemptLog::create([
                'user_id' => $user->user_id,
                'lesson_id' => $lesson->lesson_id,
                ...$metadata,
                'attempt_score' => $isCorrect ? 100 : 0,
                'passed' => $isCorrect,
            ]);

            $response = StudentResponse::create([
                'attempt_id' => $attempt->attempt_id,
                'question_id' => $question->question_id,
                'student_answer_text' => $validated['answer'] ?? '',
                'is_correct' => $isCorrect,
                'ai_question_feedback' => null,
            ]);

            $this->syncEligibleSkills($user, $lesson, [$metadata['attempt_skill_type']]);
            app(GamificationService::class)->recordActivity($user);

            return [$attempt, $response];
        });

        $freshUser = $user->fresh();

        return response()->json([
            'attempt' => [
                'attempt_id' => $attempt->attempt_id,
                'lesson_id' => $attempt->lesson_id,
                'score' => (float) $attempt->attempt_score,
                'passed' => $attempt->passed,
            ],
            'result' => [
                'response_id' => $response->response_id,
                'question_id' => $question->question_id,
                'is_correct' => $isCorrect,
            ],
            'message' => $isCorrect ? '¡Correcto!' : 'Respuesta incorrecta.',
            'xp_awarded' => max(0, (int) $freshUser->xp - $xpBefore),
            'total_xp' => (int) ($freshUser->xp ?? 0),
        ]);
    }

    public function submitBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'attempts' => ['required', 'array', 'min:1', 'max:50'],
            'attempts.*' => ['required', 'array:lesson_id,question_id,answer'],
            'attempts.*.lesson_id' => ['required', 'uuid', 'exists:lessons,lesson_id'],
            'attempts.*.question_id' => ['required', 'uuid', 'distinct:strict', 'exists:questions,question_id'],
            'attempts.*.answer' => ['present', 'nullable', 'string', 'max:5000'],
            'attempts.*.correct' => ['prohibited'],
        ]);

        $user = $this->activeUser();
        $lessonIds = collect($validated['attempts'])->pluck('lesson_id')->unique();
        $submittedLessons = Lesson::query()->whereIn('lesson_id', $lessonIds)->get()->keyBy('lesson_id');

        foreach ($lessonIds as $lessonId) {
            $this->assertLessonUnlocked($user, $submittedLessons->get($lessonId));
        }

        $questionIds = collect($validated['attempts'])->pluck('question_id');
        $questions = Question::query()
            ->with(['options', 'questionnaire.listeningLesson'])
            ->whereIn('question_id', $questionIds)
            ->get()
            ->keyBy('question_id');
        $grouped = [];

        foreach ($validated['attempts'] as $index => $data) {
            $question = $questions->get($data['question_id']);

            if (! $question || ! $this->questionBelongsToLesson(
                $question,
                $submittedLessons->get($data['lesson_id']),
            )) {
                throw ValidationException::withMessages([
                    "attempts.{$index}.question_id" => ['La pregunta no pertenece a la leccion indicada.'],
                ]);
            }

            $metadata = StudentProgress::attemptMetadataForQuestion($question);
            $groupKey = implode('|', [
                $data['lesson_id'],
                $metadata['attempt_skill_type'],
                $metadata['listening_lesson_id'] ?? '',
                $metadata['questionnaire_id'] ?? '',
            ]);
            $grouped[$groupKey]['lesson_id'] = $data['lesson_id'];
            $grouped[$groupKey]['metadata'] = $metadata;
            $grouped[$groupKey]['items'][] = [
                'question' => $question,
                'answer' => $data['answer'],
                'is_correct' => $this->gradeQuestion(
                    $question,
                    $data['answer'],
                    "attempts.{$index}.answer",
                ),
            ];
        }

        $xpBefore = (int) ($user->xp ?? 0);
        [$attempts, $results] = DB::transaction(function () use ($grouped, $submittedLessons, $user): array {
            $attempts = [];
            $results = [];
            $affectedSkills = [];

            foreach ($grouped as $group) {
                $lessonId = $group['lesson_id'];
                $items = $group['items'];
                $metadata = $group['metadata'];
                $correctCount = collect($items)->where('is_correct', true)->count();
                $score = round(($correctCount / count($items)) * 100, 2);
                $attempt = AttemptLog::create([
                    'user_id' => $user->user_id,
                    'lesson_id' => $lessonId,
                    ...$metadata,
                    'attempt_score' => $score,
                    'passed' => $score >= StudentProgress::PASSING_SCORE,
                ]);

                foreach ($items as $item) {
                    $response = StudentResponse::create([
                        'attempt_id' => $attempt->attempt_id,
                        'question_id' => $item['question']->question_id,
                        'student_answer_text' => $item['answer'] ?? '',
                        'is_correct' => $item['is_correct'],
                        'ai_question_feedback' => null,
                    ]);

                    $results[] = [
                        'response_id' => $response->response_id,
                        'question_id' => $item['question']->question_id,
                        'is_correct' => $item['is_correct'],
                    ];
                }

                $attempts[] = [
                    'attempt_id' => $attempt->attempt_id,
                    'lesson_id' => $lessonId,
                    'score' => (float) $attempt->attempt_score,
                    'passed' => $attempt->passed,
                ];
                $affectedSkills[$lessonId][$metadata['attempt_skill_type']] = true;
            }

            foreach ($affectedSkills as $lessonId => $skills) {
                $this->syncEligibleSkills(
                    $user,
                    $submittedLessons->get($lessonId),
                    array_keys($skills),
                );
            }

            app(GamificationService::class)->recordActivity($user);

            return [$attempts, $results];
        });

        $freshUser = $user->fresh();

        return response()->json([
            'attempts' => $attempts,
            'results' => $results,
            'count' => count($validated['attempts']),
            'xp_awarded' => max(0, (int) $freshUser->xp - $xpBefore),
            'total_xp' => (int) ($freshUser->xp ?? 0),
        ]);
    }

    public function stats(): JsonResponse
    {
        $user = $this->activeUser();

        $totalAttempts = $user->attemptLogs()->count();
        $correctAttempts = $user->attemptLogs()->where('passed', true)->count();
        $accuracy = $totalAttempts > 0 ? round(($correctAttempts / $totalAttempts) * 100, 1) : 0;

        $lessons = Lesson::query()
            ->with([
                'listeningLessons.questionnaire.questions',
                'questionnaires.questions',
            ])
            ->get();
        $completedCount = StudentProgress::completedLessonIds(
            $lessons,
            $user->progress()->get(),
        )->count();
        $gamification = app(GamificationService::class)->snapshot($user);

        return response()->json([
            'total_xp' => (int) ($user->xp ?? 0),
            'completed_lessons' => $completedCount,
            'total_attempts' => $totalAttempts,
            'correct_attempts' => $correctAttempts,
            'accuracy' => $accuracy,
            'current_streak' => $gamification['current_streak'],
            'longest_streak' => $gamification['longest_streak'],
            'weekly_activities' => $gamification['weekly_activities'],
        ]);
    }

    private function activeUser(): User
    {
        $user = auth('api')->user();

        abort_unless($user instanceof User && $user->user_status === 'active', 403, 'Cuenta no disponible.');

        return $user;
    }

    private function assertLessonUnlocked(User $user, Lesson $lesson): void
    {
        abort_unless(
            StudentProgress::latestPlacementFor($user)
                && StudentProgress::levelIsUnlocked($user, $lesson->lesson_cefr_level),
            403,
            'Este nivel todavía está bloqueado.',
        );
    }

    private function questionForLesson(string $questionId, Lesson $lesson): Question
    {
        $question = Question::query()
            ->with(['options', 'questionnaire.listeningLesson'])
            ->where('question_id', $questionId)
            ->whereHas('questionnaire', function ($query) use ($lesson): void {
                $query->where('lesson_id', $lesson->lesson_id)
                    ->orWhereHas('listeningLesson', function ($listeningQuery) use ($lesson): void {
                        $listeningQuery->where('lesson_id', $lesson->lesson_id)
                            ->orWhere(function ($fallbackQuery) use ($lesson): void {
                                $fallbackQuery->whereNull('lesson_id')
                                    ->where('cefr_level', $lesson->lesson_cefr_level)
                                    ->where('sub_level', $lesson->lesson_sub_level);
                            });
                    });
            })
            ->first();

        if (! $question) {
            throw ValidationException::withMessages([
                'question_id' => ['La pregunta no pertenece a esta leccion.'],
            ]);
        }

        return $question;
    }

    private function gradeQuestion(Question $question, ?string $answer, string $errorKey): bool
    {
        if ($this->skillForQuestion($question) === 'speaking') {
            throw ValidationException::withMessages([
                $errorKey => ['Las preguntas orales deben evaluarse mediante el endpoint de examen.'],
            ]);
        }

        if ($question->question_type === 'multiple_choice') {
            if ($answer === null) {
                return false;
            }

            $selectedOption = $question->options->firstWhere('option_id', $answer);

            if (! $selectedOption) {
                throw ValidationException::withMessages([
                    $errorKey => ['La opcion seleccionada no pertenece a la pregunta.'],
                ]);
            }

            return $selectedOption->is_correct;
        }

        if ($question->correct_answer === null) {
            throw ValidationException::withMessages([
                $errorKey => ['La pregunta no admite calificacion automatica.'],
            ]);
        }

        return AnswerNormalizer::normalize((string) $answer)
            === AnswerNormalizer::normalize($question->correct_answer);
    }

    private function questionBelongsToLesson(Question $question, Lesson $lesson): bool
    {
        $content = $question->questionnaire?->listeningLesson;

        return $question->questionnaire?->lesson_id === $lesson->lesson_id
            || $content?->lesson_id === $lesson->lesson_id
            || (
                $content?->lesson_id === null
                && $content?->cefr_level === $lesson->lesson_cefr_level
                && (int) $content?->sub_level === (int) $lesson->lesson_sub_level
            );
    }

    private function skillForQuestion(Question $question): string
    {
        if ($question->question_type === 'speaking' || $question->question_skill_type === 'speaking') {
            return 'speaking';
        }

        if ($question->question_type === 'listening' || $question->question_skill_type === 'listening') {
            return 'listening';
        }

        return 'reading';
    }

    /**
     * @param  iterable<int, string>  $skills
     */
    private function syncEligibleSkills(User $user, Lesson $lesson, iterable $skills): void
    {
        $placementTestId = StudentProgress::latestPlacementFor($user)?->placement_test_id;

        foreach (collect($skills)->unique() as $skill) {
            if (! in_array($skill, StudentProgress::LEARNING_SKILLS, true)) {
                continue;
            }

            $progress = StudentProgress::masterSkillWhenEligible(
                $user,
                $lesson,
                $skill,
                $placementTestId,
            );

            if (! $progress?->wasRecentlyCreated) {
                continue;
            }

            $xp = match ($skill) {
                'listening' => GamificationService::XP_LISTENING_PASS,
                'speaking' => GamificationService::XP_SPEAKING_PASS,
                default => GamificationService::XP_LESSON_COMPLETE,
            };
            app(GamificationService::class)->awardXp($user, $xp);
        }
    }
}
