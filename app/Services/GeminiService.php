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
            ? "\n- Expected answer (model): \"{$expectedAnswer}\""
            : '';

        $prompt =<<<PROMPT
You are an English teacher evaluating a student's spoken response.

Question asked to the student: "{$questionText}"
{$expectedPart}

Analyze the audio and respond with ONLY a valid JSON object (no markdown, no code fences):
{
  "transcription": "What the student actually said",
  "is_correct": true/false,
  "feedback": "One short sentence (max 30 words) with specific correction or praise"
}

Rules for is_correct:
- true if the student communicated the correct idea, even with minor grammar/vocabulary errors
- false if the meaning is completely wrong, the student stayed silent, or spoke a different language

Keep feedback constructive and specific (e.g. "Remember to use 'am' before 'fine': 'I am fine'").
PROMPT;

        $response = $this->callGemini([
            'contents' => [[
                'parts' => [
                    ['text' => $prompt],
                    [
                        'inline_data' => [
                            'mime_type' => $mimeType,
                            'data' => $audioBase64,
                        ],
                    ],
                ],
            ]],
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
            $response = Http::connectTimeout(5)
                ->timeout(20)
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
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*/', '', $text);
            $text = preg_replace('/\s*```$/', '', $text);
        }

        try {
            $decoded = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            Log::warning('Gemini returned invalid JSON for speaking evaluation', ['raw' => $text]);

            throw new UnexpectedValueException(
                'Gemini returned invalid JSON for speaking evaluation.',
                0,
                $exception,
            );
        }

        if (
            ! is_array($decoded)
            || ! is_string($decoded['transcription'] ?? null)
            || ! is_bool($decoded['is_correct'] ?? null)
            || ! is_string($decoded['feedback'] ?? null)
        ) {
            throw new UnexpectedValueException('Gemini returned an incomplete speaking evaluation.');
        }

        return [
            'transcription' => $decoded['transcription'],
            'is_correct' => $decoded['is_correct'],
            'feedback' => $decoded['feedback'],
        ];
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
