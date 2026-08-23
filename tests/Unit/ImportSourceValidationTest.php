<?php

namespace Tests\Unit;

use App\Console\Commands\ImportLessonContent;
use App\Console\Commands\ImportListeningFromDrive;
use App\Console\Commands\ImportListeningQuestions;
use App\Console\Commands\ImportPlacementQuestions;
use App\Models\ListeningLesson;
use App\Services\ExcelReaderService;
use App\Services\GoogleDriveService;
use JsonException;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ImportSourceValidationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_placement_source_has_unambiguous_correct_keys(): void
    {
        $questions = $this->invoke(new ImportPlacementQuestions, 'questions');
        $byOrder = array_column($questions, null, 'order');

        $this->assertSame('Worried', $byOrder[41]['options'][$byOrder[41]['correct']]);
        $this->assertSame(
            ['Excited', 'Worried', 'Confident', 'Surprised'],
            $byOrder[41]['options'],
        );
        $this->assertSame(
            'Why chocolate prices are expected to rise worldwide',
            $byOrder[52]['options'][$byOrder[52]['correct']],
        );
    }

    /**
     * @throws JsonException
     */
    public function test_lesson_content_source_has_the_editorial_regressions_fixed(): void
    {
        $path = dirname(__DIR__, 2).'/storage/app/lesson-content.json';
        $content = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $a28Listening = $content['A2']['8']['listening'];
        $this->assertStringContainsString('Maya: Do we have any coffee?', $a28Listening);
        $this->assertSame(1, substr_count($a28Listening, "No, there's no bread. We finished it yesterday."));

        $a234Speaking = $content['A2']['34']['speaking'];
        $this->assertLessThanOrEqual(5000, mb_strlen($a234Speaking));
        $this->assertStringNotContainsString('Lección 35', $a234Speaking);
        $this->assertStringEndsWith('smoothly and naturally!', $a234Speaking);

        $this->assertStringContainsString('a total of ninety-five dollars', $content['A1']['9']['reading']);
        $this->assertStringContainsString('works as an architect', $content['A1']['3']['speaking']);
        $this->assertStringContainsString('an Italian restaurant', $content['A1']['6']['listening']);
        $this->assertStringContainsString('an underground subway station', $content['A1']['25']['listening']);
        $this->assertStringContainsString('Welcome back to Childhood Memories', $content['A1']['31']['listening']);
        $this->assertStringContainsString('an unexpected thirty-minute delay', $content['A2']['19']['reading']);
        $this->assertStringContainsString('an early morning flight', $content['A2']['20']['reading']);
        $this->assertStringContainsString('an eventful day', $content['A2']['22']['reading']);
        $this->assertStringContainsString('what is certain or highly likely', $content['A2']['32']['speaking']);
    }

    public function test_lesson_content_validation_rejects_invalid_levels_and_long_speaking(): void
    {
        $command = new ImportLessonContent;

        $invalidLevel = $this->invoke($command, 'validationErrors', [
            ['Z9' => ['1' => ['speaking' => 'Short prompt']]],
        ]);
        $this->assertContains("Invalid CEFR level 'Z9'.", $invalidLevel);

        $longSpeaking = $this->invoke($command, 'validationErrors', [
            ['A1' => ['1' => ['speaking' => str_repeat('x', 5001)]]],
        ]);
        $this->assertNotEmpty($longSpeaking);
        $this->assertStringContainsString('maximum is 5000', $longSpeaking[0]);
    }

    public function test_drive_normalization_never_defaults_invalid_values_and_preserves_zero(): void
    {
        $command = new ImportListeningFromDrive(
            Mockery::mock(GoogleDriveService::class),
            Mockery::mock(ExcelReaderService::class),
        );

        $this->assertNull($this->invoke($command, 'extractLevelAndSubLevel', ['Overview']));
        $this->assertSame(['A2', 1], $this->invoke($command, 'extractLevelAndSubLevel', ['A2 Lessons 1-17']));
        $this->assertNull($this->invoke($command, 'normalizeLevel', ['Z9']));
        $this->assertNull($this->invoke($command, 'normalizeType', ['essay']));
        $this->assertNull($this->invoke($command, 'normalizeSkill', ['Vocabulary']));
        $this->assertNull($this->invoke($command, 'extractSubLevel', ['0', 1]));
        $this->assertSame(['0', '1'], $this->invoke($command, 'parseOptions', ['0 | 1']));
        $this->assertSame('0', $this->invoke($command, 'getColumnValue', [
            ['Respuesta_Correcta' => '0'],
            ['Respuesta_Correcta'],
        ]));
    }

    public function test_question_preflight_preserves_zero_and_reports_bad_mc_keys(): void
    {
        $command = new ImportListeningQuestions;
        $valid = new ListeningLesson([
            'cefr_level' => 'A2',
            'sub_level' => 1,
            'sort_order' => 1,
            'title' => 'Zero answer',
            'questions_data' => [[
                'number' => 1,
                'text' => 'How many?',
                'type' => 'multiple_choice',
                'options' => ['0', '1'],
                'skill' => 'Listening',
            ]],
            'answers_data' => ['1' => '0'],
        ]);

        [$questions, $errors] = $this->invoke($command, 'prepareQuestions', [$valid]);
        $this->assertSame([], $errors);
        $this->assertSame('0', $questions[0]['answer']);
        $this->assertSame(['0', '1'], $questions[0]['options']);
        $this->assertSame('listening', $questions[0]['skill']);

        $invalid = new ListeningLesson([
            'cefr_level' => 'A2',
            'sub_level' => 1,
            'sort_order' => 2,
            'title' => 'Bad key',
            'questions_data' => [[
                'number' => 1,
                'text' => 'Choose one',
                'type' => 'multiple_choice',
                'options' => ['A', 'B'],
                'skill' => 'Listening',
            ]],
            'answers_data' => ['1' => 'C'],
        ]);

        [$questions, $errors] = $this->invoke($command, 'prepareQuestions', [$invalid]);
        $this->assertSame([], $questions);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString("la clave MC 'C' no coincide", $errors[0]);
    }

    private function invoke(object $object, string $method, array $arguments = []): mixed
    {
        $reflection = new ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, array_values($arguments));
    }
}
