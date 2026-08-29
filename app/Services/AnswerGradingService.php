<?php

namespace App\Services;

use App\Contracts\AiProvider;
use App\Models\AttemptLog;
use App\Models\ListeningLesson;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\StudentProgress;
use App\Models\StudentResponse;
use App\Models\User;
use App\Support\AnswerNormalizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AnswerGradingService
{
    public function __construct(
        private readonly GamificationService $gamification,
        private readonly AiProvider $ai,
    ) {}

    /**
     * Resultado de una sesión de calificación.
     */
    public readonly array $results;

    public readonly int $correctCount;

    public readonly int $gradableCount;

    public readonly int $score;

    public readonly bool $passed;

    public readonly ?AttemptLog $attempt;

    public readonly ?string $aiFeedback;

    /**
     * Califica un conjunto de preguntas de un cuestionario de lección (práctica).
     *
     * @param  Collection<Question>  $questions
     * @param  array<string, string>  $studentAnswers  [question_id => answer]
     */
    public function gradePractice(
        User $user,
        ListeningLesson $listeningLesson,
        Questionnaire $questionnaire,
        Collection $questions,
        array $studentAnswers,
        string $skill,
    ): self {
        if (! in_array($skill, ['reading', 'writing', 'listening'], true)) {
            throw new \InvalidArgumentException("Unsupported practice skill: {$skill}");
        }

        $results = [];
        $correctCount = 0;
        $gradableCount = 0;
        $errors = [];
        $attempt = null;

        DB::transaction(function () use ($user, $listeningLesson, $questionnaire, $questions, $studentAnswers, $skill, &$results, &$correctCount, &$gradableCount, &$errors, &$attempt) {
            $attempt = AttemptLog::create([
                'user_id' => $user->user_id,
                'lesson_id' => $listeningLesson->lesson_id,
                'attempt_skill_type' => $skill,
                'questionnaire_id' => $questionnaire->questionnaire_id,
                'listening_lesson_id' => $listeningLesson->listening_lesson_id,
                'attempt_score' => 0,
                'passed' => false,
            ]);

            foreach ($questions as $question) {
                if ($question->question_type === 'speaking') {
                    continue;
                }

                $gradableCount++;
                $studentAnswer = $studentAnswers[$question->question_id] ?? '';
                $isCorrect = $this->gradeQuestion($question, $studentAnswer);

                if ($isCorrect) {
                    $correctCount++;
                } else {
                    $errors[] = [
                        'question' => $question->question_text,
                        'student_answer' => $studentAnswer,
                    ];
                }

                $results[$question->question_id] = [
                    'student_answer' => $studentAnswer,
                    'correct_answer' => $question->correct_answer,
                    'correct_option_id' => $question->question_type === 'multiple_choice'
                        ? ($question->options->firstWhere('is_correct', true)?->option_id ?? null)
                        : null,
                    'is_correct' => $isCorrect,
                ];

                StudentResponse::create([
                    'attempt_id' => $attempt->attempt_id,
                    'question_id' => $question->question_id,
                    'student_answer_text' => $studentAnswer,
                    'is_correct' => $isCorrect,
                    'ai_question_feedback' => null,
                ]);
            }

            $score = $gradableCount > 0 ? round(($correctCount / $gradableCount) * 100) : 0;
            $passed = $score >= StudentProgress::PASSING_SCORE;

            $attempt->update(['attempt_score' => $score, 'passed' => $passed]);

            if ($passed) {
                $progress = StudentProgress::masterListeningLessonSkillWhenEligible(
                    $user,
                    $listeningLesson,
                    $skill,
                    StudentProgress::latestPlacementFor($user)?->placement_test_id,
                );

                if ($progress?->wasRecentlyCreated) {
                    $xp = $skill === 'listening'
                        ? GamificationService::XP_LISTENING_PASS
                        : GamificationService::XP_LESSON_COMPLETE;
                    $this->gamification->awardXp($user, $xp);
                }
            }

            $this->gamification->recordActivity($user);
        });

        return $this->buildResult($results, $correctCount, $gradableCount, $attempt, $errors);
    }

    /**
     * Califica un conjunto de respuestas de una lección de listening.
     *
     * @param  array<int|string, string>  $correctAnswers  [num => correct_text]
     * @param  array<int|string, string>  $studentAnswers  [num => student_text]
     * @param  Collection<Question>  $questions
     */
    public function gradeListening(
        User $user,
        ListeningLesson $listeningLesson,
        array $correctAnswers,
        array $studentAnswers,
        Collection $questions,
    ): self {
        $results = [];
        $correctCount = 0;
        $totalCount = 0;
        $errors = [];
        $attempt = null;
        $questionModels = [];
        $questionData = collect($listeningLesson->questions_data ?? []);

        foreach ($correctAnswers as $num => $correctAnswer) {
            $studentAnswer = $studentAnswers[$num] ?? '';
            $question = $questions->first(
                fn ($candidate): bool => (int) $candidate->question_order > 0
                    && (string) $candidate->question_order === (string) $num,
            ) ?? $questions->values()->get(max(0, (int) $num - 1));
            $data = $questionData->first(
                fn (mixed $candidate): bool => is_array($candidate)
                    && (string) ($candidate['number'] ?? '') === (string) $num,
            );
            $isSpeaking = $question?->question_type === 'speaking'
                || strtolower((string) ($data['type'] ?? '')) === 'speaking'
                || strtolower((string) ($data['skill'] ?? '')) === 'speaking'
                || strtolower(trim((string) $correctAnswer)) === 'n/a';

            $questionModels[$num] = $question;

            $isCorrect = ! $isSpeaking
                && AnswerNormalizer::normalize($studentAnswer) === AnswerNormalizer::normalize((string) $correctAnswer);

            $results[$num] = [
                'student_answer' => $studentAnswer,
                'correct_answer' => $correctAnswer,
                'is_correct' => $isSpeaking ? null : $isCorrect,
            ];

            if ($isSpeaking) {
                continue;
            }

            $totalCount++;
            if ($isCorrect) {
                $correctCount++;
            } else {
                $errors[] = [
                    'question' => $question?->question_text ?? ($data['text'] ?? "Pregunta {$num}"),
                    'student_answer' => $studentAnswer,
                ];
            }
        }

        $score = $totalCount > 0 ? round(($correctCount / $totalCount) * 100) : 0;
        $passed = $score >= StudentProgress::PASSING_SCORE;

        $lesson = $listeningLesson->lesson;

        if ($lesson && $totalCount > 0) {
            StudentProgress::prepareLessonsForProgress([$lesson]);
            DB::transaction(function () use ($user, $lesson, $listeningLesson, $correctAnswers, $questionModels, $results, $score, $passed, &$attempt) {
                $attempt = $user->attemptLogs()->create([
                    'lesson_id' => $lesson->lesson_id,
                    'attempt_skill_type' => 'listening',
                    'questionnaire_id' => $listeningLesson->questionnaire?->questionnaire_id,
                    'listening_lesson_id' => $listeningLesson->listening_lesson_id,
                    'attempt_score' => $score,
                    'passed' => $passed,
                ]);

                foreach ($correctAnswers as $num => $_) {
                    $question = $questionModels[$num] ?? null;
                    if (! $question) {
                        continue;
                    }

                    StudentResponse::create([
                        'attempt_id' => $attempt->attempt_id,
                        'question_id' => $question->question_id,
                        'student_answer_text' => $results[$num]['student_answer'],
                        'is_correct' => $results[$num]['is_correct'],
                        'ai_question_feedback' => null,
                    ]);
                }

                if ($passed) {
                    $progress = StudentProgress::masterSkillWhenEligible(
                        $user,
                        $lesson,
                        'listening',
                        StudentProgress::latestPlacementFor($user)?->placement_test_id,
                    );

                    if ($progress?->wasRecentlyCreated) {
                        $this->gamification->awardXp($user, GamificationService::XP_LISTENING_PASS);
                    }
                }

                $this->gamification->recordActivity($user);
            });
        }

        return $this->buildResult($results, $correctCount, $totalCount, $attempt, $errors);
    }

    /**
     * Califica una pregunta individual (MC por option_id, fill_blank por texto normalizado).
     */
    public function gradeQuestion(mixed $question, string $answer): bool
    {
        if ($question->question_type === 'multiple_choice') {
            $correctOption = $question->options->firstWhere('is_correct', true);

            return $correctOption !== null && $correctOption->option_id === $answer;
        }

        return AnswerNormalizer::normalize($answer) === AnswerNormalizer::normalize((string) $question->correct_answer);
    }

    /**
     * Obtiene feedback de IA y actualiza el attempt si Gemini está configurado.
     */
    public function fetchAiFeedback(int $score, int $gradableCount, int $correctCount, array $errors, ?AttemptLog $attempt): ?string
    {
        if (! $this->ai->isConfigured() || ! $attempt) {
            return null;
        }

        try {
            $feedback = $this->ai->generateGeneralFeedback($score, $gradableCount, $correctCount, $errors);
            if (is_string($feedback) && trim($feedback) !== '') {
                $attempt->update(['ai_feedback' => $feedback]);

                return $feedback;
            }
        } catch (Throwable $exception) {
            Log::warning('General activity feedback failed after progress was saved', [
                'attempt_id' => $attempt->attempt_id,
                'exception' => $exception::class,
            ]);
        }

        return null;
    }

    private function buildResult(
        array $results,
        int $correctCount,
        int $gradableCount,
        ?AttemptLog $attempt,
        array $errors,
    ): self {
        $clone = clone $this;
        $score = $gradableCount > 0 ? round(($correctCount / $gradableCount) * 100) : 0;
        $clone->results = $results;
        $clone->correctCount = $correctCount;
        $clone->gradableCount = $gradableCount;
        $clone->score = $score;
        $clone->passed = $score >= StudentProgress::PASSING_SCORE;
        $clone->attempt = $attempt;
        $clone->aiFeedback = $this->fetchAiFeedback($score, $gradableCount, $correctCount, $errors, $attempt);

        return $clone;
    }
}
