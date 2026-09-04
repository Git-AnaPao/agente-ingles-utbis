<?php

namespace App\Http\Controllers;

use App\Contracts\AiProvider;
use App\Models\AttemptLog;
use App\Models\ListeningLesson;
use App\Models\StudentProgress;
use App\Models\StudentResponse;
use App\Models\User;
use App\Services\AnswerGradingService;
use App\Services\GamificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $allContent = ListeningLesson::query()
            ->with('questionnaire.questions')
            ->ordered()
            ->get();

        $allProgress = $user->progress()->get();
        $progressByListeningLesson = $allProgress->groupBy('listening_lesson_id');

        $unlockedLevels = $user->isStudent()
            ? StudentProgress::unlockedCefrLevels($user)
            : StudentProgress::CEFR_LEVELS;
        $placementIndex = array_search($placement?->result_level ?? 'A1', StudentProgress::CEFR_LEVELS, true);
        $placementIndex = $placementIndex === false ? 0 : $placementIndex;

        $levels = [];

        foreach (StudentProgress::CEFR_LEVELS as $levelIndex => $cefr) {
            $levelListeningLessons = $allContent->where('cefr_level', $cefr)->sortBy('sort_order')->values();
            $isLevelUnlocked = in_array($cefr, $unlockedLevels, true);
            $lessonRows = [];
            // Computed in a single pass instead of re-querying "is the previous
            // lesson complete?" per row (that turned the page into an N+1 query
            // storm — 2 extra round trips per lesson, ~100+ lessons per level).
            $previousComplete = true;

            foreach ($levelListeningLessons as $index => $listeningLesson) {
                $requiredSkills = StudentProgress::requiredSkillsForListeningLesson($listeningLesson);
                $availableSkills = StudentProgress::availableSkillsForListeningLesson($listeningLesson);
                $masteredSkills = $progressByListeningLesson
                    ->get($listeningLesson->listening_lesson_id, collect())
                    ->pluck('student_skill_type')
                    ->unique()
                    ->values()
                    ->all();
                $completed = StudentProgress::listeningLessonIsComplete($listeningLesson, $masteredSkills);
                $unlocked = $user->isStudent()
                    ? ($isLevelUnlocked && $previousComplete)
                    : true;
                $previousComplete = $completed;

                $lessonRows[] = [
                    'number' => $index + 1,
                    'listeningLesson' => $listeningLesson,
                    'completed' => $completed,
                    'unlocked' => $unlocked,
                    'evaluable' => $requiredSkills !== [],
                    'steps_total' => count($availableSkills),
                    'steps_done' => count(array_intersect($availableSkills, $masteredSkills)),
                ];
            }

            $completedCount = count(array_filter($lessonRows, fn (array $row): bool => $row['completed']));
            $evaluableCount = count(array_filter($lessonRows, fn (array $row): bool => $row['evaluable']));
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
                'lessons' => $lessonRows,
                'total' => count($lessonRows),
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

    public function learn(Request $request, ListeningLesson $listeningLesson): View|RedirectResponse
    {
        if ($redirect = $this->placementRedirect($request)) {
            return $redirect;
        }

        $this->assertLessonUnlocked($request, $listeningLesson);

        $listeningLesson->loadMissing('questionnaire.questions.options');

        $availableSkills = StudentProgress::availableSkillsForListeningLesson($listeningLesson);
        $requiredSkills = StudentProgress::requiredSkillsForListeningLesson($listeningLesson);
        $masteredSkills = $request->user()->progress()
            ->where('listening_lesson_id', $listeningLesson->listening_lesson_id)
            ->pluck('student_skill_type')
            ->all();

        // Skills inside a lesson are freely navigable in any order — only
        // the lesson-to-lesson sequence is gated. Fall back to the first
        // pending one so the page has something sensible to show by default.
        $requestedTab = $request->query('tab');
        $pendingSkill = collect($availableSkills)->first(
            fn (string $skill): bool => ! in_array($skill, $masteredSkills, true),
        );
        $activeTab = (is_string($requestedTab) && in_array($requestedTab, $availableSkills, true))
            ? $requestedTab
            : ($pendingSkill ?? collect($availableSkills)->first() ?? 'reading');

        return view('levels.learn', [
            'listeningLesson' => $listeningLesson,
            'activeTab' => $activeTab,
            'availableSkills' => $availableSkills,
            'requiredSkills' => $requiredSkills,
            'masteredSkills' => $masteredSkills,
            'activitiesTotal' => count($availableSkills),
            'activitiesDone' => count(array_intersect($availableSkills, $masteredSkills)),
            'title' => $listeningLesson->title,
            'lessonPath' => $this->lessonPathForUnit($request->user(), $listeningLesson),
            'geminiConfigured' => app(AiProvider::class)->isConfigured(),
            'gamification' => app(GamificationService::class)->snapshot($request->user()),
            'nextLessonUrl' => $this->nextLessonUrl($listeningLesson),
        ]);
    }

    /**
     * The horizontal lesson picker shown inside the station: every lesson
     * of the same import unit ("#1 Presentaciones...", "#2 Mi Mundo...",
     * up to ~16), each tagged with its lock state.
     *
     * @return list<array{listeningLesson: ListeningLesson, number: int, completed: bool, unlocked: bool, current: bool}>
     */
    private function lessonPathForUnit(User $user, ListeningLesson $active): array
    {
        $siblings = ListeningLesson::query()
            ->where('lesson_id', $active->lesson_id)
            ->orderBy('sort_order')
            ->get();

        $masteredBySibling = $user->progress()
            ->whereIn('listening_lesson_id', $siblings->pluck('listening_lesson_id'))
            ->get()
            ->groupBy('listening_lesson_id');

        return $siblings->map(function (ListeningLesson $sibling, int $index) use ($user, $active, $masteredBySibling): array {
            $mastered = $masteredBySibling->get($sibling->listening_lesson_id, collect())
                ->pluck('student_skill_type')
                ->all();
            $completed = StudentProgress::listeningLessonIsComplete($sibling, $mastered);
            $unlocked = $user->isStudent()
                ? StudentProgress::listeningLessonIsUnlocked($user, $sibling)
                : true;

            return [
                'listeningLesson' => $sibling,
                'number' => $index + 1,
                'completed' => $completed,
                'unlocked' => $unlocked,
                'current' => $sibling->listening_lesson_id === $active->listening_lesson_id,
            ];
        })->all();
    }

    private function nextLessonUrl(ListeningLesson $listeningLesson): ?string
    {
        $next = ListeningLesson::query()
            ->where('cefr_level', $listeningLesson->cefr_level)
            ->where('sort_order', '>', $listeningLesson->sort_order)
            ->orderBy('sort_order')
            ->first();

        if (! $next) {
            $levelIndex = array_search($listeningLesson->cefr_level, StudentProgress::CEFR_LEVELS, true);
            $nextCefr = $levelIndex === false ? null : (StudentProgress::CEFR_LEVELS[$levelIndex + 1] ?? null);

            $next = $nextCefr
                ? ListeningLesson::query()
                    ->where('cefr_level', $nextCefr)
                    ->orderBy('sort_order')
                    ->first()
                : null;
        }

        return $next ? route('lessons.learn', $next) : null;
    }

    public function checkPractice(Request $request, ListeningLesson $listeningLesson): JsonResponse
    {
        $this->assertLessonUnlocked($request, $listeningLesson);

        $validated = $request->validate([
            'skill' => ['required', 'in:reading,writing,listening'],
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'string'],
        ]);

        $questionnaire = $listeningLesson->questionnaire()->with(['questions.options'])->first();

        if (! $questionnaire) {
            return response()->json(['error' => 'Esta lección no tiene un cuestionario configurado.'], 422);
        }

        $skill = $validated['skill'];
        $questions = $questionnaire->questions
            ->filter(fn ($question): bool => $question->question_skill_type === $skill
                && $question->question_type !== 'speaking')
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
            listeningLesson: $listeningLesson,
            questionnaire: $questionnaire,
            questions: $questions,
            studentAnswers: $validated['answers'],
            skill: $skill,
        );

        $masteredSkills = $request->user()->progress()
            ->where('listening_lesson_id', $listeningLesson->listening_lesson_id)
            ->pluck('student_skill_type')
            ->all();
        $requiredSkills = StudentProgress::requiredSkillsForListeningLesson($listeningLesson);
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
            'lesson_completed' => StudentProgress::listeningLessonIsComplete($listeningLesson, $masteredSkills),
            'xp_awarded' => max(0, $totalXp - $xpBefore),
            'total_xp' => $totalXp,
            'streak' => (int) ($request->user()->fresh()->current_streak ?? 0),
            'ai_feedback' => $grading->aiFeedback,
        ]);
    }

    public function speakingFeedback(
        Request $request,
        ListeningLesson $listeningLesson,
    ): JsonResponse {
        $this->assertLessonUnlocked($request, $listeningLesson);

        $validated = $request->validate([
            'audio_base64' => ['required', 'string', 'max:4000000'],
            'mime_type' => ['nullable', 'string', 'regex:/^audio\/(webm|mp4|ogg|mpeg|wav)(;.*)?$/'],
        ]);

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

        DB::transaction(function () use ($user, $listeningLesson, $result, $isCorrect, &$attempt): void {
            $attempt = AttemptLog::create([
                'user_id' => $user->user_id,
                'lesson_id' => $listeningLesson->lesson_id,
                'attempt_skill_type' => 'speaking',
                'questionnaire_id' => $listeningLesson->questionnaire?->questionnaire_id,
                'listening_lesson_id' => $listeningLesson->listening_lesson_id,
                'attempt_score' => (float) ($result['overall_score'] ?? ($isCorrect ? 100 : 0)),
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
                $progress = StudentProgress::masterListeningLessonSkillWhenEligible(
                    $user,
                    $listeningLesson,
                    'speaking',
                    StudentProgress::latestPlacementFor($user)?->placement_test_id,
                );

                if ($progress?->wasRecentlyCreated) {
                    app(GamificationService::class)->awardXp($user, GamificationService::XP_SPEAKING_PASS);
                }
            }

            app(GamificationService::class)->recordActivity($user);
        });

        $masteredSkills = $user->progress()
            ->where('listening_lesson_id', $listeningLesson->listening_lesson_id)
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
            'required_skills' => StudentProgress::requiredSkillsForListeningLesson($listeningLesson),
            'lesson_completed' => StudentProgress::listeningLessonIsComplete($listeningLesson, $masteredSkills),
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

    private function assertLessonUnlocked(Request $request, ListeningLesson $listeningLesson): void
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return;
        }

        abort_unless(
            StudentProgress::latestPlacementFor($user)
                && StudentProgress::listeningLessonIsUnlocked($user, $listeningLesson),
            403,
            'Esta lección todavía está bloqueada. Completa la lección anterior primero.',
        );
    }

}
