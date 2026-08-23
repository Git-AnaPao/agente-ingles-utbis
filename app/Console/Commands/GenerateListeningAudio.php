<?php

namespace App\Console\Commands;

use App\Models\ListeningLesson;
use App\Services\TtsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateListeningAudio extends Command
{
    protected $signature = 'generate:listening-audio
        {--level= : Solo lecciones de un nivel CEFR (A1, A2, ...)}
        {--dry-run : Muestra lo que se generaría sin escribir archivos}
        {--force : Regenera audios aunque ya existan}';

    protected $description = 'Genera audios MP3 con Google Cloud TTS para lecciones de listening sin audio';

    public function handle(TtsService $tts): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        if (! $dryRun && ! $tts->isConfigured()) {
            $this->error('No hay credenciales configuradas. Agrega GOOGLE_SERVICE_ACCOUNT_PATH en el .env');

            return self::FAILURE;
        }

        $lessons = ListeningLesson::query()
            ->when($this->option('level'), fn ($query) => $query->where('cefr_level', strtoupper($this->option('level'))))
            ->get()
            ->filter(function (ListeningLesson $lesson) use ($force) {
                return $force || ! ($lesson->audio_drive_file_id || $lesson->audio_drive_url || $lesson->audio_local_path);
            });

        if ($lessons->isEmpty()) {
            $this->info('No hay lecciones pendientes de audio.');

            return self::SUCCESS;
        }

        $generated = 0;
        $failed = 0;

        foreach ($lessons as $lesson) {
            $text = trim((string) $lesson->listening_script);

            if ($text === '') {
                $this->warn("[{$lesson->cefr_level}.{$lesson->sub_level}] {$lesson->title}: sin listening_script");

                continue;
            }

            $voice = $this->pickVoice($lesson);

            $this->line("[{$lesson->cefr_level}.{$lesson->sub_level}] {$lesson->title} -> {$voice}");

            if ($dryRun) {
                $generated++;

                continue;
            }

            $audio = $tts->synthesize($text, $voice);

            if ($audio === null) {
                $this->error("  Fallo al sintetizar '{$lesson->title}'");
                $failed++;

                continue;
            }

            $path = "listening/{$lesson->listening_lesson_id}.mp3";

            try {
                if (! Storage::disk('public')->put($path, $audio)) {
                    throw new \RuntimeException('Storage write returned false.');
                }

                $lesson->update(['audio_local_path' => $path]);
            } catch (Throwable $exception) {
                $this->error("  No se pudo guardar el audio de '{$lesson->title}': {$exception->getMessage()}");
                $failed++;

                continue;
            }

            $generated++;
        }

        $action = $dryRun ? 'Audio pendiente para' : 'Audio generado para';
        $this->info("{$action} {$generated} lecciones.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Selecciona una voz Neural2 según el contenido (diálogo -> femenina, conversación -> masculina).
     */
    private function pickVoice(ListeningLesson $lesson): string
    {
        $sample = strtolower($lesson->title.' '.($lesson->listening_script ?? ''));
        $female = ['woman', 'girl', 'she', 'mrs', 'ms.', 'conversation'];

        foreach ($female as $marker) {
            if (str_contains($sample, $marker)) {
                return 'en-US-Neural2-F';
            }
        }

        return 'en-US-Neural2-C';
    }
}
