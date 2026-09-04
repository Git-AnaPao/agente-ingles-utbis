<?php

namespace App\Services;

use App\Contracts\AiProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonException;
use RuntimeException;
use Throwable;
use UnexpectedValueException;

class GeminiService implements AiProvider
{
    private string $apiKey = '';
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';
    private string $model = 'gemini-3.6-flash';

    public function __construct()
    {
        $this->apiKey = (string) config('services.gemini.api_key');
        $this->model = trim((string) config('services.gemini.model', 'gemini-3.6-flash'))
            ?: 'gemini-3.6-flash';
    }

    /**
     * Indica si hay una API key configurada para usar Gemini.
     */
    public function isConfigured(): bool
    {
        return trim($this->apiKey) !== '';
    }

    /**
     * Evalua un audio de speaking: transcribe + determina si es correcto + feedback corto.
     *
     * @param string $audioBase64  Audio codificado en base64 (sin el prefijo data:...)
     * @param string $mimeType     MIME type del audio (audio/webm, audio/mpeg, etc.)
     * @param string $questionText La pregunta que se le hizo al alumno
     * @param string|null $expectedAnswer Respuesta esperada o modelo (opcional)
     * @return array{transcription: string, is_correct: bool, feedback: string}
     */
   public function evaluateSpeakingAudio(
    string $audioBase64,
    string $mimeType,
    string $questionText,
    ?string $expectedAnswer = null,
): array {
    $expectedPart = $expectedAnswer
        ? "\nREFERENCE TEXT THE STUDENT MUST READ:\n\"{$expectedAnswer}\""
        : "\nNo reference text was provided.";

    $prompt = <<<PROMPT
You are an English pronunciation evaluator for university students.

The student is completing a SPEAKING activity.

TASK:
"{$questionText}"

{$expectedPart}

Listen carefully to the student's audio.

Your job is to evaluate the student's spoken English, especially when reading the provided reference text aloud.

Evaluate these four criteria from 0 to 100:

1. pronunciation_score
   - How understandable and accurate the student's English pronunciation is.
   - Consider individual sounds, word pronunciation, stress, and clarity.
   - Do not penalize a normal non-native accent if the words remain clearly understandable.

2. fluency_score
   - How smoothly and naturally the student speaks.
   - Consider excessive pauses, hesitation, rhythm, and continuity.

3. accuracy_score
   - How accurately the spoken words match the reference text.
   - Penalize omitted, substituted, added, or incorrectly read words.

4. completeness_score
   - How much of the required reference text the student actually read.
   - If the student says unrelated content, introduces themselves instead of reading, or reads only a small fragment, this score must be low.

Calculate overall_score as:

pronunciation_score * 0.40
+ fluency_score * 0.20
+ accuracy_score * 0.25
+ completeness_score * 0.15

Round overall_score to the nearest integer.

IMPORTANT PASSING RULE:
- is_correct = true only when overall_score >= 90.
- is_correct = false when overall_score < 90.

Special rules:
- If the student is silent, is_correct must be false.
- If the student speaks mostly in another language, is_correct must be false.
- If the student does not read the requested reference text, accuracy_score and completeness_score must be very low.
- Do not give a high score merely because the student's English is understandable if they did not perform the requested task.
- Be appropriate for an English learner. A normal foreign accent by itself is NOT an error.
- Feedback must identify the most important pronunciation, fluency, accuracy, or completeness issue.
- Feedback must be in Spanish.
- Keep feedback concise, supportive, and useful.

Return ONLY valid JSON.

Use exactly this structure:

{
  "transcription": "Exact transcription of what the student said",
  "pronunciation_score": 0,
  "fluency_score": 0,
  "accuracy_score": 0,
  "completeness_score": 0,
  "overall_score": 0,
  "is_correct": false,
  "feedback": "Retroalimentación breve y específica en español."
}
PROMPT;

    $response = $this->callGemini([
        'contents' => [[
            'parts' => [
                [
                    'text' => $prompt,
                ],
                [
                    'inline_data' => [
                        'mime_type' => $mimeType,
                        'data' => $audioBase64,
                    ],
                ],
            ],
        ]],

        'generationConfig' => [
            'temperature' => 0.1,
            'responseMimeType' => 'application/json',
        ],
    ]);

    return $this->parseSpeakingResponse($response);
}
    /**
     * Genera el feedback general de una leccion basado en todos los errores del alumno.
     *
     * @param float $score Porcentaje de acierto (0-100)
     * @param int $total Total de preguntas
     * @param int $correct Correctas
     * @param array $errors Lista de errores [{question, student_answer, feedback}]
     * @return string|null Párrafo de feedback general (null sin API key)
     */
    public function generateGeneralFeedback(
        float $score,
        int $total,
        int $correct,
        array $errors,
    ): ?string {
        if (!$this->isConfigured()) {
            return null;
        }
        $errorList = '';
        foreach ($errors as $i => $error) {
            $errorList .= "\n".($i + 1).". Question: \"{$error['question']}\"";
            $errorList .= "\n   Student said/wrote: \"{$error['student_answer']}\"";
            if (!empty($error['feedback'])) {
                $errorList .= "\n   AI feedback: \"{$error['feedback']}\"";
            }
        }

        $prompt =<<<PROMPT
You are a friendly and motivating English teacher.

A student just completed a lesson. Here are the results:
- Score: {$score}% ({$correct}/{$total} correct)
{$errorList}

Write a single paragraph (max 80 words) that:
1. Congratulates them on their score (be enthusiastic if >= 80%, encouraging if < 80%)
2. Mentions 1-2 specific things they need to improve based on the errors
3. Ends with a motivational closing line

Write in English. Be warm, specific, and encouraging. Do not use emojis.
PROMPT;

        $response = $this->callGemini([
            'contents' => [[
                'parts' => [
                    ['text' => $prompt],
                ],
            ]],
        ]);

        return $this->responseText($response, 'general feedback');
    }

