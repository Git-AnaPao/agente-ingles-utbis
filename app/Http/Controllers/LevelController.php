<?php

namespace App\Http\Controllers;

use App\Contracts\AiProvider;
use App\Models\AttemptLog;
use App\Models\Lesson;
use App\Models\ListeningLesson;
use App\Models\Questionnaire;
use App\Models\StudentProgress;
use App\Models\StudentResponse;
use App\Services\AnswerGradingService;
use App\Services\GamificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class LevelController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $placement = StudentProgress::latestPlacementFor($user);

        if ($user->isStudent() && ! $placement) {
            return redirect()->route('placement.index');
        }

        $lessons = Lesson::query()
            ->with([
                'questionnaires.questions.options',
                'listeningLessons.questionnaire.questions.options',
            ])
            ->orderBy('lesson_cefr_level')
            ->orderBy('lesson_sub_level')
            ->get();

        $allContent = ListeningLesson::query()
            ->with('questionnaire.questions')
            ->ordered()
            ->get();

        foreach ($lessons as $lesson) {
            $lesson->setRelation('listeningLessons', $this->contentForLesson($lesson, $allContent));
        }

        $progressByLesson = $user->progress()->get()->groupBy('lesson_id');
        $attemptedIds = AttemptLog::query()
            ->where('user_id', $user->user_id)
            ->pluck('lesson_id')
            ->unique()
            ->all();

        $unlockedLevels = $user->isStudent()
            ? StudentProgress::unlockedCefrLevels($user)
            : StudentProgress::CEFR_LEVELS;
        $placementIndex = array_search($placement?->result_level ?? 'A1', StudentProgress::CEFR_LEVELS, true);
        $placementIndex = $placementIndex === false ? 0 : $placementIndex;

        $levels = [];

        foreach (StudentProgress::CEFR_LEVELS as $levelIndex => $cefr) {
            $levelLessons = $lessons->where('lesson_cefr_level', $cefr)->values();
            $subLevels = [];

            foreach ($levelLessons as $lesson) {
                $requiredSkills = StudentProgress::requiredSkillsForLesson($lesson);
                $availableSkills = StudentProgress::availableSkillsForLesson($lesson);
                $masteredSkills = $progressByLesson
                    ->get($lesson->lesson_id, collect())
                    ->pluck('student_skill_type')
                    ->unique()
                    ->values()
                    ->all();
                $activityCounts = $this->skillActivityCounts($lesson);

                $subLevels[] = [
                    'sub_level' => $lesson->lesson_sub_level,
                    'lesson' => $lesson,
                    'completed' => StudentProgress::lessonIsComplete($lesson, $masteredSkills),
                    'evaluable' => $requiredSkills !== [],
                    'attempted' => in_array($lesson->lesson_id, $attemptedIds, true),
                    'topics' => $lesson->listeningLessons->pluck('title')->filter()->values()->all(),
                    'required_skills' => $requiredSkills,
                    'mastered_skills' => $masteredSkills,
                    'skills' => collect(StudentProgress::LEARNING_SKILLS)
                        ->mapWithKeys(fn (string $skill): array => [
                            $skill => [
                                'available' => in_array($skill, $availableSkills, true),
                                'mastered' => in_array($skill, $masteredSkills, true),
                                'activities' => $activityCounts[$skill],
                            ],
                        ])
                        ->all(),
                ];
            }

            $completedCount = count(array_filter($subLevels, fn (array $node): bool => $node['completed']));
            $evaluableCount = count(array_filter($subLevels, fn (array $node): bool => $node['evaluable']));
            $levelComplete = $evaluableCount > 0 && $completedCount === $evaluableCount;
            $isUnlocked = in_array($cefr, $unlockedLevels, true);

            $status = match (true) {
                $levelComplete => 'completed',
                ! $isUnlocked => 'locked',
                $levelIndex < $placementIndex => 'placement-open',
                default => 'current',
            };

            $levels[] = [
                'cefr' => $cefr,
                'sub_levels' => $subLevels,
                'total' => count($subLevels),
                'completed' => $completedCount,
                'status' => $status,
                'placement_entry' => $levelIndex === $placementIndex,
            ];
        }

        return view('levels.index', [
            'levels' => $levels,
            'placement' => $placement,
            'gamification' => app(GamificationService::class)->snapshot($user),
        ]);
    }

    public function learn(Request $request, Lesson $lesson): View|RedirectResponse
    {
        if ($redirect = $this->placementRedirect($request)) {
            return $redirect;
        }

        $this->assertLessonUnlocked($request, $lesson);

        $questionnaires = $lesson->questionnaires()
            ->with(['questions.options', 'listeningLesson'])
            ->orderBy('created_at')
            ->get();
        $contentLessons = $this->contentForLesson($lesson);

        $lesson->setRelation('questionnaires', $questionnaires);
        $lesson->setRelation('listeningLessons', $contentLessons);

        $availableSkills = StudentProgress::availableSkillsForLesson($lesson);
        $requestedTab = $request->query('tab');
        $activeTab = is_string($requestedTab) && in_array($requestedTab, $availableSkills, true)
            ? $requestedTab
            : ($availableSkills[0] ?? 'reading');

        // The current view uses this variable to render tab availability.
        $requiredSkills = $availableSkills;
        $masteredSkills = $request->user()->progress()
            ->where('lesson_id', $lesson->lesson_id)
            ->pluck('student_skill_type')
            ->all();
        $payload = $lesson->lesson_prompt_payload ?? [];
        $title = $contentLessons->pluck('title')->filter()->first()
            ?? ($payload['title'] ?? $payload['topic'] ?? "Nivel {$lesson->lesson_cefr_level}.{$lesson->lesson_sub_level}");
        $objective = $payload['objective'] ?? $payload['objectives'] ?? $payload['prompt'] ?? null;

        if (is_array($objective)) {
            $objective = implode(' ', array_filter($objective, 'is_string'));
        }

        return view('levels.learn', [
            'lesson' => $lesson,
            'questionnaires' => $questionnaires,
            'contentLessons' => $contentLessons,
            'activeTab' => $activeTab,
            'requiredSkills' => $requiredSkills,
            'masteredSkills' => $masteredSkills,
            'title' => $title,
            'objective' => $objective,
            'geminiConfigured' => app(AiProvider::class)->isConfigured(),
            'gamification' => app(GamificationService::class)->snapshot($request->user()),
        ]);
    }

    public function checkPractice(Request $request, Lesson $lesson): JsonResponse
    {
        $this->assertLessonUnlocked($request, $lesson);

        $validated = $request->validate([
            'questionnaire_id' => ['required', 'uuid'],
            'skill' => ['required', 'in:reading,listening'],
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'string'],
        ]);

        $questionnaire = Questionnaire::with(['questions.options', 'listeningLesson'])
            ->findOrFail($validated['questionnaire_id']);

        $fallbackMatches = $questionnaire->listeningLesson
            && $questionnaire->listeningLesson->lesson_id === null
            && $questionnaire->listeningLesson->cefr_level === $lesson->lesson_cefr_level
            && (int) $questionnaire->listeningLesson->sub_level === (int) $lesson->lesson_sub_level;

        if ($questionnaire->lesson_id !== $lesson->lesson_id && ! $fallbackMatches) {
            return response()->json(['error' => 'El cuestionario no pertenece a esta lección.'], 403);
        }

        $skill = $validated['skill'];
        $questions = $questionnaire->questions
            ->filter(function ($question) use ($skill): bool {
                if ($question->question_type === 'speaking') {
                    return false;
                }

                return $skill === 'reading'
                    ? in_array($question->question_skill_type, ['reading', 'writing'], true)
                    : $question->question_skill_type === 'listening';
            })
            ->values();

        if ($questions->isEmpty()) {
            throw ValidationException::withMessages([
                'answers' => 'Este ejercicio no tiene preguntas evaluables para la habilidad seleccionada.',
            ]);
        }

        $missing = $questions->pluck('question_id')->filter(
            fn (string $id): bool => ! array_key_exists($id, $validated['answers'])
                || trim((string) $validated['answers'][$id]) === '',
        );

        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'answers' => 'Responde todas las preguntas antes de verificar el ejercicio.',
            ]);
        }

        $xpBefore = (int) ($request->user()->xp ?? 0);
        $grading = app(AnswerGradingService::class)->gradePractice(
            user: $request->user(),
            lesson: $lesson,
            questionnaire: $questionnaire,
            questions: $questions,
            studentAnswers: $validated['answers'],
            skill: $skill,
        );

        $this->loadLessonContent($lesson);
        $masteredSkills = $request->user()->progress()
            ->where('lesson_id', $lesson->lesson_id)
            ->pluck('student_skill_type')
            ->all();
        $requiredSkills = StudentProgress::requiredSkillsForLesson($lesson);
        $totalXp = (int) $request->user()->fresh()->xp;

        return response()->json([
            'results' => $grading->results,
            'score' => $grading->score,
            'correct_count' => $grading->correctCount,
            'gradable_count' => $grading->gradableCount,
            'passed' => $grading->passed,
            'attempt_id' => $grading->attempt?->attempt_id,
            'skill' => $skill,
            'mastered_skills' => $masteredSkills,
            'required_skills' => $requiredSkills,
            'lesson_completed' => StudentProgress::lessonIsComplete($lesson, $masteredSkills),
            'xp_awarded' => max(0, $totalXp - $xpBefore),
            'total_xp' => $totalXp,
            'streak' => (int) ($request->user()->fresh()->current_streak ?? 0),
            'ai_feedback' => $grading->aiFeedback,
        ]);
    }

    public function speakingFeedback(
        Request $request,
        Lesson $lesson,
        ListeningLesson $listeningLesson,
    ): JsonResponse {
        $this->assertLessonUnlocked($request, $lesson);

        $validated = $request->validate([
            'audio_base64' => ['required', 'string', 'max:4000000'],
            'mime_type' => ['nullable', 'string', 'regex:/^audio\/(webm|mp4|ogg|mpeg|wav)(;.*)?$/'],
        ]);

        if ($listeningLesson->lesson_id !== $lesson->lesson_id) {
            return response()->json(['error' => 'El contenido no pertenece a esta lección.'], 403);
        }

        if (! is_string($listeningLesson->speaking_text) || trim($listeningLesson->speaking_text) === '') {
            return response()->json(['error' => 'Este contenido no tiene una actividad de speaking.'], 422);
        }

        $ai = app(AiProvider::class);
        if (! $ai->isConfigured()) {
            return response()->json(['error' => 'La evaluación de speaking no está disponible en este momento.'], 503);
        }

        $audioBase64 = $validated['audio_base64'];
        if (str_contains($audioBase64, 'base64,')) {
            $audioBase64 = substr($audioBase64, strpos($audioBase64, 'base64,') + 7);
        }

        if ($audioBase64 === '' || base64_decode($audioBase64, true) === false) {
            throw ValidationException::withMessages([
                'audio_base64' => 'La grabación recibida no es válida.',
            ]);
        }

        try {
            $result = $ai->evaluateSpeakingAudio(
                audioBase64: $audioBase64,
                mimeType: $validated['mime_type'] ?? 'audio/webm',
                questionText: "Read this text aloud: {$listeningLesson->speaking_text}",
                expectedAnswer: $listeningLesson->speaking_text,
            );
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'error' => 'No fue posible evaluar la grabación. Intenta más tarde.',
            ], 503);
        }

        $isCorrect = is_bool($result['is_correct'] ?? null) ? $result['is_correct'] : null;
        if ($isCorrect === null) {
            return response()->json([
                ...$result,
                'is_correct' => null,
                'evaluated' => false,
                'passed' => null,
                'attempt_id' => null,
                'xp_awarded' => 0,
                'total_xp' => (int) ($request->user()->xp ?? 0),
            ]);
        }

        $user = $request->user();
        $xpBefore = (int) ($user->xp ?? 0);
        $attempt = null;

        DB::transaction(function () use ($user, $lesson, $listeningLesson, $result, $isCorrect, &$attempt): void {
            $attempt = AttemptLog::create([
                'user_id' => $user->user_id,
                'lesson_id' => $lesson->lesson_id,
                'attempt_skill_type' => 'speaking',
                'questionnaire_id' => $listeningLesson->questionnaire?->questionnaire_id,
                'listening_lesson_id' => $listeningLesson->listening_lesson_id,
                'attempt_score' => $isCorrect ? 100 : 0,
                'ai_feedback' => (string) ($result['feedback'] ?? ''),
                'passed' => $isCorrect,
            ]);

            $question = $listeningLesson->questionnaire()
                ->with('questions')
                ->first()
                ?->questions
                ->firstWhere('question_type', 'speaking');

            if ($question) {
                StudentResponse::create([
                    'attempt_id' => $attempt->attempt_id,
                    'question_id' => $question->question_id,
                    'student_answer_text' => (string) ($result['transcription'] ?? ''),
                    'is_correct' => $isCorrect,
                    'ai_question_feedback' => (string) ($result['feedback'] ?? ''),
                ]);
            }

            if ($isCorrect) {
                $progress = StudentProgress::masterSkillWhenEligible(
                    $user,
                    $lesson,
                    'speaking',
                    StudentProgress::latestPlacementFor($user)?->placement_test_id,
                );

                if ($progress?->wasRecentlyCreated) {
                    app(GamificationService::class)->awardXp($user, GamificationService::XP_SPEAKING_PASS);
                }
            }

            app(GamificationService::class)->recordActivity($user);
        });

        $this->loadLessonContent($lesson);
        $masteredSkills = $user->progress()
            ->where('lesson_id', $lesson->lesson_id)
            ->pluck('student_skill_type')
            ->all();
        $totalXp = (int) $user->fresh()->xp;

        return response()->json([
            ...$result,
            'is_correct' => $isCorrect,
            'evaluated' => true,
            'passed' => $isCorrect,
            'attempt_id' => $attempt?->attempt_id,
            'mastered_skills' => $masteredSkills,
            'required_skills' => StudentProgress::requiredSkillsForLesson($lesson),
            'lesson_completed' => StudentProgress::lessonIsComplete($lesson, $masteredSkills),
            'xp_awarded' => max(0, $totalXp - $xpBefore),
            'total_xp' => $totalXp,
            'streak' => (int) ($user->fresh()->current_streak ?? 0),
        ]);
    }

    private function placementRedirect(Request $request): ?RedirectResponse
    {
        if ($request->user()->isStudent() && ! StudentProgress::latestPlacementFor($request->user())) {
            return redirect()->route('placement.index');
        }

        return null;
    }

    private function assertLessonUnlocked(Request $request, Lesson $lesson): void
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return;
        }

        abort_unless(
            StudentProgress::latestPlacementFor($user)
                && StudentProgress::levelIsUnlocked($user, $lesson->lesson_cefr_level),
            403,
            'Este nivel todavía está bloqueado.',
        );
    }

    /**
     * @param  Collection<int, ListeningLesson>|null  $allContent
     * @return Collection<int, ListeningLesson>
     */
    private function contentForLesson(Lesson $lesson, ?Collection $allContent = null): Collection
    {
        $content = $allContent ?? ListeningLesson::query()
            ->with('questionnaire.questions')
            ->ordered()
            ->get();

        return $content
            ->filter(fn (ListeningLesson $item): bool => $item->lesson_id === $lesson->lesson_id
                || (
                    $item->lesson_id === null
                    && $item->cefr_level === $lesson->lesson_cefr_level
                    && (int) $item->sub_level === (int) $lesson->lesson_sub_level
                ))
            ->values();
    }

    private function loadLessonContent(Lesson $lesson): void
    {
        $lesson->load('questionnaires.questions');
        $lesson->setRelation('listeningLessons', $this->contentForLesson($lesson));
    }

    /**
     * @return array{reading: int, listening: int, speaking: int}
     */
    private function skillActivityCounts(Lesson $lesson): array
    {
        return collect(StudentProgress::LEARNING_SKILLS)
            ->mapWithKeys(fn (string $skill): array => [
                $skill => count(StudentProgress::evaluableActivitiesForSkill($lesson, $skill)),
            ])
            ->all();
    }
}
