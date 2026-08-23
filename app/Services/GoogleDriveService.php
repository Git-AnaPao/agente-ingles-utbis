<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    private ?string $apiKey;

    private ?string $serviceAccountPath;

    private string $baseUrl = 'https://www.googleapis.com/drive/v3';

    private string $uploadUrl = 'https://www.googleapis.com/upload/drive/v3';

    public function __construct()
    {
        $this->apiKey = config('services.google.drive_api_key');
        $this->serviceAccountPath = config('services.google.service_account_path');
    }

    /**
     * Prepara un request HTTP con la autenticación correcta.
     * Usa Bearer token para service account, o key query param para API key.
     */
    private function withAuth($request, array $queryParams = []): PendingRequest
    {
        $token = $this->getAccessToken();

        if ($this->serviceAccountPath && file_exists($this->serviceAccountPath) && $token) {
            return $request->withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->withQueryParameters($queryParams);
        }

        if ($this->apiKey) {
            return $request->withQueryParameters(array_merge($queryParams, [
                'key' => $this->apiKey,
            ]));
        }

        return $request->withQueryParameters($queryParams);
    }

    /**
     * Obtiene el contenido de un archivo Excel desde Google Drive.
     *
     * @param  string  $fileId  ID del archivo en Google Drive
     * @return array Datos del archivo
     */
    public function getFile(string $fileId): array
    {
        $response = $this->withAuth(Http::timeout(60))->get("{$this->baseUrl}/files/{$fileId}", [
            'alt' => 'media',
        ]);

        if ($response->failed()) {
            Log::error('Google Drive API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [];
        }

        return $response->json();
    }

    /**
     * Obtiene información de un archivo.
     */
    public function getFileInfo(string $fileId): array
    {
        $response = $this->withAuth(Http::timeout(60))->get("{$this->baseUrl}/files/{$fileId}", [
            'fields' => 'id,name,mimeType,size,createdTime,modifiedTime',
        ]);

        if ($response->failed()) {
            return [];
        }

        return $response->json();
    }

    /**
     * Lista archivos en una carpeta.
     */
    public function listFiles(string $folderId, string $mimeType = ''): array
    {
        $query = "'{$folderId}' in parents and trashed = false";
        if ($mimeType) {
            $query .= " and mimeType = '{$mimeType}'";
        }

        $response = $this->withAuth(Http::timeout(60))->get("{$this->baseUrl}/files", [
            'q' => $query,
            'fields' => 'files(id,name,mimeType,size)',
            'orderBy' => 'name',
            'pageSize' => 100,
        ]);

        if ($response->failed()) {
            return [];
        }

        return $response->json()['files'] ?? [];
    }

    /**
     * Lista todas las carpetas en una carpeta padre.
     */
    public function listFolders(string $folderId): array
    {
        return $this->listFiles($folderId, 'application/vnd.google-apps.folder');
    }

    /**
     * Lista archivos de audio en una carpeta.
     */
    public function listAudioFiles(string $folderId): array
    {
        $audioMimeTypes = [
            'audio/mpeg',
            'audio/mp3',
            'audio/wav',
            'audio/ogg',
            'audio/webm',
            'audio/mp4',
            'audio/x-m4a',
            'video/mpeg',
        ];

        $files = [];
        foreach ($audioMimeTypes as $mimeType) {
            $files = array_merge($files, $this->listFiles($folderId, $mimeType));
        }

        return $files;
    }

    /**
     * Obtiene una URL temporal para descargar un archivo.
     */
    public function getDownloadUrl(string $fileId): string
    {
        if ($this->serviceAccountPath && file_exists($this->serviceAccountPath)) {
            return "{$this->baseUrl}/files/{$fileId}?alt=media";
        }

        return "{$this->baseUrl}/files/{$fileId}?alt=media&key={$this->apiKey}";
    }

    /**
     * Obtiene una URL para previsualizar un archivo.
     */
    public function getPreviewUrl(string $fileId): string
    {
        return "https://drive.google.com/uc?export=view&id={$fileId}";
    }

    /**
     * Descarga un archivo en modo streaming (útil para servir audio/video).
     *
     * @return Response
     */
    public function streamFile(string $fileId, ?string $range = null)
    {
        $token = $this->getAccessToken();
        if ($this->serviceAccountPath && file_exists($this->serviceAccountPath) && $token) {
            $request = Http::timeout(300)->withOptions(['stream' => true]);
            if ($range !== null) {
                $request = $request->withHeaders(['Range' => $range]);
            }
            $request = $request->withHeaders(['Authorization' => 'Bearer '.$token]);
            $response = $request->withQueryParameters(['alt' => 'media'])->get("{$this->baseUrl}/files/{$fileId}");
            if (in_array($response->status(), [200, 206], true)) {
                return $response;
            }
        }

        $request = Http::timeout(300)->withOptions(['stream' => true])
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            ]);

        if ($range !== null) {
            $request = $request->withHeaders(['Range' => $range]);
        }

        if ($this->apiKey) {
            $apiResponse = (clone $request)->withQueryParameters(['alt' => 'media', 'key' => $this->apiKey])->get("{$this->baseUrl}/files/{$fileId}");
            if (in_array($apiResponse->status(), [200, 206], true)) {
                return $apiResponse;
            }
        }

        return $request->get("https://drive.google.com/uc?export=download&id={$fileId}");
    }

    /**
     * Descarga el contenido de un archivo.
     * Para Google Docs/Sheets usa export; para binarios usa alt=media.
     */
    public function downloadFile(string $fileId): ?string
    {
        $mimeType = $this->getExportMimeType($fileId);

        if ($mimeType) {
            $response = $this->withAuth(Http::timeout(120))->get(
                "{$this->baseUrl}/files/{$fileId}/export",
                ['mimeType' => $mimeType]
            );
        } else {
            $response = $this->withAuth(Http::timeout(120))->get(
                "{$this->baseUrl}/files/{$fileId}",
                ['alt' => 'media']
            );
        }

        if ($response->failed()) {
            Log::error('Google Drive download error', [
                'file_id' => $fileId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($mimeType === null) {
                Log::info('Retrying download with export as spreadsheet', ['file_id' => $fileId]);
                $retryResponse = $this->withAuth(Http::timeout(120))->get(
                    "{$this->baseUrl}/files/{$fileId}/export",
                    ['mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
                );
                if ($retryResponse->successful()) {
                    return $retryResponse->body();
                }
            }

            return null;
        }

        return $response->body();
    }

    /**
     * Detecta si un archivo es de Google Docs y retorna el mimeType de export.
     * Retorna null si no es un archivo de Google Docs (binario normal).
     */
    private function getExportMimeType(string $fileId): ?string
    {
        $info = $this->getFileInfo($fileId);
        $mime = $info['mimeType'] ?? '';

        $exportMap = [
            'application/vnd.google-apps.spreadsheet' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.google-apps.document' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.google-apps.presentation' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ];

        return $exportMap[$mime] ?? null;
    }

    /**
     * Descarga un archivo Excel y lo guarda temporalmente.
     */
    public function downloadExcelTemp(string $fileId): ?string
    {
        $content = $this->downloadFile($fileId);
        if (! $content) {
            return null;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'drive_excel_').'.xlsx';
        file_put_contents($tempFile, $content);

        return $tempFile;
    }

    /**
     * Obtiene el access token para la API de Drive.
     * Usa service account si esta configurada, sino API key.
     */
    private function getAccessToken(): string
    {
        if ($this->serviceAccountPath && file_exists($this->serviceAccountPath)) {
            return $this->getServiceAccountToken();
        }

        return $this->apiKey ?? '';
    }

    /**
     * Obtiene un token de acceso usando service account.
     */
    private function getServiceAccountToken(): string
    {
        $serviceAccount = json_decode(file_get_contents($this->serviceAccountPath), true);

        $now = time();
        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ]));

        $payload = $this->base64UrlEncode(json_encode([
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/drive.readonly',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $signature = '';
        openssl_sign(
            "{$header}.{$payload}",
            $signature,
            $serviceAccount['private_key'],
            'SHA256'
        );

        $jwt = "{$header}.{$payload}.".$this->base64UrlEncode($signature);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if ($response->failed()) {
            Log::error('Service account token error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return $this->apiKey;
        }

        return $response->json()['access_token'] ?? $this->apiKey;
    }

    /**
     * Codifica en base64 URL-safe.
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
