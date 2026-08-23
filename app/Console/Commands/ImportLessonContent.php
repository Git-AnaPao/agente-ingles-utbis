<?php

namespace App\Console\Commands;

use App\Models\ListeningLesson;
use Illuminate\Console\Command;
use JsonException;

class ImportLessonContent extends Command
{
    private const CEFR_LEVELS = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

    private const MAX_SPEAKING_LENGTH = 5000;

    protected $signature = 'import:lesson-content {--path= : Path to lesson-content.json} {--dry-run : Preview without saving}';

    protected $description = 'Import reading/listening/speaking content into listening_lessons';

    public function handle(): int
    {
        $path = $this->option('path') ?? storage_path('app/lesson-content.json');
        if (!is_file($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        try {
            $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $this->error("Invalid JSON: {$exception->getMessage()}");

            return self::FAILURE;
        }

        if (!is_array($data)) {
            $this->error('Invalid JSON: the root value must be an object.');

            return self::FAILURE;
        }

        $errors = $this->validationErrors($data);
        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->error($error);
            }

            $this->error('Import aborted before updating lessons.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $updated = 0;
        $skipped = 0;

        foreach ($data as $level => $lessons) {
            foreach ($lessons as $num => $content) {
                $lesson = ListeningLesson::byLevel($level)
                    ->where('sort_order', (int) $num)
                    ->first();

                if (!$lesson) {
                    $this->warn("  No DB lesson for {$level} lesson {$num}");
                    $skipped++;
                    continue;
                }

                if ($dryRun) {
                    $this->line("  [{$level}.{$num}] {$lesson->title}");
                    $updated++;
                    continue;
                }

                $lesson->update([
                    'reading_text' => $content['reading'] ?? null,
                    'listening_script' => $content['listening'] ?? null,
                    'speaking_text' => $content['speaking'] ?? null,
                ]);
                $updated++;
            }
        }

        $this->info("Done: {$updated} lessons updated, {$skipped} skipped.");
        return self::SUCCESS;
    }

    private function validationErrors(array $data): array
    {
        $errors = [];

        foreach ($data as $level => $lessons) {
            if (!in_array($level, self::CEFR_LEVELS, true)) {
                $errors[] = "Invalid CEFR level '{$level}'.";
                continue;
            }

            if (!is_array($lessons)) {
                $errors[] = "Lessons for {$level} must be an object.";
                continue;
            }

            foreach ($lessons as $number => $content) {
                if (filter_var($number, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                    $errors[] = "Invalid lesson number '{$number}' for {$level}.";
                    continue;
                }

                if (!is_array($content)) {
                    $errors[] = "Content for {$level}.{$number} must be an object.";
                    continue;
                }

                foreach (['reading', 'listening', 'speaking'] as $field) {
                    if (array_key_exists($field, $content)
                        && $content[$field] !== null
                        && !is_string($content[$field])) {
                        $errors[] = "{$level}.{$number}.{$field} must be a string or null.";
                    }
                }

                $speaking = $content['speaking'] ?? null;
                if (is_string($speaking) && mb_strlen($speaking) > self::MAX_SPEAKING_LENGTH) {
                    $length = mb_strlen($speaking);
                    $errors[] = "{$level}.{$number}.speaking is {$length} characters; maximum is ".self::MAX_SPEAKING_LENGTH.'.';
                }
            }
        }

        return $errors;
    }
}
