<?php

namespace Tests\Feature;

use App\Models\ListeningLesson;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestionOption;
use App\Services\ExcelReaderService;
use App\Services\GoogleDriveService;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ImportContentCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_placement_import_uses_the_correct_keys_for_questions_41_and_52(): void
    {
        $this->artisan('import:placement-questions')
            ->assertExitCode(Command::SUCCESS);

        $questionnaire = Questionnaire::query()
            ->where('title', 'Placement Test')
            ->whereNull('lesson_id')
            ->firstOrFail();

        $question41 = Question::query()
            ->where('questionnaire_id', $questionnaire->questionnaire_id)
            ->where('question_order', 41)
            ->firstOrFail();
        $this->assertSame('Worried', $question41->correct_answer);
        $this->assertSame(
            ['Excited', 'Worried', 'Confident', 'Surprised'],
            $question41->options()->orderBy('option_order')->pluck('option_text')->all(),
        );
        $this->assertSame(1, $question41->options()->where('is_correct', true)->count());
        $this->assertSame(
            'Worried',
            $question41->options()->where('is_correct', true)->value('option_text'),
        );

        $question52 = Question::query()
            ->where('questionnaire_id', $questionnaire->questionnaire_id)
            ->where('question_order', 52)
            ->firstOrFail();
        $this->assertSame('Why chocolate prices are expected to rise worldwide', $question52->correct_answer);
        $this->assertSame(1, $question52->options()->where('is_correct', true)->count());
    }

    public function test_lesson_content_import_aborts_before_updates_when_speaking_is_too_long(): void
    {
        $lesson = ListeningLesson::create([
            'cefr_level' => 'A1',
            'sub_level' => 1,
            'sort_order' => 1,
            'title' => 'Existing lesson',
            'reading_text' => 'Original reading',
            'speaking_text' => 'Original speaking',
        ]);

        $path = tempnam(sys_get_temp_dir(), 'lesson-content-');
        $this->assertNotFalse($path);
        file_put_contents($path, json_encode([
            'A1' => [
                '1' => [
                    'reading' => 'Replacement reading',
                    'listening' => 'Replacement listening',
                    'speaking' => str_repeat('x', 5001),
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        try {
            $this->artisan('import:lesson-content', ['--path' => $path])
                ->expectsOutputToContain('maximum is 5000')
                ->assertExitCode(Command::FAILURE);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }

        $lesson->refresh();
        $this->assertSame('Original reading', $lesson->reading_text);
        $this->assertSame('Original speaking', $lesson->speaking_text);
    }

    public function test_drive_import_skips_invalid_metadata_and_mc_keys_without_network(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'drive-import-');
        $this->assertNotFalse($tempFile);
        file_put_contents($tempFile, 'offline fixture');

        $drive = Mockery::mock(GoogleDriveService::class);
        $drive->shouldReceive('downloadExcelTemp')
            ->once()
            ->with('offline-excel')
            ->andReturn($tempFile);

        $excel = Mockery::mock(ExcelReaderService::class);
        $excel->shouldReceive('readExcel')
            ->once()
            ->with($tempFile)
            ->andReturn([
                'Overview' => [
                    $this->driveRow(level: 'A1'),
                ],
                'A2 Lessons 1-17' => [
                    $this->driveRow(answer: '0', options: '0 | 1'),
                    $this->driveRow(question: 'Bad key', answer: '2', options: '0 | 1'),
                    $this->driveRow(question: 'Bad skill', skill: 'Vocabulary'),
                    $this->driveRow(question: 'Bad sublevel', subLevel: '0'),
                    $this->driveRow(question: 'Bad type', type: 'essay'),
                ],
            ]);

        $this->app->instance(GoogleDriveService::class, $drive);
        $this->app->instance(ExcelReaderService::class, $excel);

        try {
            $this->artisan('import:listening', [
                '--excel-file-id' => 'offline-excel',
                '--audio-folder-id' => '',
            ])
                ->expectsOutputToContain("Skipping sheet 'Overview': no valid CEFR level was found")
                ->expectsOutputToContain("multiple-choice answer '2' does not match exactly one option")
                ->expectsOutputToContain('Skipped invalid input: 4 rows, 1 sheets')
                ->assertExitCode(Command::SUCCESS);
        } finally {
            if (is_file($tempFile)) {
                unlink($tempFile);
            }
        }

        $this->assertSame(1, ListeningLesson::count());
        $this->assertDatabaseMissing('listening_lessons', ['cefr_level' => 'A1']);

        $lesson = ListeningLesson::firstOrFail();
        $this->assertSame('A2', $lesson->cefr_level);
        $this->assertSame(1, $lesson->sub_level);
        $this->assertSame('0', $lesson->answers_data['1']);
        $this->assertSame(['0', '1'], $lesson->questions_data[0]['options']);
        $this->assertSame('listening', $lesson->questions_data[0]['skill']);
    }

    public function test_drive_import_rejects_an_invalid_level_filter_before_downloading(): void
    {
        $drive = Mockery::mock(GoogleDriveService::class);
        $drive->shouldNotReceive('downloadExcelTemp');
        $this->app->instance(GoogleDriveService::class, $drive);

        $this->artisan('import:listening', [
            '--excel-file-id' => 'offline-excel',
            '--audio-folder-id' => '',
            '--level' => 'Z9',
        ])->assertExitCode(Command::FAILURE);
    }

    public function test_question_import_preserves_zero_and_skips_invalid_source_lessons(): void
    {
        $valid = ListeningLesson::create([
            'cefr_level' => 'A2',
            'sub_level' => 1,
            'sort_order' => 1,
            'title' => 'Valid zero answer',
            'questions_data' => [[
                'number' => 1,
                'text' => 'How many items are left?',
                'type' => 'multiple_choice',
                'options' => ['0', '1'],
                'skill' => 'Listening',
            ]],
            'answers_data' => ['1' => '0'],
        ]);

        $badKey = ListeningLesson::create([
            'cefr_level' => 'A2',
            'sub_level' => 1,
            'sort_order' => 2,
            'title' => 'Invalid MC key',
            'questions_data' => [[
                'number' => 1,
                'text' => 'Choose one',
                'type' => 'multiple_choice',
                'options' => ['A', 'B'],
                'skill' => 'Listening',
            ]],
            'answers_data' => ['1' => 'C'],
        ]);

        $badMetadata = ListeningLesson::create([
            'cefr_level' => 'A2',
            'sub_level' => 0,
            'sort_order' => 3,
            'title' => 'Invalid metadata',
            'questions_data' => [[
                'number' => 1,
                'text' => 'Unsupported question',
                'type' => 'essay',
                'options' => [],
                'skill' => 'Vocabulary',
            ]],
            'answers_data' => ['1' => 'answer'],
        ]);

        $this->artisan('import:listening-questions')
            ->expectsOutputToContain("la clave MC 'C' no coincide con exactamente una opcion")
            ->expectsOutputToContain("Subnivel invalido '0'")
            ->assertExitCode(Command::SUCCESS);

        $questionnaire = Questionnaire::query()
            ->where('listening_lesson_id', $valid->listening_lesson_id)
            ->firstOrFail();
        $question = Question::query()
            ->where('questionnaire_id', $questionnaire->questionnaire_id)
            ->firstOrFail();

        $this->assertSame('0', $question->correct_answer);
        $this->assertTrue((bool) QuestionOption::query()
            ->where('question_id', $question->question_id)
            ->where('option_text', '0')
            ->value('is_correct'));
        $this->assertDatabaseMissing('questionnaires', ['listening_lesson_id' => $badKey->listening_lesson_id]);
        $this->assertDatabaseMissing('questionnaires', ['listening_lesson_id' => $badMetadata->listening_lesson_id]);
    }

    private function driveRow(
        string $level = 'A2',
        string $question = 'How many?',
        string $answer = 'A',
        string $options = 'A | B',
        string $skill = 'Listening',
        string $type = 'multiple_choice',
        string $subLevel = '1',
    ): array {
        return [
            'Nivel' => $level,
            'Lección' => '1',
            'Sub_Nivel' => $subLevel,
            'Titulo_Tema' => 'Offline lesson',
            'Habilidad' => $skill,
            'Tipo_Pregunta' => $type,
            'Pregunta_Texto' => $question,
            'Respuesta_Correcta' => $answer,
            'Opciones' => $options,
        ];
    }
}
