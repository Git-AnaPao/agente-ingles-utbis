<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private string $apiKey;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
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
     * @return string Párrafo de feedback general
     */
    public function generateGeneralFeedback(
        float $score,
        int $total,
        int $correct,
        array $errors,
    ): string {
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

        return $response['candidates'][0]['content']['parts'][0]['text']
            ?? 'No feedback generated.';
    }

    /**
     * Realiza la llamada HTTP a la API de Gemini.
     */
    private function callGemini(array $payload): array
    {
        $url = "{$this->baseUrl}/models/gemini-2.0-flash:generateContent?key={$this->apiKey}";

        $response = Http::timeout(60)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url, $payload);

        if ($response->failed()) {
            Log::error('Gemini API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode([
                            'transcription' => '',
                            'is_correct' => null,
                            'feedback' => 'Error al comunicarse con la IA. La pregunta no fue evaluada.',
                        ]),
                    ]]],
                ]],
            ];
        }

        return $response->json();
    }

    /**
     * Parsea la respuesta de evaluateSpeakingAudio.
     */
    private function parseSpeakingResponse(array $response): array
    {
        $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? '{}';

        $text = trim($text);
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*/', '', $text);
            $text = preg_replace('/\s*```$/', '', $text);
        }

        $decoded = json_decode($text, true);

        if (!is_array($decoded)) {
            Log::warning('Gemini returned invalid JSON for speaking evaluation', ['raw' => $text]);

            return [
                'transcription' => '',
                'is_correct' => null,
                'feedback' => 'Error al procesar la respuesta de la IA.',
            ];
        }

        return [
            'transcription' => $decoded['transcription'] ?? '',
            'is_correct' => $decoded['is_correct'] ?? null,
            'feedback' => $decoded['feedback'] ?? '',
        ];
    }
}
