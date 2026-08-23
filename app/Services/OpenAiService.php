<?php

namespace App\Services;

use App\Contracts\AiProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonException;
use RuntimeException;
use Throwable;
use UnexpectedValueException;

class OpenAiService implements AiProvider
{
    private string $apiKey = '';

    private string $baseUrl = 'https://api.openai.com/v1';

    private string $chatModel = 'gpt-4o-mini';

    private string $transcribeModel = 'gpt-4o-transcribe';

    public function __construct(
        ?string $apiKey = null,
        ?string $baseUrl = null,
        ?string $chatModel = null,
        ?string $transcribeModel = null,
    ) {
        $this->apiKey = $apiKey ?? (string) config('services.openai.api_key');
        $this->baseUrl = rtrim(
            $baseUrl ?? (string) config('services.openai.base_url', 'https://api.openai.com/v1'),
            '/',
        );
        $this->chatModel = trim($chatModel ?? (string) config('services.openai.chat_model', 'gpt-4o-mini'))
            ?: 'gpt-4o-mini';
        $this->transcribeModel = trim(
            $transcribeModel ?? (string) config('services.openai.transcribe_model', 'gpt-4o-transcribe'),
        ) ?: 'gpt-4o-transcribe';
    }

    /**
     * Indica si hay una API key configurada para usar OpenAI.
     */
    public function isConfigured(): bool
    {
        return trim($this->apiKey) !== '';
    }

    /**
     * Evalua un audio de speaking: transcribe con gpt-4o-transcribe y evalúa con el modelo de chat.
     *
     * @return array{transcription: string, is_correct: bool, feedback: string}
     */
    public function evaluateSpeakingAudio(
        string $audioBase64,
        string $mimeType,
        string $questionText,
        ?string $expectedAnswer = null,
    ): array {
        $transcription = $this->transcribe($audioBase64, $mimeType);
        $expectedPart = $expectedAnswer
            ? "\n- Expected answer (model): \"{$expectedAnswer}\""
            : '';

        $prompt = <<<PROMPT
You are an English teacher evaluating a student's spoken response.

Question asked to the student: "{$questionText}"
{$expectedPart}

Student's transcription: "{$transcription}"

Respond with ONLY a valid JSON object (no markdown, no code fences):
{
  "is_correct": true/false,
  "feedback": "One short sentence (max 30 words) with specific correction or praise"
}

Rules for is_correct:
- true if the student communicated the correct idea, even with minor grammar/vocabulary errors
- false if the meaning is completely wrong, the student stayed silent, or spoke a different language

Keep feedback constructive and specific (e.g. "Remember to use 'am' before 'fine': 'I am fine'").
PROMPT;

        $text = $this->chatCompletion([
            ['role' => 'user', 'content' => $prompt],
        ], temperature: 0.2);

        $text = trim($text);
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*/', '', $text);
            $text = preg_replace('/\s*```$/', '', $text);
        }

        try {
            $decoded = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            Log::warning('OpenAI returned invalid JSON for speaking evaluation', ['raw' => $text]);

            throw new UnexpectedValueException(
                'OpenAI returned invalid JSON for speaking evaluation.',
                0,
                $exception,
            );
        }

        if (
            ! is_array($decoded)
            || ! is_bool($decoded['is_correct'] ?? null)
            || ! is_string($decoded['feedback'] ?? null)
        ) {
            throw new UnexpectedValueException('OpenAI returned an incomplete speaking evaluation.');
        }

        return [
            'transcription' => $transcription,
            'is_correct' => $decoded['is_correct'],
            'feedback' => $decoded['feedback'],
        ];
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
        if (! $this->isConfigured()) {
            return null;
        }

