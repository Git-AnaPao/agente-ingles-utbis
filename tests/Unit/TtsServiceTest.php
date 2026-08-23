<?php

namespace Tests\Unit;

use App\Services\TtsService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use phpseclib3\Crypt\RSA;
use Tests\TestCase;

class TtsServiceTest extends TestCase
{
    /** @var list<string> */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    public function test_it_reads_google_service_account_path_and_synthesizes_audio(): void
    {
        $privateKey = RSA::createKey(2048)->toString('PKCS8');

        $relativePath = 'storage/app/tts-service-account-'.bin2hex(random_bytes(8)).'.json';
        $absolutePath = base_path($relativePath);
        $this->temporaryFiles[] = $absolutePath;

        file_put_contents($absolutePath, json_encode([
            'client_email' => 'tts-test@example.iam.gserviceaccount.com',
            'private_key' => $privateKey,
        ], JSON_THROW_ON_ERROR));

        config()->set('services.google.service_account_path', $relativePath);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ]),
            'https://texttospeech.googleapis.com/v1/text:synthesize' => Http::response([
                'audioContent' => base64_encode('audio-content'),
            ]),
        ]);

        $service = new TtsService;

        $this->assertTrue($service->isConfigured());
        $this->assertSame('audio-content', $service->synthesize('Read this text.'));

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://texttospeech.googleapis.com/v1/text:synthesize'
                && $request->hasHeader('Authorization', 'Bearer test-access-token')
                && $request['input']['text'] === 'Read this text.';
        });
    }

    public function test_it_rejects_malformed_service_account_files(): void
    {
        $relativePath = 'storage/app/tts-service-account-'.bin2hex(random_bytes(8)).'.json';
        $absolutePath = base_path($relativePath);
        $this->temporaryFiles[] = $absolutePath;
        file_put_contents($absolutePath, '{not-json');

        config()->set('services.google.service_account_path', $relativePath);

        $this->assertFalse((new TtsService)->isConfigured());
    }
}
