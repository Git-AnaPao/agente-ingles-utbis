<?php

namespace Tests\Unit;

use App\Services\GeminiService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;
use UnexpectedValueException;

class GeminiServiceTest extends TestCase
{
    public function test_speaking_uses_the_configured_model_and_requires_valid_json(): void
    {
        config([
            'services.gemini.api_key' => 'test-key',
            'services.gemini.model' => 'gemini-2.5-flash',
        ]);
        Http::fake([
            '*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode([
                            'transcription' => 'Hello.',
                            'is_correct' => true,
                            'feedback' => 'Clear pronunciation.',
                        ], JSON_THROW_ON_ERROR),
                    ]]],
                ]],
            ]),
        ]);

        $result = (new GeminiService())->evaluateSpeakingAudio(
            audioBase64: base64_encode('audio'),
            mimeType: 'audio/webm',
            questionText: 'Say hello.',
        );

        $this->assertSame('Hello.', $result['transcription']);
        $this->assertTrue($result['is_correct']);
        Http::assertSent(fn (Request $request): bool => str_contains(
            $request->url(),
            '/models/gemini-2.5-flash:generateContent',
        ));
    }

    public function test_http_errors_throw_instead_of_returning_synthetic_content(): void
    {
        config([
            'services.gemini.api_key' => 'test-key',
            'services.gemini.model' => 'gemini-2.5-flash',
        ]);
        Http::fake(['*' => Http::response(['error' => 'quota'], 429)]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Gemini request failed.');

        (new GeminiService())->chatReply([
            ['role' => 'user', 'content' => 'Hello'],
        ]);
    }

    public function test_invalid_speaking_json_throws_instead_of_becoming_an_unevaluated_result(): void
    {
        config([
            'services.gemini.api_key' => 'test-key',
            'services.gemini.model' => 'gemini-2.5-flash',
        ]);
        Http::fake([
            '*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'not-json']]],
                ]],
            ]),
        ]);

        $this->expectException(UnexpectedValueException::class);

        (new GeminiService())->evaluateSpeakingAudio(
            audioBase64: base64_encode('audio'),
            mimeType: 'audio/webm',
            questionText: 'Say hello.',
        );
    }
}
