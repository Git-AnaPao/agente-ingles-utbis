<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonException;

class TtsService
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const TTS_URL = 'https://texttospeech.googleapis.com/v1/text:synthesize';

    private static ?string $cachedToken = null;

    private static int $tokenExpiresAt = 0;

    private static ?string $cachedCredentialId = null;

    /** @var array{client_email: string, private_key: string}|null */
    private ?array $loadedCredentials = null;

    private bool $credentialsLoaded = false;

    /**
     * Indica si hay credenciales de service account configuradas.
     */
    public function isConfigured(): bool
    {
        return $this->credentials() !== null;
    }

    /**
     * Sintetiza texto a audio (MP3) usando Google Cloud Text-to-Speech.
     *
     * @param  string  $text  Texto a leer (máx ~4500 chars)
     * @param  string  $voice  Voz Neural2 (ej: en-US-Neural2-C)
     * @param  string|null  $languageCode  Idioma (ej: en-US)
     * @return string|null Contenido binario del audio, null si falla
     */
    public function synthesize(string $text, string $voice = 'en-US-Neural2-C', ?string $languageCode = null): ?string
    {
        $text = trim($text);

        if ($text === '' || ! $this->isConfigured()) {
            return null;
        }

        $token = $this->accessToken();
        if ($token === null) {
            return null;
        }

        $payload = [
            'input' => ['text' => mb_substr($text, 0, 4500)],
            'voice' => [
                'languageCode' => $languageCode ?? 'en-US',
                'name' => $voice,
            ],
            'audioConfig' => [
                'audioEncoding' => 'MP3',
                'speakingRate' => 0.95,
                'pitch' => 0,
            ],
        ];

        try {
            $response = Http::connectTimeout(10)
                ->timeout(60)
                ->withToken($token)
                ->post(self::TTS_URL, $payload);
        } catch (ConnectionException $exception) {
            Log::error('Google TTS connection error', ['message' => $exception->getMessage()]);

            return null;
        }

        if ($response->failed()) {
            Log::error('Google TTS error', [
                'status' => $response->status(),
                'error' => $response->json('error.message') ?? $response->json('error'),
            ]);

            return null;
        }

        $audioBase64 = $response->json('audioContent');

        if (! is_string($audioBase64) || $audioBase64 === '') {
            Log::error('Google TTS response did not contain audio data');

            return null;
        }

        $audio = base64_decode($audioBase64, true);

        return $audio === false ? null : $audio;
    }

    /**
     * Obtiene (y cachea) un access token a partir del service account JSON.
     */
    private function accessToken(): ?string
    {
        $credentials = $this->credentials();

        if ($credentials === null) {
            return null;
        }

        $credentialId = hash('sha256', $credentials['client_email']."\0".$credentials['private_key']);

        if (self::$cachedToken !== null
            && self::$cachedCredentialId === $credentialId
            && time() < self::$tokenExpiresAt) {
            return self::$cachedToken;
        }

        $now = time();

        try {
            $header = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
            $claims = $this->base64Url(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/cloud-platform',
                'aud' => self::TOKEN_URL,
                'iat' => $now,
                'exp' => $now + 3600,
            ], JSON_THROW_ON_ERROR));
        } catch (JsonException $exception) {
            Log::error('TTS: unable to encode service account assertion', ['message' => $exception->getMessage()]);

            return null;
        }

        $unsigned = "{$header}.{$claims}";
        $signature = '';
        $privateKey = openssl_pkey_get_private($credentials['private_key']);

        if ($privateKey === false
            || ! openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            Log::error('TTS: unable to sign service account assertion');

            return null;
        }

        $jwt = "{$unsigned}.".$this->base64Url($signature);

        try {
            $response = Http::asForm()
                ->connectTimeout(10)
                ->timeout(30)
                ->post(self::TOKEN_URL, [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);
        } catch (ConnectionException $exception) {
            Log::error('TTS token connection error', ['message' => $exception->getMessage()]);

            return null;
        }

        if ($response->failed()) {
            Log::error('TTS: service account assertion exchange failed', [
                'status' => $response->status(),
                'error' => $response->json('error_description') ?? $response->json('error'),
            ]);

            return null;
        }

        $token = $response->json('access_token');

        if (! is_string($token) || $token === '') {
            Log::error('TTS token response did not contain an access token');

            return null;
        }

        self::$cachedToken = $token;
        self::$cachedCredentialId = $credentialId;
        self::$tokenExpiresAt = $now + max(60, (int) $response->json('expires_in', 3600)) - 60;

        return self::$cachedToken;
    }

    private function credentials(): ?array
    {
        if ($this->credentialsLoaded) {
            return $this->loadedCredentials;
        }

        $this->credentialsLoaded = true;
        $configuredPath = config('services.google.service_account_path');

        if (! is_string($configuredPath) || trim($configuredPath) === '') {
            return null;
        }

        $path = $this->absolutePath(trim($configuredPath));

        if (! is_file($path) || ! is_readable($path)) {
            Log::warning('TTS service account file is missing or unreadable', ['path' => $path]);

            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            Log::warning('TTS service account file could not be read', ['path' => $path]);

            return null;
        }

        try {
            $credentials = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            Log::warning('TTS service account file contains invalid JSON', [
                'path' => $path,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }

        if (! is_array($credentials)
            || ! is_string($credentials['client_email'] ?? null)
            || trim($credentials['client_email']) === ''
            || ! is_string($credentials['private_key'] ?? null)
            || openssl_pkey_get_private($credentials['private_key']) === false) {
            Log::warning('TTS service account file is missing valid client_email or private_key fields', [
                'path' => $path,
            ]);

            return null;
        }

        $this->loadedCredentials = [
            'client_email' => trim($credentials['client_email']),
            'private_key' => $credentials['private_key'],
        ];

        return $this->loadedCredentials;
    }

    private function absolutePath(string $path): string
    {
        if (str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('/^[A-Z]:[\\\\\/]/i', $path) === 1) {
            return $path;
        }

        return base_path($path);
    }

    private function base64Url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
