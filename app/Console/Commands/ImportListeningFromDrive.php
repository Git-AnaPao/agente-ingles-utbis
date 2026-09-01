<?php

namespace App\Console\Commands;

use App\Models\ListeningLesson;
use App\Services\ExcelReaderService;
use App\Services\GoogleDriveService;
use Illuminate\Console\Command;

class ImportListeningFromDrive extends Command
{
    private const CEFR_LEVELS = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

    private const MAX_SPEAKING_LENGTH = 5000;

    protected $signature = 'import:listening
                            {--excel-file-id= : Google Drive file ID of the Excel file}
                            {--audio-folder-id= : Google Drive folder ID containing audio files}
                            {--level= : Filter by CEFR level (A1, A2, etc.)}
                            {--dry-run : Preview changes without saving}';

    protected $description = 'Import listening lessons from Google Drive (Excel + Audio files)';

    private GoogleDriveService $driveService;
    private ExcelReaderService $excelService;
    private int $skippedRows = 0;
    private int $skippedSheets = 0;

    public function __construct(GoogleDriveService $driveService, ExcelReaderService $excelService)
    {
        parent::__construct();
        $this->driveService = $driveService;
        $this->excelService = $excelService;
    }

    public function handle(): int
    {
        $excelFileId = $this->option('excel-file-id') ?? config('services.google.drive_excel_file_id');
        $audioFolderId = $this->option('audio-folder-id') ?? config('services.google.drive_audio_folder_id');
        $levelFilter = $this->option('level');
        $levelFilter = $levelFilter === null || trim((string) $levelFilter) === ''
            ? null
            : strtoupper(trim((string) $levelFilter));
        $dryRun = (bool) $this->option('dry-run');

        if ($levelFilter !== null && !in_array($levelFilter, self::CEFR_LEVELS, true)) {
            $this->error("Invalid CEFR level '{$levelFilter}'. Expected: " . implode(', ', self::CEFR_LEVELS) . '.');

            return self::FAILURE;
        }

        if (!$excelFileId) {
            $this->error('Please provide --excel-file-id option or set GOOGLE_DRIVE_EXCEL_FILE_ID in .env');
            return self::FAILURE;
        }

        $this->info('Downloading Excel file from Google Drive...');

        $tempFile = $this->driveService->downloadExcelTemp($excelFileId);
        if (!$tempFile) {
            $this->error('Failed to download Excel file from Google Drive');
            return self::FAILURE;
        }

        $this->info('Reading Excel file...');
        $excelData = $this->excelService->readExcel($tempFile);

        @unlink($tempFile);

        if (empty($excelData)) {
            $this->error('No data found in Excel file');
            $this->newLine();
            $this->warn('Check storage/logs/laravel.log for details.');
            $this->warn('Possible causes:');
            $this->warn('  - PhpSpreadsheet not installed (run: composer dump-autoload)');
            $this->warn('  - Google Drive credentials not configured');
            $this->warn('  - File is empty or has only headers');
            return self::FAILURE;
        }

        $this->info('Found ' . count($excelData) . ' sheets in Excel');
        $this->newLine();

        $imported = 0;
        $totalQuestions = 0;

        foreach ($excelData as $sheetName => $rows) {
            $sheetLevel = $this->extractLevelAndSubLevel((string) $sheetName);

            if ($sheetLevel === null) {
                $this->warn("  Skipping sheet '{$sheetName}': no valid CEFR level was found.");
                $this->skippedSheets++;
                continue;
            }

            [$level, $sheetSubLevel] = $sheetLevel;

            if ($levelFilter && $level !== $levelFilter) {
                $this->line("  Skipping sheet: {$sheetName} (level: {$level})");
                continue;
            }

            if (!is_array($rows)) {
                $this->warn("  Skipping sheet '{$sheetName}': rows must be an array.");
                $this->skippedSheets++;
                continue;
            }

            $displaySubLevel = $sheetSubLevel ?? 'not specified';
            $this->info("Processing sheet: {$sheetName} (Level: {$level}, SubLevel: {$displaySubLevel})");

            $grouped = $this->groupByLesson($rows, $level, $sheetSubLevel);

            foreach ($grouped as $lessonData) {
                $this->line("  [{$lessonData['sort_order']}] {$lessonData['title']} ({$lessonData['question_count']} questions)");

                if ($dryRun) {
                    $imported++;
                    $totalQuestions += $lessonData['question_count'];
                    continue;
                }

                $listeningLesson = ListeningLesson::updateOrCreate(
                    [
                        'cefr_level' => $lessonData['level'],
                        'sort_order' => $lessonData['sort_order'],
                    ],
                    [
                        'sub_level' => $lessonData['sub_level'],
                        'title' => $lessonData['title'],
                        'description' => $lessonData['description'],
                        'questions_data' => $lessonData['questions'],
                        'answers_data' => $lessonData['answers'],
                    ]
                );

                $imported++;
                $totalQuestions += $lessonData['question_count'];
            }
        }
        $this->processResourcesSheet($excelData, $levelFilter, $dryRun);
        if ($audioFolderId && !$dryRun) {
            $this->newLine();
            $this->info('Linking audio files...');
            $this->linkAudioFiles($audioFolderId, $levelFilter);
        }

        $this->newLine();
        $this->info("Import complete: {$imported} lessons ({$totalQuestions} questions)");

        if ($this->skippedRows > 0 || $this->skippedSheets > 0) {
            $this->warn("Skipped invalid input: {$this->skippedRows} rows, {$this->skippedSheets} sheets.");
        }

        if ($imported === 0) {
            $this->error('No valid lessons were found to import.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function extractLevelAndSubLevel(string $sheetName): ?array
    {
        $sheetName = trim($sheetName);
        if (!preg_match('/(?<![A-Z0-9])(A1|A2|B1|B2|C1|C2)(?![A-Z0-9])/i', $sheetName, $levelMatches)) {
            return null;
        }

        $level = strtoupper($levelMatches[1]);
        $subLevel = null;

        if (preg_match('/(\d+)-(\d+)/', $sheetName, $rangeMatches)) {
            $start = (int) $rangeMatches[1];
            if ($start < 1) {
                return null;
            } elseif ($start <= 17) {
                $subLevel = 1;
            } elseif ($start <= 34) {
                $subLevel = 2;
            } elseif ($start <= 50) {
                $subLevel = 3;
            } else {
                $subLevel = (int) ceil($start / 17);
            }
        } elseif (preg_match('/Lessons?\s+(\d+)/iu', $sheetName, $singleMatches)) {
            $lessonNum = (int) $singleMatches[1];
            if ($lessonNum < 1) {
                return null;
            }
            $subLevel = (int) ceil($lessonNum / 17);
        }

        return [$level, $subLevel];
    }

    private function extractLevel(string $sheetName): ?string
    {
        $levelAndSubLevel = $this->extractLevelAndSubLevel($sheetName);

        return $levelAndSubLevel[0] ?? null;
    }

    /**
     * Group flat question rows into lessons.
     * Excel columns: Nivel, Lección, Sub_Nivel, Titulo_Tema, Habilidad, Tipo_Pregunta,
     * Pregunta_Texto, Respuesta_Correcta, Opciones, Tipo_Recurso, URL_Recurso, Transcripción
     */
    private function groupByLesson(array $rows, string $level, ?int $sheetSubLevel): array
    {
        $lessons = [];

        foreach (array_values($rows) as $index => $row) {
            $rowNumber = $index + 2;

            if (!is_array($row)) {
                $this->skipRow($rowNumber, 'row data must be an array');
                continue;
            }

            $rowLevelRaw = $this->getColumnValue($row, ['Nivel', 'Level', 'CEFR', 'cefr_level']);
            $lessonKey = $this->getColumnValue($row, ['Lección', 'Leccion', 'Leccin', 'Lesson', 'lesson']);
            $subLevelRaw = $this->getColumnValue($row, ['Sub_Nivel', 'SubNivel', 'Sublevel', 'sub_level']);
            $title = $this->getColumnValue($row, ['Titulo_Tema', 'Title', 'titulo']);
            $questionText = $this->getColumnValue($row, ['Pregunta_Texto', 'Question', 'pregunta']);
            $correctAnswer = $this->getColumnValue($row, ['Respuesta_Correcta', 'Answer', 'respuesta']);
            $optionsRaw = $this->getColumnValue($row, ['Opciones', 'Options', 'opciones']);
            $typeRaw = $this->getColumnValue($row, ['Tipo_Pregunta', 'Type', 'tipo']);
            $skillRaw = $this->getColumnValue($row, ['Habilidad', 'Skill', 'habilidad']);
            $resourceType = $this->getColumnValue($row, ['Tipo_Recurso', 'Resource_Type']);
            $resourceUrl = $this->getColumnValue($row, ['URL_Recurso', 'Resource_URL']);
            $transcription = $this->getColumnValue($row, ['Transcripción', 'Transcripcion', 'Transcripcin', 'Transcription']);

            if ($rowLevelRaw !== null) {
                $rowLevel = $this->normalizeLevel($rowLevelRaw);
                if ($rowLevel === null) {
                    $this->skipRow($rowNumber, "invalid CEFR level '{$rowLevelRaw}'");
                    continue;
                }

                if ($rowLevel !== $level) {
                    $this->skipRow($rowNumber, "row level {$rowLevel} does not match sheet level {$level}");
                    continue;
                }
            }

            if ($questionText === null) {
                $this->skipRow($rowNumber, 'question text is missing');
                continue;
            }

            $type = $this->normalizeType($typeRaw);
            if ($type === null) {
                $displayType = $typeRaw ?? '(missing)';
                $this->skipRow($rowNumber, "invalid question type '{$displayType}'");
                continue;
            }

            $skill = $this->normalizeSkill($skillRaw);
            if ($skill === null) {
                $displaySkill = $skillRaw ?? '(missing)';
                $this->skipRow($rowNumber, "invalid skill '{$displaySkill}'");
                continue;
            }

            if ($correctAnswer === null && $type !== 'speaking') {
                $this->skipRow($rowNumber, 'correct answer is missing');
                continue;
            }

            $sortOrder = $this->extractLessonNumber($lessonKey);
            $subLevel = $this->extractSubLevel($subLevelRaw, $sheetSubLevel);
            if ($sortOrder === null) {
                $this->skipRow($rowNumber, 'lesson number is missing or invalid');
                continue;
            }

            if ($subLevel === null) {
                $this->skipRow($rowNumber, 'sub-level is missing or invalid');
                continue;
            }

            $cleanTitle = $this->cleanTitle($title);
            $options = $this->parseOptions($optionsRaw);

            if ($type === 'multiple_choice') {
                if ($correctAnswer === null || count($options) < 2) {
                    $this->skipRow($rowNumber, 'multiple-choice questions require an answer and at least two options');
                    continue;
                }

                $matchingOptions = array_filter(
                    $options,
                    fn(string $option): bool => $this->normalizeAnswer($option) === $this->normalizeAnswer($correctAnswer),
                );

                if (count($matchingOptions) !== 1) {
                    $this->skipRow($rowNumber, "multiple-choice answer '{$correctAnswer}' does not match exactly one option");
                    continue;
                }
            }

            if ($type === 'speaking' && mb_strlen($questionText) > self::MAX_SPEAKING_LENGTH) {
                $this->skipRow($rowNumber, 'speaking prompt exceeds ' . self::MAX_SPEAKING_LENGTH . ' characters');
                continue;
            }

            $groupKey = "{$level}_{$subLevel}_{$sortOrder}";

            if (!isset($lessons[$groupKey])) {
                $lessons[$groupKey] = [
                    'level' => $level,
                    'sub_level' => $subLevel,
                    'title' => $cleanTitle ?: "Lesson {$sortOrder}",
                    'description' => $skill ? "Habilidad: {$skill}" : null,
                    'sort_order' => $sortOrder,
                    'questions' => [],
                    'answers' => [],
                    'question_count' => 0,
                    'resource_type' => $resourceType,
                    'resource_url' => $resourceUrl,
                    'transcription' => $transcription,
                ];
            }

            if ($cleanTitle && strlen($cleanTitle) >= strlen($lessons[$groupKey]['title'])) {
                $lessons[$groupKey]['title'] = $cleanTitle;
            }

            $qNum = $lessons[$groupKey]['question_count'] + 1;

            $lessons[$groupKey]['questions'][] = [
                'number' => $qNum,
                'text' => $questionText,
                'type' => $type,
                'options' => $options,
                'skill' => $skill,
                'resource_type' => $resourceType,
                'resource_url' => $resourceUrl,
                'transcription' => $transcription,
            ];

            $lessons[$groupKey]['answers'][(string) $qNum] = $correctAnswer;
            $lessons[$groupKey]['question_count'] = $qNum;
        }

        usort($lessons, fn($a, $b) => $a['sub_level'] <=> $b['sub_level'] ?: $a['sort_order'] <=> $b['sort_order']);

        return $lessons;
    }

    private function getColumnValue(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($row[$key]) && $row[$key] !== null && trim((string) $row[$key]) !== '') {
                return trim((string) $row[$key]);
            }
        }
        return null;
    }

    private function cleanTitle(?string $title): ?string
    {
        if (!$title) {
            return null;
        }

        $title = str_replace('_', ' ', $title);
        $title = preg_replace('/^Lecci.n\s+\d+\s*:?\s*/iu', '', $title);
        $title = preg_replace('/^Lesson\s+\d+\s*:?\s*/iu', '', $title);
        $title = trim($title);

        return $title ?: null;
    }

    private function extractLessonNumber(?string $lessonKey): ?int
    {
        if (!$lessonKey) {
            return null;
        }

        if (preg_match('/(\d+)/', $lessonKey, $matches)) {
            $number = (int) $matches[1];

            return $number > 0 ? $number : null;
        }

        return null;
    }

    private function extractSubLevel(?string $subLevelRaw, ?int $sheetSubLevel): ?int
    {
        if ($subLevelRaw !== null) {
            if (!preg_match('/^\D*(\d+)\D*$/u', $subLevelRaw, $matches)) {
                return null;
            }

            $subLevel = (int) $matches[1];

            return $subLevel > 0 ? $subLevel : null;
        }

        return $sheetSubLevel !== null && $sheetSubLevel > 0 ? $sheetSubLevel : null;
    }

    private function parseOptions(?string $optionsRaw): array
    {
        if ($optionsRaw === null || trim($optionsRaw) === '') {
            return [];
        }

        $parts = preg_split('/\s*\|\s*/', $optionsRaw);

        return array_values(array_filter(
            array_map('trim', $parts),
            fn(string $option): bool => $option !== '',
        ));
    }

    private function normalizeType(?string $type): ?string
    {
        if ($type === null) {
            return null;
        }

        $type = strtolower(trim($type));

        $map = [
            'multiple_choice' => 'multiple_choice',
            'multiple choice' => 'multiple_choice',
            'opcion multiple' => 'multiple_choice',
            'opción multiple' => 'multiple_choice',
            'fill_blank' => 'fill_blank',
            'fill in the blank' => 'fill_blank',
            'completar' => 'fill_blank',
            'true_false' => 'multiple_choice',
            'true or false' => 'multiple_choice',
            'verdadero o falso' => 'multiple_choice',
            'listening' => 'listening',
            'speaking' => 'speaking',
        ];

        return $map[$type] ?? null;
    }

    private function normalizeLevel(string $level): ?string
    {
        $level = strtoupper(trim($level));

        return in_array($level, self::CEFR_LEVELS, true) ? $level : null;
    }

    private function normalizeSkill(?string $skill): ?string
    {
        if ($skill === null) {
            return null;
        }

        $map = [
            'reading' => 'reading',
            'lectura' => 'reading',
            'listening' => 'listening',
            'escucha' => 'listening',
            'writing' => 'writing',
            'escritura' => 'writing',
            'speaking' => 'speaking',
            'oral' => 'speaking',
        ];

        return $map[mb_strtolower(trim($skill))] ?? null;
    }

    private function normalizeAnswer(string $answer): string
    {
        $answer = mb_strtolower(trim($answer));
        $answer = preg_replace('/\s+/u', ' ', $answer);
        $answer = ltrim($answer, '$€£¥¢');

        return rtrim($answer, '.,!?;:');
    }

    private function skipRow(int $rowNumber, string $reason): void
    {
        $this->skippedRows++;
        $this->warn("  Skipping row {$rowNumber}: {$reason}.");
    }

    private function linkAudioFiles(string $folderId, ?string $levelFilter): void
    {
        $folders = $this->driveService->listFolders($folderId);

        foreach ($folders as $folder) {
            $levelAndSub = $this->extractLevelAndSubLevel($folder['name']);
            $folderLevel = $levelAndSub ? $levelAndSub[0] : $this->extractLevel($folder['name']);
            $folderSubLevel = $levelAndSub ? $levelAndSub[1] : null;

            if ($folderLevel === null) {
                $this->warn("  Skipping audio folder '{$folder['name']}': no valid CEFR level was found.");
                continue;
            }

            if ($levelFilter && $folderLevel !== $levelFilter) {
                continue;
            }

            $displaySub = $folderSubLevel ? "SubLevel {$folderSubLevel}" : 'All SubLevels';
            $this->line("  Processing audio folder: {$folder['name']} (Level: {$folderLevel}, {$displaySub})");

            $audioFiles = $this->driveService->listAudioFiles($folder['id']);

            foreach ($audioFiles as $audioFile) {
                $audioName = strtolower(preg_replace('/(\.\w+)+$/', '', $audioFile['name']));

                $match = $this->findLessonByAudioName($audioName, $folderLevel, $folderSubLevel);

                if ($match) {
                    $match->update([
                        'audio_drive_file_id' => $audioFile['id'],
                        'audio_drive_url' => $this->driveService->getDownloadUrl($audioFile['id']),
                    ]);
                    $this->line("    Linked: {$audioFile['name']} -> {$match->title}");
                } else {
                    $this->warn("    No match for: {$audioFile['name']}");
                }
            }
        }
    }

    private function findLessonByAudioName(string $audioName, string $level, ?int $subLevel = null): ?ListeningLesson
    {
        if (!preg_match_all('/(\d+)/', $audioName, $matches)) {
            return null;
        }

        $lessonNumber = (int) end($matches[1]);

        $query = ListeningLesson::byLevel($level);

        // Si tenemos el subnivel especificado en la carpeta
        if ($subLevel !== null) {
            // Intentar primero como orden absoluto dentro del subnivel
            $absoluteMatch = (clone $query)->where('sub_level', $subLevel)->where('sort_order', $lessonNumber)->first();
            if ($absoluteMatch) {
                return $absoluteMatch;
            }

            // Si está numerado relativamente (1..17 dentro del subnivel)
            $relativeMatch = (clone $query)
                ->where('sub_level', $subLevel)
                ->orderBy('sort_order')
                ->skip(max(0, $lessonNumber - 1))
                ->first();

            if ($relativeMatch) {
                return $relativeMatch;
            }
        }

        return $query->where('sort_order', $lessonNumber)->first();
    }

    /**
 * Import Reading, Listening and Speaking resources
 * from the Resources sheet.
 */
private function processResourcesSheet(
    array $excelData,
    ?string $levelFilter = null,
    bool $dryRun = false
): void {
    $resourcesSheetName = null;

    foreach (array_keys($excelData) as $sheetName) {
        if (in_array(
            strtolower(trim($sheetName)),
            ['recursos', 'resources'],
            true
        )) {
            $resourcesSheetName = $sheetName;
            break;
        }
    }

    if (!$resourcesSheetName || empty($excelData[$resourcesSheetName])) {
        $this->warn(
            "No Resources sheet was found. Reading, Listening and Speaking texts were not imported."
        );

        return;
    }

    $this->newLine();
    $this->info("Processing resources sheet: {$resourcesSheetName}...");

    $rows = $excelData[$resourcesSheetName];
    $updatedCount = 0;

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $cefrLevel = strtoupper(trim(
            $this->getColumnValue(
                $row,
                ['Nivel', 'CEFR', 'level']
            ) ?? ''
        ));

        $lessonRaw = $this->getColumnValue(
            $row,
            ['Leccion', 'Lección', 'Lesson', 'leccion']
        );

        $typeRaw = strtoupper(trim(
            $this->getColumnValue(
                $row,
                ['Tipo', 'Resource_Type', 'tipo']
            ) ?? ''
        ));

        $content = trim(
            $this->getColumnValue(
                $row,
                ['Contenido', 'Content', 'contenido']
            ) ?? ''
        );

        $lessonNumber = $this->extractLessonNumber($lessonRaw);

        if (
            !$cefrLevel ||
            !$lessonNumber ||
            !$typeRaw ||
            !$content
        ) {
            continue;
        }

        // Respect --level=A1, --level=A2, etc.
        if ($levelFilter && $cefrLevel !== $levelFilter) {
            continue;
        }

        $lesson = ListeningLesson::where(
            'cefr_level',
            $cefrLevel
        )
            ->where('sort_order', $lessonNumber)
            ->first();

        if (!$lesson) {
            $this->warn(
                "  Lesson not found: {$cefrLevel} - Lesson {$lessonNumber}"
            );

            continue;
        }

        if ($dryRun) {
            $this->line(
                "  [DRY RUN] {$cefrLevel} Lesson {$lessonNumber}: {$typeRaw}"
            );
            $updatedCount++;
            continue;
        }

        if (str_contains($typeRaw, 'READING')) {
            $lesson->update([
                'reading_text' => $content,
            ]);

            $updatedCount++;
        } elseif (str_contains($typeRaw, 'LISTENING')) {
            $lesson->update([
                'listening_script' => $content,
            ]);

            $updatedCount++;
        } elseif (str_contains($typeRaw, 'SPEAK')) {
            $lesson->update([
                'speaking_text' => $content,
            ]);

            $updatedCount++;
        }
    }

    if ($dryRun) {
        $this->info(
            "Resources found: {$updatedCount} (dry run, no changes saved)."
        );
    } else {
        $this->info(
            "Updated {$updatedCount} Reading, Listening and Speaking resources."
        );
    }
}
}