    /**
     * Genera la respuesta del tutor de chat IA.
     *
     * @param array $messages Historial [{role: 'user'|'assistant', content: string}]
     * @return string|null null si no hay API key configurada
     */
    public function chatReply(array $messages): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $contents = [];
        foreach (array_slice($messages, -12) as $message) {
            $contents[] = [
                'role' => $message['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $message['content'] ?? '']],
            ];
        }

        $response = $this->callGemini([
            'system_instruction' => [
                'parts' => [[
                    'text' => <<<PROMPT
You are "Agente Inglés", the English tutor of UTBIS university students.
- Help students practice and understand English (grammar, vocabulary, listening, speaking).
- Always answer in Spanish, unless the student writes in English (then answer in English).
- Use the student's CEFR level (A1-C2) to adapt complexity. If unknown, ask them their level first.
- Correct mistakes kindly and give 1 short example for every rule you explain.
- Keep answers under 150 words. No emojis.
PROMPT,
                ]],
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 500,
            ],
        ]);

        return $this->responseText($response, 'chat reply');
    }

    /**
     * Realiza la llamada HTTP a la API de Gemini.
     */
    private function callGemini(array $payload): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Gemini API key is not configured.');
        }

        $url = "{$this->baseUrl}/models/{$this->model}:generateContent";
        $response = null;

        try {
            $response = Http::connectTimeout(15)
                ->timeout(180)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $this->apiKey,
                ])
                ->post($url, $payload);
            $response->throw();
        } catch (Throwable $exception) {
            Log::error('Gemini API error', [
                'status' => $response?->status(),
                'body' => $response?->body(),
                'exception' => $exception::class,
            ]);

            throw new RuntimeException('Gemini request failed.', 0, $exception);
        }

        $decoded = $response->json();
        if (! is_array($decoded)) {
            throw new UnexpectedValueException('Gemini returned a non-JSON response.');
        }

        return $decoded;
    }

    /**
     * Parsea la respuesta de evaluateSpeakingAudio.
     */
   private function parseSpeakingResponse(array $response): array
{
    $text = $this->responseText($response, 'speaking evaluation');
    $text = trim($text);

    // Por seguridad, quitar bloques ```json ... ```
    if (str_starts_with($text, '```')) {
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);
    }

    try {
        $decoded = json_decode(
            $text,
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    } catch (JsonException $exception) {
        Log::warning(
            'Gemini returned invalid JSON for speaking evaluation',
            [
                'raw' => $text,
            ]
        );

        throw new UnexpectedValueException(
            'Gemini returned invalid JSON for speaking evaluation.',
            0,
            $exception,
        );
    }

    if (!is_array($decoded)) {
        throw new UnexpectedValueException(
            'Gemini returned an invalid speaking evaluation.'
        );
    }

    if (
        !is_string($decoded['transcription'] ?? null)
        || !is_string($decoded['feedback'] ?? null)
    ) {
        throw new UnexpectedValueException(
            'Gemini returned an incomplete speaking evaluation.'
        );
    }

    $pronunciationScore = $this->normalizeScore(
        $decoded['pronunciation_score'] ?? null,
        'pronunciation_score'
    );

    $fluencyScore = $this->normalizeScore(
        $decoded['fluency_score'] ?? null,
        'fluency_score'
    );

    $accuracyScore = $this->normalizeScore(
        $decoded['accuracy_score'] ?? null,
        'accuracy_score'
    );

    $completenessScore = $this->normalizeScore(
        $decoded['completeness_score'] ?? null,
        'completeness_score'
    );

    /*
     * Calculamos nosotros el resultado final.
     *
     * No confiamos únicamente en el overall_score enviado
     * por Gemini para que la regla del sistema sea determinista.
     */
    $overallScore = (int) round(
        ($pronunciationScore * 0.40)
        + ($fluencyScore * 0.20)
        + ($accuracyScore * 0.25)
        + ($completenessScore * 0.15)
    );

    /*
     * La regla de aprobación pertenece a Laravel,
     * no a Gemini.
     */
    $isCorrect = $overallScore >= 90;

    return [
        'transcription' => trim($decoded['transcription']),

        'pronunciation_score' => $pronunciationScore,

        'fluency_score' => $fluencyScore,

        'accuracy_score' => $accuracyScore,

        'completeness_score' => $completenessScore,

        'overall_score' => $overallScore,

        'is_correct' => $isCorrect,

        'feedback' => trim($decoded['feedback']),
    ];
}
private function normalizeScore(mixed $value, string $field): int
{
    if (!is_numeric($value)) {
        throw new UnexpectedValueException(
            "Gemini returned an invalid {$field}."
        );
    }

    $score = (int) round((float) $value);

    return max(0, min(100, $score));
}


    private function responseText(array $response, string $purpose): string
    {
        $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (! is_string($text) || trim($text) === '') {
            throw new UnexpectedValueException("Gemini returned no text for {$purpose}.");
        }

        return trim($text);
    }
}