        $errorList = '';
        foreach ($errors as $i => $error) {
            $errorList .= "\n".($i + 1).". Question: \"{$error['question']}\"";
            $errorList .= "\n   Student said/wrote: \"{$error['student_answer']}\"";
            if (! empty($error['feedback'])) {
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

        return $this->chatCompletion([
            ['role' => 'user', 'content' => $prompt],
        ], temperature: 0.7, maxTokens: 300);
    }

    /**
     * Genera la respuesta del tutor de chat IA.
     *
     * @param array $messages Historial [{role: 'user'|'assistant', content: string}]
     * @return string|null null si no hay API key configurada
     */
    public function chatReply(array $messages): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $history = [];
        foreach (array_slice($messages, -12) as $message) {
            $history[] = [
                'role' => $message['role'] === 'user' ? 'user' : 'assistant',
                'content' => $message['content'] ?? '',
            ];
        }

        $prompt = <<<PROMPT
You are "Agente Inglés", the English tutor of UTBIS university students.
- Help students practice and understand English (grammar, vocabulary, listening, speaking).
- Always answer in Spanish, unless the student writes in English (then answer in English).
- Use the student's CEFR level (A1-C2) to adapt complexity. If unknown, ask them their level first.
- Correct mistakes kindly and give 1 short example for every rule you explain.
- Keep answers under 150 words. No emojis.
PROMPT;

        return $this->chatCompletion(array_merge(
            [['role' => 'system', 'content' => $prompt]],
            $history,
        ));
    }

    /**
     * Transcribe un audio en base64 usando el modelo de transcripcion de OpenAI.
     */
    private function transcribe(string $audioBase64, string $mimeType): string
    {
        $audioBytes = base64_decode($audioBase64, true);

        if ($audioBytes === false || $audioBytes === '') {
            throw new UnexpectedValueException('Invalid base64 audio.');
        }

        $response = null;

        try {
            $response = Http::connectTimeout(5)
                ->timeout(30)
                ->withToken($this->apiKey)
                ->attach('file', $audioBytes, 'audio.'.$this->extensionForMime($mimeType))
                ->post("{$this->baseUrl}/audio/transcriptions", [
                    'model' => $this->transcribeModel,
                    'response_format' => 'json',
                ]);
            $response->throw();
        } catch (Throwable $exception) {
            Log::error('OpenAI transcription error', [
                'status' => $response?->status(),
                'body' => $response?->body(),
                'exception' => $exception::class,
            ]);

            throw new RuntimeException('OpenAI transcription failed.', 0, $exception);
        }

        $decoded = $response->json();
        $text = $decoded['text'] ?? null;

        if (! is_string($text) || trim($text) === '') {
            throw new UnexpectedValueException('OpenAI returned no transcription.');
        }

        return trim($text);
    }

    /**
     * Realiza una llamada al endpoint de chat completions.
     */
    private function chatCompletion(
        array $messages,
        float $temperature = 0.7,
        int $maxTokens = 500,
    ): string {
        $response = null;

        try {
            $response = Http::connectTimeout(5)
                ->timeout(30)
                ->withToken($this->apiKey)
                ->acceptJson()
                ->post("{$this->baseUrl}/chat/completions", [
                    'model' => $this->chatModel,
                    'messages' => $messages,
                    'temperature' => $temperature,
                    'max_tokens' => $maxTokens,
                ]);
            $response->throw();
        } catch (Throwable $exception) {
            Log::error('OpenAI API error', [
                'status' => $response?->status(),
                'body' => $response?->body(),
                'exception' => $exception::class,
            ]);

            throw new RuntimeException('OpenAI request failed.', 0, $exception);
        }

        $decoded = $response->json();
        $text = $decoded['choices'][0]['message']['content'] ?? null;

        if (! is_string($text) || trim($text) === '') {
            throw new UnexpectedValueException('OpenAI returned no text.');
        }

        return trim($text);
    }

    private function extensionForMime(string $mimeType): string
    {
        return match (strtolower($mimeType)) {
            'audio/mpeg' => 'mp3',
            'audio/mp4' => 'mp4',
            'audio/ogg' => 'ogg',
            'audio/wav' => 'wav',
            default => 'webm',
        };
    }
}