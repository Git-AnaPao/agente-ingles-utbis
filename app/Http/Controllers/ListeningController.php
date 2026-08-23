<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\ListeningLesson;
use App\Models\StudentProgress;
use App\Services\AnswerGradingService;
use App\Services\GoogleDriveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ListeningController extends Controller
{
    public function streamAudio(Request $request, ListeningLesson $listeningLesson)
    {
        $this->assertListeningUnlocked($request, $listeningLesson);

        $fileId = $listeningLesson->audio_drive_file_id;
        abort_unless($fileId, 404);

        $range = $request->header('Range');
        if ($range !== null && ! preg_match('/^bytes=(?:\d+-\d*|-\d+)$/', $range)) {
            return response('', 416, ['Accept-Ranges' => 'bytes']);
        }

        $drive = app(GoogleDriveService::class);
        $info = $drive->getFileInfo($fileId);
        $upstream = $drive->streamFile($fileId, $range);

        if ($upstream->status() === 416) {
            $headers = ['Accept-Ranges' => 'bytes'];
            if ($contentRange = $upstream->header('Content-Range')) {
                $headers['Content-Range'] = $contentRange;
            }

            return response('', 416, $headers);
        }

        if (! in_array($upstream->status(), [200, 206], true)) {
            abort(404, 'Audio no disponible');
        }

        $mimeType = $upstream->header('Content-Type')
            ?: ($info['mimeType'] ?? 'audio/mpeg');
        if ($mimeType === 'video/mpeg' || $mimeType === 'application/octet-stream' || str_contains($mimeType, 'octet-stream')) {
            $mimeType = 'audio/mpeg';
        }

        $headers = [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=3600',
            'Accept-Ranges' => 'bytes',
        ];

        foreach (['Content-Length', 'Content-Range', 'ETag', 'Last-Modified'] as $header) {
            if ($value = $upstream->header($header)) {
                $headers[$header] = $value;
            }
        }

        return response()->stream(function () use ($upstream): void {
            $body = $upstream->toPsrResponse()->getBody();
            while (! $body->eof()) {
                echo $body->read(8192);
                flush();
            }
        }, $upstream->status(), $headers);
    }

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $placement = StudentProgress::latestPlacementFor($user);

        if ($user->isStudent() && ! $placement) {
            return redirect()->route('placement.index');
        }

        $unlockedLevels = $user->isStudent()
            ? StudentProgress::unlockedCefrLevels($user)
            : StudentProgress::CEFR_LEVELS;
        $defaultLevel = $placement?->result_level ?? 'A1';
        $level = (string) $request->query('level', $defaultLevel);

        if (! in_array($level, StudentProgress::CEFR_LEVELS, true)) {
            $level = $defaultLevel;
        }

        abort_unless(in_array($level, $unlockedLevels, true), 403, 'Este nivel todavía está bloqueado.');

        $allLessons = ListeningLesson::query()->ordered()->get();
        $lessons = $allLessons->where('cefr_level', $level)->values();
        $groupedLessons = $lessons->groupBy('sub_level');
        $allLevels = [];

        foreach (StudentProgress::CEFR_LEVELS as $cefr) {
            $levelLessons = $allLessons->where('cefr_level', $cefr);
            $allLevels[$cefr] = [
                'total' => $levelLessons->count(),
                'with_audio' => $levelLessons->filter(fn (ListeningLesson $lesson): bool => (bool) $lesson->audio_url)->count(),
                'has_content' => $levelLessons->isNotEmpty(),
                'unlocked' => in_array($cefr, $unlockedLevels, true),
                'placement_entry' => $placement?->result_level === $cefr,
            ];
        }

        return view('listening.index', compact(
            'lessons',
            'groupedLessons',
            'level',
            'allLevels',
            'placement',
        ));
    }

    public function show(Request $request, ListeningLesson $listeningLesson): View|RedirectResponse
    {
        if ($request->user()->isStudent() && ! StudentProgress::latestPlacementFor($request->user())) {
            return redirect()->route('placement.index');
        }

        $lesson = $this->assertListeningUnlocked($request, $listeningLesson);
        $questionModels = $listeningLesson->questionnaire?->questions?->values() ?? collect();
        $answers = $listeningLesson->answers_data ?? [];
        $questions = collect($listeningLesson->formatted_questions)
            ->values()
            ->map(function (array $question, int $index) use ($questionModels, $answers): array {
                $number = $question['number'] ?? $index + 1;
                $model = $this->questionModelForNumber($questionModels, $number, $index);
                $question['number'] = $number;
                $question['is_speaking'] = $this->isSpeakingQuestion(
                    $question,
                    $answers[$number] ?? null,
                    $model,
                );

                return $question;
            })
            ->all();
        $gradableCount = collect($questions)->where('is_speaking', false)->count();

        return view('listening.show', compact(
            'listeningLesson',
            'lesson',
            'questions',
            'gradableCount',
        ));
    }

    public function checkAnswers(Request $request, ListeningLesson $listeningLesson): JsonResponse
    {
        $lesson = $this->assertListeningUnlocked($request, $listeningLesson);
        $validated = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['nullable', 'string'],
        ]);
        $correctAnswers = $listeningLesson->answers_data ?? [];
        $questionData = collect($listeningLesson->questions_data ?? [])->values();
        $questionModels = $listeningLesson->questionnaire?->questions?->values() ?? collect();
        $gradableNumbers = collect($correctAnswers)
            ->keys()
            ->reject(function ($number) use ($questionData, $questionModels, $correctAnswers): bool {
                $index = max(0, (int) $number - 1);
                $data = $questionData->first(
                    fn (mixed $question): bool => is_array($question)
                        && (string) ($question['number'] ?? '') === (string) $number,
                ) ?? $questionData->get($index, []);
                $data = is_array($data) ? $data : [];
                $model = $this->questionModelForNumber($questionModels, $number, $index);

                return $this->isSpeakingQuestion($data, $correctAnswers[$number] ?? null, $model);
            })
            ->values();

        if ($gradableNumbers->isEmpty()) {
            throw ValidationException::withMessages([
                'answers' => 'Esta actividad no tiene preguntas de listening evaluables.',
            ]);
        }

        $missing = $gradableNumbers->filter(
            fn ($number): bool => ! array_key_exists($number, $validated['answers'])
                || trim((string) $validated['answers'][$number]) === '',
        );

        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'answers' => "Responde las {$gradableNumbers->count()} preguntas evaluables antes de verificar.",
            ]);
        }

        $listeningLesson->setRelation('lesson', $lesson);
        $xpBefore = (int) ($request->user()->xp ?? 0);
        $grading = app(AnswerGradingService::class)->gradeListening(
            user: $request->user(),
            listeningLesson: $listeningLesson,
            correctAnswers: $correctAnswers,
            studentAnswers: $validated['answers'],
            questions: $questionModels,
        );

        $lesson->unsetRelation('progressEvaluationContent');
        $lesson->unsetRelation('listeningLessons');
        StudentProgress::prepareLessonsForProgress([$lesson]);
        $masteredSkills = $request->user()->progress()
            ->where('lesson_id', $lesson->lesson_id)
            ->pluck('student_skill_type')
            ->all();
        $requiredSkills = StudentProgress::requiredSkillsForLesson($lesson);
        $freshUser = $request->user()->fresh();
        $totalXp = (int) ($freshUser->xp ?? 0);

        return response()->json([
            'results' => $grading->results,
            'score' => $grading->score,
            'correct_count' => $grading->correctCount,
            'total_count' => $grading->gradableCount,
            'question_count' => count($correctAnswers),
            'passed' => $grading->passed,
            'attempt_id' => $grading->attempt?->attempt_id,
            'mastered_skills' => $masteredSkills,
            'required_skills' => $requiredSkills,
            'lesson_completed' => StudentProgress::lessonIsComplete($lesson, $masteredSkills),
            'xp_awarded' => max(0, $totalXp - $xpBefore),
            'total_xp' => $totalXp,
            'streak' => (int) ($freshUser->current_streak ?? 0),
            'ai_feedback' => $grading->aiFeedback,
        ]);
    }

    private function assertListeningUnlocked(Request $request, ListeningLesson $listeningLesson): Lesson
    {
        $lesson = $listeningLesson->lesson ?: Lesson::query()
            ->where('lesson_cefr_level', $listeningLesson->cefr_level)
            ->where('lesson_sub_level', $listeningLesson->sub_level)
            ->first();

        abort_unless($lesson, 404, 'Este contenido no está asociado a una lección.');

        if ($request->user()->isStudent()) {
            abort_unless(
                StudentProgress::latestPlacementFor($request->user())
                    && StudentProgress::levelIsUnlocked($request->user(), $lesson->lesson_cefr_level),
                403,
                'Este nivel todavía está bloqueado.',
            );
        }

        return $lesson;
    }

    private function questionModelForNumber(Collection $questions, int|string $number, int $index): mixed
    {
        return $questions->first(
            fn ($question): bool => (int) $question->question_order > 0
                && (string) $question->question_order === (string) $number,
        ) ?? $questions->get($index);
    }

    private function isSpeakingQuestion(array $question, mixed $answer, mixed $model): bool
    {
        return $model?->question_type === 'speaking'
            || strtolower((string) ($question['type'] ?? '')) === 'speaking'
            || strtolower((string) ($question['skill'] ?? '')) === 'speaking'
            || strtolower(trim((string) $answer)) === 'n/a';
    }
}
