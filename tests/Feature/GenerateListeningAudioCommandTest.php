<?php

namespace Tests\Feature;

use App\Models\ListeningLesson;
use App\Services\TtsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class GenerateListeningAudioCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_synthesizes_the_listening_script_and_stores_the_audio(): void
    {
        Storage::fake('public');

        $lesson = ListeningLesson::create([
            'cefr_level' => 'A1',
            'sub_level' => 1,
            'title' => 'Airport notice',
            'description' => 'This description must not be synthesized.',
            'listening_script' => 'Board at gate five.',
        ]);

        $tts = Mockery::mock(TtsService::class);
        $tts->shouldReceive('isConfigured')->once()->andReturnTrue();
        $tts->shouldReceive('synthesize')
            ->once()
            ->with('Board at gate five.', 'en-US-Neural2-C')
            ->andReturn('mp3-content');
        $this->app->instance(TtsService::class, $tts);

        $this->artisan('generate:listening-audio')->assertSuccessful();

        $path = "listening/{$lesson->listening_lesson_id}.mp3";
        Storage::disk('public')->assertExists($path);
        $this->assertSame($path, $lesson->fresh()->audio_local_path);
    }

    public function test_dry_run_does_not_require_credentials_or_write_audio(): void
    {
        Storage::fake('public');

        $lesson = ListeningLesson::create([
            'cefr_level' => 'A1',
            'sub_level' => 2,
            'title' => 'Local preview',
            'listening_script' => 'Preview this script.',
        ]);

        $tts = Mockery::mock(TtsService::class);
        $tts->shouldNotReceive('isConfigured');
        $tts->shouldNotReceive('synthesize');
        $this->app->instance(TtsService::class, $tts);

        $this->artisan('generate:listening-audio', ['--dry-run' => true])->assertSuccessful();

        Storage::disk('public')->assertMissing("listening/{$lesson->listening_lesson_id}.mp3");
        $this->assertNull($lesson->fresh()->audio_local_path);
    }
}
