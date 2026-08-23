<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AiProvider;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class ExamController extends Controller
{
    private const PASSING_SCORE = 70;

    private const MAX_AUDIO_BYTES = 3_000_000;

    private const MAX_TOTAL_AUDIO_BYTES = 10_000_000;

    private const ALLOWED_AUDIO_MIME_TYPES = [
        'audio/webm',
        'audio/mpeg',
        'audio/mp4',
        'audio/ogg',
        'audio/wav',
    ];

    public function __construct(
        private AiProvider $ai,
    ) {}

    public function submit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lesson_id' => ['required', 'uuid', 'exists:lessons,lesson_id'],
            'responses' => ['required', 'array', 'min:1', 'max:50'],
            'responses.*' => ['required', 'array:question_id,answer,audio_base64,mime_type'],
            'responses.*.question_id' => [
                'required',
                'uuid',
                'distinct:strict',
                'exists:questions,question_id',
            ],
            'responses.*.answer' => ['present', 'nullable', 'string', 'max:5000'],
            'responses.*.audio_base64' => ['nullable', 'string', 'max:4000000'],
            'responses.*.mime_type' => [
                'nullable',
                'required_with:responses.*.audio_base64',
                'string',
                'max:50',
                Rule::in(self::ALLOWED_AUDIO_MIME_TYPES),
            ],
        ]);

        $user = $this->activeUser();
        $lesson = Lesson::findOrFail($validated['lesson_id']);
        abort_unless(
            StudentProgress::latestPlacementFor($user)
                && StudentProgress::levelIsUnlocked($user, $lesson->lesson_cefr_level),
            403,
            'Este nivel todavía está bloqueado.',
        );
        $questionIds = collect($validated['responses'])->pluck('question_id');
        $questions = Question::query()
            ->with(['options', 'questionnaire.listeningLesson'])
            ->whereIn('question_id', $questionIds)
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
            ->get()
            ->keyBy('question_id');

        if ($questions->count() !== count($validated['responses'])) {
            throw ValidationException::withMessages([
                'responses' => ['Todas las preguntas deben pertenecer a la leccion indicada.'],
            ]);
        }

        $totalAudioBytes = 0;
        $hasSpeakingResponses = false;

        foreach ($validated['responses'] as $index => $response) {
            $question = $questions->get($response['question_id']);
            $hasAudio = isset($response['audio_base64']) && $response['audio_base64'] !== '';
            $isSpeaking = $question->question_type === 'speaking'
                || $question->question_skill_type === 'speaking';

            if (! $isSpeaking) {
                if ($hasAudio || isset($response['mime_type'])) {
                    throw ValidationException::withMessages([
                        "responses.{$index}.audio_base64" => ['El audio solo se admite para preguntas orales.'],
                    ]);
                }

                continue;
            }

            $hasSpeakingResponses = true;

            if (! $hasAudio || empty($response['mime_type'])) {
                throw ValidationException::withMessages([
                    "responses.{$index}.audio_base64" => ['La respuesta oral requiere audio y un tipo MIME permitido.'],
                ]);
            }

            $totalAudioBytes += $this->decodedAudioSize($response['audio_base64'], $index);

            if ($totalAudioBytes > self::MAX_TOTAL_AUDIO_BYTES) {
                throw ValidationException::withMessages([
                    'responses' => ['El tamano total de los audios excede el limite permitido.'],
                ]);
            }
        }

        $aiConfigured = $this->ai->isConfigured();

        if ($hasSpeakingResponses && ! $aiConfigured) {
            return response()->json([
                'error' => 'La evaluacion oral no esta disponible temporalmente.',
            ], 503);
        }

        $gradedResponses = [];
        $errors = [];
        $correctCount = 0;

        foreach ($validated['responses'] as $index => $response) {
            /** @var Question $question */
            $question = $questions->get($response['question_id']);
            $isSpeaking = $question->question_type === 'speaking'
                || $question->question_skill_type === 'speaking';

            if ($isSpeaking) {
                try {
                    $aiResult = $this->ai->evaluateSpeakingAudio(
                        audioBase64: $response['audio_base64'],
                        mimeType: $response['mime_type'],
                        questionText: $question->question_text,
                        expectedAnswer: $question->correct_answer,
                    );
                } catch (Throwable $exception) {
                    Log::warning('Speaking evaluation failed', [
                        'exception' => $exception::class,
                    ]);

                    return response()->json([
                        'error' => 'No se pudo evaluar el audio. Intente de nuevo mas tarde.',
                    ], 503);
                }

                if (! is_bool($aiResult['is_correct'] ?? null)) {
                    Log::warning('Speaking evaluation returned an incomplete result');

                    return response()->json([
                        'error' => 'No se pudo evaluar el audio. Intente de nuevo mas tarde.',
                    ], 503);
                }

                $studentAnswer = Str::limit((string) ($aiResult['transcription'] ?? ''), 10_000, '');
                $isCorrect = $aiResult['is_correct'];
                $questionFeedback = $isCorrect
                    ? null
                    : Str::limit((string) ($aiResult['feedback'] ?? ''), 2_000, '');
            } else {
                $studentAnswer = $response['answer'] ?? '';
                $isCorrect = $this->gradeQuestion(
                    $question,
                    $response['answer'],
                    "responses.{$index}.answer",
                );
                $questionFeedback = null;
            }

            if ($isCorrect) {
                $correctCount++;
            } else {
                $errors[] = [
                    'question' => $question->question_text,
                    'student_answer' => $studentAnswer,
                    'feedback' => $questionFeedback,
                ];
            }

            $gradedResponses[] = [
                'question_id' => $question->question_id,
                'student_answer_text' => $studentAnswer,
                'is_correct' => $isCorrect,
                'ai_question_feedback' => $questionFeedback,
            ];
        }

        $total = count($gradedResponses);
        $score = round(($correctCount / $total) * 100, 2);
        $generalFeedback = null;
        $attemptMetadata = $this->attemptMetadata($questions);
        $xpBefore = (int) ($user->xp ?? 0);

        try {
            [$attempt, $storedResponses] = DB::transaction(
                function () use ($user, $validated, $score, $attemptMetadata, $gradedResponses): array {
                    $attempt = AttemptLog::create([
                        'user_id' => $user->user_id,
                        'lesson_id' => $validated['lesson_id'],
                        ...$attemptMetadata,
                        'attempt_score' => $score,
                        'passed' => $score >= self::PASSING_SCORE,
                        'ai_feedback' => null,
                    ]);
                    $storedResponses = [];

                    foreach ($gradedResponses as $response) {
                        $storedResponses[] = StudentResponse::create([
                            'attempt_id' => $attempt->attempt_id,
                            'question_id' => $response['question_id'],
                            'student_answer_text' => $response['student_answer_text'],
                            'is_correct' => $response['is_correct'],
                            'ai_question_feedback' => $response['ai_question_feedback'],
                        ]);
                    }

                    return [$attempt, $storedResponses];
                }
            );
        } catch (Throwable $exception) {
            Log::error('Exam persistence failed', [
                'exception' => $exception::class,
            ]);

            return response()->json([
                'error' => 'Error al guardar las respuestas.',
            ], 500);
        }

        $this->syncEligibleSkills(
            $user,
            $lesson,
            $questions->map(
                fn (Question $question): string => StudentProgress::normalizeSkill(
                    $question->question_skill_type,
                    $question->question_type,
                ) ?? 'reading',
            ),
        );
        app(GamificationService::class)->recordActivity($user);

        // General feedback is enrichment: persistence and progression must not depend on it.
        if ($aiConfigured) {
            try {
                $feedback = $this->ai->generateGeneralFeedback(
                    score: $score,
                    total: $total,
                    correct: $correctCount,
                    errors: $errors,
                );
                $generalFeedback = is_string($feedback) && trim($feedback) !== ''
                    ? Str::limit($feedback, 10_000, '')
                    : null;

                if ($generalFeedback !== null) {
                    $attempt->update(['ai_feedback' => $generalFeedback]);
                }
            } catch (Throwable $exception) {
                Log::warning('General exam feedback failed after progress was saved', [
                    'attempt_id' => $attempt->attempt_id,
                    'exception' => $exception::class,
                ]);
            }
        }

        $freshUser = $user->fresh();

        return response()->json([
            'attempt_id' => $attempt->attempt_id,
            'score' => (float) $attempt->attempt_score,
            'passed' => $attempt->passed,
            'ai_feedback' => $generalFeedback,
            'xp_awarded' => max(0, (int) $freshUser->xp - $xpBefore),
            'total_xp' => (int) ($freshUser->xp ?? 0),
            'responses' => collect($storedResponses)
                ->map(fn (StudentResponse $response): array => $this->responsePayload($response))
                ->all(),
        ]);
    }

    public function result(AttemptLog $attempt): JsonResponse
    {
        $user = $this->activeUser();

        if ($attempt->user_id !== $user->user_id) {
            return response()->json(['error' => 'No encontrado.'], 404);
        }

        $responses = StudentResponse::query()
            ->where('attempt_id', $attempt->attempt_id)
            ->with([
                'question' => fn ($query) => $query->select(
                    'question_id',
                    'question_type',
                    'question_text',
                ),
            ])
            ->get();

        return response()->json([
            'attempt' => [
                'attempt_id' => $attempt->attempt_id,
                'lesson_id' => $attempt->lesson_id,
                'score' => (float) $attempt->attempt_score,
                'passed' => $attempt->passed,
                'ai_feedback' => $attempt->ai_feedback,
                'attempted_at' => $attempt->attempted_at?->toISOString(),
            ],
            'responses' => $responses
                ->map(fn (StudentResponse $response): array => $this->responsePayload($response, true))
                ->all(),
        ]);
    }

    private function activeUser(): User
    {
        $user = auth('api')->user();

        abort_unless($user instanceof User && $user->user_status === 'active', 403, 'Cuenta no disponible.');

        return $user;
    }

    private function decodedAudioSize(string $audioBase64, int $index): int
    {
        $canonicalBase64 = preg_match(
            '/^(?:[A-Za-z0-9+\/]{4})*(?:[A-Za-z0-9+\/]{2}==|[A-Za-z0-9+\/]{3}=)?$/D',
            $audioBase64,
        );
        $decoded = $canonicalBase64 === 1 ? base64_decode($audioBase64, true) : false;

        if ($decoded === false || $decoded === '') {
            throw ValidationException::withMessages([
                "responses.{$index}.audio_base64" => ['El audio debe usar Base64 valido.'],
            ]);
        }

        $size = strlen($decoded);

        if ($size > self::MAX_AUDIO_BYTES) {
            throw ValidationException::withMessages([
                "responses.{$index}.audio_base64" => ['El audio excede el limite de 3 MB.'],
            ]);
        }

        return $size;
    }

    private function gradeQuestion(Question $question, ?string $answer, string $errorKey): bool
    {
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

    /**
     * @return array<string, mixed>
     */
    private function responsePayload(StudentResponse $response, bool $includeQuestion = false): array
    {
        $payload = [
            'response_id' => $response->response_id,
            'question_id' => $response->question_id,
            'student_answer_text' => $response->student_answer_text,
            'is_correct' => $response->is_correct,
            'ai_question_feedback' => $response->ai_question_feedback,
        ];

        if ($includeQuestion && $response->question) {
            $payload['question'] = [
                'question_id' => $response->question->question_id,
                'question_type' => $response->question->question_type,
                'question_text' => $response->question->question_text,
            ];
        }

        return $payload;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Question>  $questions
     * @return array{attempt_skill_type: ?string, questionnaire_id: ?string, listening_lesson_id: ?string}
     */
    private function attemptMetadata($questions): array
    {
        $metadata = $questions
            ->map(fn (Question $question): array => StudentProgress::attemptMetadataForQuestion($question))
            ->unique(fn (array $item): string => json_encode($item, JSON_THROW_ON_ERROR))
            ->values();

        if ($metadata->count() === 1) {
            return $metadata->first();
        }

        return [
            'attempt_skill_type' => null,
            'questionnaire_id' => null,
            'listening_lesson_id' => null,
        ];
    }

    /**
     * @param  iterable<int, string>  $skills
     */
    private function syncEligibleSkills(User $user, Lesson $lesson, iterable $skills): void
    {
        $placementTestId = StudentProgress::latestPlacementFor($user)?->placement_test_id;

        foreach (collect($skills)->unique() as $skill) {
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
