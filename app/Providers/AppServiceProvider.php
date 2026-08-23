<?php

namespace App\Providers;

use App\Contracts\AiProvider;
use App\Services\GeminiService;
use App\Services\OpenAiService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AiProvider::class, function (): AiProvider {
            return match (config('services.ai.provider')) {
                'openai' => new OpenAiService(),
                'groq' => new OpenAiService(
                    apiKey: (string) config('services.groq.api_key'),
                    baseUrl: (string) config('services.groq.base_url', 'https://api.groq.com/openai/v1'),
                    chatModel: (string) config('services.groq.chat_model', 'llama-3.3-70b-versatile'),
                    transcribeModel: (string) config('services.groq.transcribe_model', 'whisper-large-v3-turbo'),
                ),
                default => new GeminiService(),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
