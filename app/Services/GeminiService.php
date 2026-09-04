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
        string $cefrLevel = 'A1',
    ): array {
        $cefrLevel = strtoupper(trim($cefrLevel));

        if (!in_array($cefrLevel, ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'], true)) {
            $cefrLevel = 'A1';
        }
        $levelGuidance = $this->speakingGuidanceForLevel($cefrLevel);
        $expectedPart = $expectedAnswer
            ? "\nREFERENCE TEXT THE STUDENT MUST READ:\n\"{$expectedAnswer}\""
            : "\nNo reference text was provided.";

        $prompt = <<<PROMPT
You are an English pronunciation evaluator for university students.

The student's CEFR English level is: {$cefrLevel}.

IMPORTANT:
You must evaluate the student according to CEFR {$cefrLevel} expectations.
Do NOT judge an A1 learner using B1, B2, C1, or native-speaker standards.

{$levelGuidance}

The student is completing a SPEAKING activity.

TASK:
"{$questionText}"

{$expectedPart}

Listen carefully to the student's audio.

Your job is to evaluate the student's spoken English while considering
their CEFR {$cefrLevel} proficiency level.

Evaluate these four criteria from 0 to 100:

1. pronunciation_score
   - How understandable and accurate the student's pronunciation is
     FOR A {$cefrLevel} LEARNER.
   - Consider individual sounds, word pronunciation, stress, and clarity.
   - Never penalize a normal non-native accent by itself.

2. fluency_score
   - Evaluate fluency according to CEFR {$cefrLevel}.
   - Consider pauses, hesitation, rhythm, continuity, and speaking speed.
   - Pauses that are normal for this proficiency level should not be heavily penalized.

3. accuracy_score
   - How accurately the spoken words match the reference text.
   - Penalize omitted, substituted, added, or incorrectly read words.
   - This criterion is based on reading accuracy and is independent of accent.

4. completeness_score
   - How much of the required reference material the student actually read.
   - Do not give a high completeness score for unrelated speech.

Calculate overall_score as:

pronunciation_score * 0.40
+ fluency_score * 0.20
+ accuracy_score * 0.25
+ completeness_score * 0.15

Round overall_score to the nearest integer.

PASSING RULE:
- overall_score >= 90 = approved
- overall_score < 90 = not approved

IMPORTANT:
The 90% threshold represents mastery relative to CEFR {$cefrLevel},
not native-speaker pronunciation.

Examples:
- An A1 student can receive 90+ with slow but clear beginner-level speech.
- An A1 student must NOT be penalized for lacking B2-style connected speech.
- A B2 student should be evaluated with noticeably higher fluency and pronunciation expectations.
- A C1/C2 student should be evaluated more strictly for rhythm, stress and naturalness.

If the student is silent or speaks mostly unrelated content:
- is_correct must be false.
- accuracy_score and completeness_score must be very low.

Feedback:
- Write feedback in Spanish.
- Mention the student's CEFR level.
- Be concise, constructive and specific.
- Point out at most 2 improvements.
- Do not tell an A1 learner to perform skills expected only at advanced levels.

Return ONLY valid JSON:

{
  "transcription": "Exact transcription of what the student said",
  "pronunciation_score": 0,
  "fluency_score": 0,
  "accuracy_score": 0,
  "completeness_score": 0,
  "overall_score": 0,
  "is_correct": false,
  "feedback": "Retroalimentación breve en español."
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
            $errorList .= "\n" . ($i + 1) . ". Question: \"{$error['question']}\"";
            $errorList .= "\n   Student said/wrote: \"{$error['student_answer']}\"";
            if (!empty($error['feedback'])) {
                $errorList .= "\n   AI feedback: \"{$error['feedback']}\"";
            }
        }

        $prompt = <<<PROMPT
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


    private function speakingGuidanceForLevel(string $cefrLevel): string
    {
        return match ($cefrLevel) {
            'A1' => <<<TEXT
CEFR A1 EXPECTATIONS:
- Evaluate as a beginner English learner.
- Speech may be slow and carefully articulated.
- Pauses between phrases are normal and should not be heavily penalized.
- A noticeable non-native accent is completely acceptable.
- Focus mainly on whether common words are understandable.
- Minor stress, rhythm, article, ending, or vowel inaccuracies are acceptable when meaning remains clear.
- Do not expect natural connected speech.
- Fluency means being able to continue reading understandably, even with pauses.
- Give simple and encouraging feedback.
TEXT,

            'A2' => <<<TEXT
CEFR A2 EXPECTATIONS:
- Evaluate as an elementary English learner.
- Some hesitation and pauses are acceptable.
- A non-native accent is completely acceptable.
- Most common words should be clearly understandable.
- Expect somewhat better control of word endings and common sounds than A1.
- Basic sentence stress and rhythm should begin to appear.
- Do not require native-like connected speech.
- Fluency should allow short groups of words to be spoken without excessive interruption.
- Feedback should focus on one or two useful improvements.
TEXT,

            'B1' => <<<TEXT
CEFR B1 EXPECTATIONS:
- Evaluate as an intermediate English learner.
- Speech should generally be clear and understandable.
- Occasional pronunciation mistakes and hesitation are acceptable.
- Expect reasonable control of word stress and common English sounds.
- Expect sentences to be spoken in meaningful groups rather than word by word.
- Fluency should be reasonably continuous, although pauses for difficult words are acceptable.
- Rhythm and intonation should support meaning.
TEXT,

            'B2' => <<<TEXT
CEFR B2 EXPECTATIONS:
- Evaluate as an upper-intermediate English learner.
- Speech should be consistently clear and easy to understand.
- Expect good word stress, sentence stress, rhythm, and intonation.
- Occasional pronunciation mistakes are acceptable if communication is not affected.
- Speech should normally flow in phrases with limited unnatural hesitation.
- Expect some natural linking and connected speech.
- Do not require a native accent.
TEXT,

            'C1' => <<<TEXT
CEFR C1 EXPECTATIONS:
- Evaluate as an advanced English learner.
- Pronunciation should be consistently clear and precise.
- Expect strong control of stress, rhythm, intonation, and connected speech.
- Hesitation should be limited and normally related to complex language.
- Minor accent features are acceptable and must not reduce the score simply for being non-native.
- Speech should sound natural, fluent, and expressive.
TEXT,

            'C2' => <<<TEXT
CEFR C2 EXPECTATIONS:
- Evaluate as a highly proficient English speaker.
- Speech should be effortless, precise, highly intelligible, and natural.
- Expect excellent control of individual sounds, stress, rhythm, intonation, linking, and connected speech.
- Hesitation should be minimal.
- A non-native accent is acceptable when pronunciation remains highly clear and natural.
- Evaluate subtle pronunciation and fluency issues more strictly than at lower CEFR levels.
TEXT,

            default => '',
        };
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
