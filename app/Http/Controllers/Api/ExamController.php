<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttemptLog;
use App\Models\Question;
use App\Models\StudentResponse;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExamController extends Controller
{
    public function __construct(
        private GeminiService $gemini,
    ) {}

    /**
     * Recibe el examen completo del alumno: respuestas de reading/listening + audios de speaking.
     *
     * Body esperado:
     * {
     *   "lesson_id": "uuid",
     *   "responses": [
     *     { "question_id": "uuid", "answer": "texto o null" },
     *     { "question_id": "uuid", "answer": null, "audio_base64": "...", "mime_type": "audio/webm" }
     *   ]
     * }
     */
    public function submit(Request $request): JsonResponse
    {
        $request->validate([
            'lesson_id' => 'required|exists:lessons,lesson_id',
            'responses' => 'required|array|min:1',
            'responses.*.question_id' => 'required|exists:questions,question_id',
            'responses.*.answer' => 'nullable|string',
            'responses.*.audio_base64' => 'nullable|string',
            'responses.*.mime_type' => 'nullable|string',
        ]);

        $user = auth('api')->user();
        $lessonId = $request->lesson_id;

        $attempt = AttemptLog::create([
            'user_id' => $user->user_id,
            'lesson_id' => $lessonId,
            'attempt_score' => 0,
            'passed' => false,
        ]);

        $speakingResponses = [];
        $allResponses = [];

        DB::beginTransaction();

        try {
            foreach ($request->responses as $resp) {
                $question = Question::with('questionnaire')->find($resp['question_id']);
                $isSpeaking = $question->question_type === 'speaking';

                if ($isSpeaking && !empty($resp['audio_base64'])) {
                    $speakingResponses[] = [
                        'response' => $resp,
                        'question' => $question,
                    ];

                    $studentResponse = StudentResponse::create([
                        'attempt_id' => $attempt->attempt_id,
                        'question_id' => $resp['question_id'],
                        'student_answer_text' => '',
                        'is_correct' => null,
                        'ai_question_feedback' => null,
                    ]);
                } else {
                    $isCorrect = $this->gradeMultipleChoice($question, $resp['answer'] ?? null);

                    $studentResponse = StudentResponse::create([
                        'attempt_id' => $attempt->attempt_id,
                        'question_id' => $resp['question_id'],
                        'student_answer_text' => $resp['answer'] ?? '',
                        'is_correct' => $isCorrect,
                        'ai_question_feedback' => null,
                    ]);
                }

                $allResponses[] = $studentResponse;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating exam responses', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Error al guardar las respuestas.'], 500);
        }

        // Fase 2: Evaluar audios de speaking con Gemini
        foreach ($speakingResponses as $item) {
            $this->evaluateSpeakingResponse(
                $item['response'],
                $item['question'],
                $attempt->attempt_id,
            );
        }

        // Fase 3: Calcular puntaje
        $this->calculateScore($attempt);

        // Fase 4: Feedback general con Gemini
        $this->generateGeneralFeedback($attempt);

        $attempt->refresh();

        return response()->json([
            'attempt_id' => $attempt->attempt_id,
            'score' => $attempt->attempt_score,
            'passed' => $attempt->passed,
            'ai_feedback' => $attempt->ai_feedback,
            'responses' => $allResponses,
        ]);
    }

    /**
     * Obtiene el resultado detallado de un examen.
     */
    public function result(AttemptLog $attempt): JsonResponse
    {
        $user = auth('api')->user();

        if ($attempt->user_id !== $user->user_id) {
            return response()->json(['error' => 'No autorizado.'], 403);
        }

        $responses = StudentResponse::where('attempt_id', $attempt->attempt_id)
            ->with('question')
            ->get();

        return response()->json([
            'attempt' => $attempt,
            'responses' => $responses,
        ]);
    }

    /**
     * Evalua una respuesta de speaking llamando a Gemini.
     */
    private function evaluateSpeakingResponse(
        array $response,
        Question $question,
        string $attemptId,
    ): void {
        $expectedAnswer = $question->correct_answer;

        $result = $this->gemini->evaluateSpeakingAudio(
            audioBase64: $response['audio_base64'],
            mimeType: $response['mime_type'] ?? 'audio/webm',
            questionText: $question->question_text,
            expectedAnswer: $expectedAnswer,
        );

        StudentResponse::where('attempt_id', $attemptId)
            ->where('question_id', $response['question_id'])
            ->update([
                'student_answer_text' => $result['transcription'],
                'is_correct' => $result['is_correct'],
                // Solo guardar feedback detallado si es incorrecto (condicion anti-bloat)
                'ai_question_feedback' => $result['is_correct']
                    ? null
                    : $result['feedback'],
            ]);
    }

    /**
     * Califica una respuesta de opcion multiple.
     */
    private function gradeMultipleChoice(Question $question, ?string $answer): bool
    {
        if ($answer === null) {
            return false;
        }

        $correctOption = $question->options()->where('is_correct', true)->first();

        return $correctOption && $correctOption->option_id === $answer;
    }

    /**
     * Calcula el porcentaje total y si aprobo (>= 90%).
     */
    private function calculateScore(AttemptLog $attempt): void
    {
        $total = StudentResponse::where('attempt_id', $attempt->attempt_id)->count();
        $correct = StudentResponse::where('attempt_id', $attempt->attempt_id)
            ->where('is_correct', true)
            ->count();

        $score = $total > 0 ? round(($correct / $total) * 100, 2) : 0;

        $attempt->update([
            'attempt_score' => $score,
            'passed' => $score >= 90,
        ]);
    }

    /**
     * Genera el feedback general con Gemini basado en los errores del alumno.
     */
    private function generateGeneralFeedback(AttemptLog $attempt): void
    {
        $responses = StudentResponse::where('attempt_id', $attempt->attempt_id)
            ->with('question')
            ->get();

        $total = $responses->count();
        $correct = $responses->where('is_correct', true)->count();

        $errors = $responses
            ->filter(fn($r) => $r->is_correct === false)
            ->map(fn($r) => [
                'question' => $r->question->question_text,
                'student_answer' => $r->student_answer_text,
                'feedback' => $r->ai_question_feedback,
            ])
            ->values()
            ->toArray();

        $feedback = $this->gemini->generateGeneralFeedback(
            score: $attempt->attempt_score,
            total: $total,
            correct: $correct,
            errors: $errors,
        );

        $attempt->update(['ai_feedback' => $feedback]);
    }
}
