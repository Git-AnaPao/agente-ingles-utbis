<?php

namespace Tests\Feature\Api;

use App\Models\Lesson;
use App\Models\PlacementTest;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\Role;
use App\Models\User;
use App\Contracts\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ExamSubmissionSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_exam_rejects_duplicate_ids_and_questions_from_another_lesson(): void
    {
        $user = $this->makeStudent();
        [$lesson, $question] = $this->multipleChoiceLesson('A1');
        [, $foreignQuestion] = $this->multipleChoiceLesson('A2');
        $item = [
            'question_id' => $question->question_id,
            'answer' => $question->correct_option_id,
        ];

        $this->actingAs($user, 'api')
            ->postJson('/api/exam/submit', [
                'lesson_id' => $lesson->lesson_id,
                'responses' => [$item, $item],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('responses.1.question_id');

        $this->actingAs($user, 'api')
            ->postJson('/api/exam/submit', [
                'lesson_id' => $lesson->lesson_id,
                'responses' => [[
                    'question_id' => $foreignQuestion->question_id,
                    'answer' => $foreignQuestion->correct_option_id,
                ]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('responses');

        $this->assertDatabaseCount('attempt_logs', 0);
        $this->assertDatabaseCount('student_responses', 0);
    }

    public function test_exam_is_graded_on_the_server_and_result_omits_answer_keys_and_user_id(): void
    {
        $user = $this->makeStudent();
        [$lesson, $question] = $this->multipleChoiceLesson();
        $gemini = Mockery::mock(AiProvider::class);
        $gemini->shouldReceive('isConfigured')->once()->andReturnFalse();
        $this->app->instance(AiProvider::class, $gemini);

        $submit = $this->actingAs($user, 'api')
            ->postJson('/api/exam/submit', [
                'lesson_id' => $lesson->lesson_id,
                'responses' => [[
                    'question_id' => $question->question_id,
                    'answer' => $question->correct_option_id,
                ]],
            ]);

        $submit
            ->assertOk()
            ->assertJsonPath('score', 100)
            ->assertJsonPath('passed', true)
            ->assertJsonPath('responses.0.is_correct', true);
        $this->assertArrayNotHasKey('correct_answer', $submit->json('responses.0'));
        $this->assertArrayNotHasKey('user_id', $submit->json());

        $result = $this->actingAs($user, 'api')
            ->getJson('/api/exam/'.$submit->json('attempt_id').'/result');

        $result
            ->assertOk()
            ->assertJsonPath('responses.0.question.question_id', $question->question_id);
        $this->assertArrayNotHasKey('user_id', $result->json('attempt'));
        $this->assertArrayNotHasKey('correct_answer', $result->json('responses.0.question'));

        $otherUser = $this->makeStudent();
        $this->actingAs($otherUser, 'api')
            ->getJson('/api/exam/'.$submit->json('attempt_id').'/result')
            ->assertNotFound();
    }

    public function test_speaking_rejects_unapproved_mime_and_ai_failure_leaves_no_partial_attempt(): void
    {
        $user = $this->makeStudent('B1');
        [$lesson, $question] = $this->speakingLesson();
        $audio = base64_encode('fake audio bytes');
        $gemini = Mockery::mock(AiProvider::class);
        $gemini->shouldReceive('isConfigured')->once()->andReturnTrue();
        $gemini->shouldReceive('evaluateSpeakingAudio')
            ->once()
            ->andThrow(new RuntimeException('provider unavailable'));
        $this->app->instance(AiProvider::class, $gemini);

        $this->actingAs($user, 'api')
            ->postJson('/api/exam/submit', [
                'lesson_id' => $lesson->lesson_id,
                'responses' => [[
                    'question_id' => $question->question_id,
                    'answer' => null,
                    'audio_base64' => $audio,
                    'mime_type' => 'application/octet-stream',
                ]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('responses.0.mime_type');

        $this->actingAs($user, 'api')
            ->postJson('/api/exam/submit', [
                'lesson_id' => $lesson->lesson_id,
                'responses' => [[
                    'question_id' => $question->question_id,
                    'answer' => null,
                    'audio_base64' => $audio,
                    'mime_type' => 'audio/webm',
                ]],
            ])
            ->assertStatus(503);

        $this->assertDatabaseCount('attempt_logs', 0);
        $this->assertDatabaseCount('student_responses', 0);
    }

    public function test_exam_limits_the_number_of_responses(): void
    {
        $user = $this->makeStudent();
        [$lesson, $question] = $this->multipleChoiceLesson();
        $response = [
            'question_id' => $question->question_id,
            'answer' => $question->correct_option_id,
        ];

        $this->actingAs($user, 'api')
            ->postJson('/api/exam/submit', [
                'lesson_id' => $lesson->lesson_id,
                'responses' => array_fill(0, 51, $response),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('responses');

        $this->assertDatabaseCount('attempt_logs', 0);
    }

    public function test_exam_rejects_audio_larger_than_three_megabytes(): void
    {
        $user = $this->makeStudent('B1');
        [$lesson, $question] = $this->speakingLesson();

        $this->actingAs($user, 'api')
            ->postJson('/api/exam/submit', [
                'lesson_id' => $lesson->lesson_id,
                'responses' => [[
                    'question_id' => $question->question_id,
                    'answer' => null,
                    'audio_base64' => base64_encode(str_repeat('a', 3_000_001)),
                    'mime_type' => 'audio/webm',
                ]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('responses.0.audio_base64');

        $this->assertDatabaseCount('attempt_logs', 0);
    }

    public function test_exam_passes_at_seventy_percent_and_awards_web_equivalent_progress_xp(): void
    {
        $user = $this->makeStudent();
        [$lesson, $questionnaire] = $this->lessonAndQuestionnaire('A1');
        $responses = [];

        for ($index = 0; $index < 10; $index++) {
            $question = Question::create([
                'questionnaire_id' => $questionnaire->questionnaire_id,
                'question_type' => 'multiple_choice',
                'question_skill_type' => 'reading',
                'question_order' => $index + 1,
                'question_text' => "Question {$index}",
                'correct_answer' => null,
            ]);
            $correct = $question->options()->create([
                'option_text' => 'Correct',
                'is_correct' => true,
                'option_order' => 1,
            ]);
            $wrong = $question->options()->create([
                'option_text' => 'Wrong',
                'is_correct' => false,
                'option_order' => 2,
            ]);
            $responses[] = [
                'question_id' => $question->question_id,
                'answer' => $index < 7 ? $correct->option_id : $wrong->option_id,
            ];
        }

        $gemini = Mockery::mock(AiProvider::class);
        $gemini->shouldReceive('isConfigured')->once()->andReturnFalse();
        $this->app->instance(AiProvider::class, $gemini);

        $this->actingAs($user, 'api')->postJson('/api/exam/submit', [
            'lesson_id' => $lesson->lesson_id,
            'responses' => $responses,
        ])->assertOk()
            ->assertJsonPath('score', 70)
            ->assertJsonPath('passed', true)
            ->assertJsonPath('xp_awarded', 50)
            ->assertJsonPath('total_xp', 50);

        $this->assertDatabaseHas('student_progress', [
            'student_id' => $user->user_id,
            'lesson_id' => $lesson->lesson_id,
            'student_skill_type' => 'reading',
        ]);
    }

    public function test_general_ai_feedback_failure_after_persistence_is_non_blocking(): void
    {
        $user = $this->makeStudent();
        [$lesson, $question] = $this->multipleChoiceLesson();
        $gemini = Mockery::mock(AiProvider::class);
        $gemini->shouldReceive('isConfigured')->once()->andReturnTrue();
        $gemini->shouldReceive('generateGeneralFeedback')
            ->once()
            ->andThrow(new RuntimeException('feedback unavailable'));
        $this->app->instance(AiProvider::class, $gemini);

        $response = $this->actingAs($user, 'api')->postJson('/api/exam/submit', [
            'lesson_id' => $lesson->lesson_id,
            'responses' => [[
                'question_id' => $question->question_id,
                'answer' => $question->correct_option_id,
            ]],
        ]);

        $response->assertOk()
            ->assertJsonPath('passed', true)
            ->assertJsonPath('ai_feedback', null)
            ->assertJsonPath('xp_awarded', 50);
        $this->assertDatabaseHas('attempt_logs', [
            'attempt_id' => $response->json('attempt_id'),
            'passed' => true,
            'ai_feedback' => null,
        ]);
        $this->assertDatabaseHas('student_progress', [
            'student_id' => $user->user_id,
            'lesson_id' => $lesson->lesson_id,
            'student_skill_type' => 'reading',
        ]);
    }

    /**
     * @return array{Lesson, Question}
     */
    private function multipleChoiceLesson(string $level = 'A1'): array
    {
        [$lesson, $questionnaire] = $this->lessonAndQuestionnaire($level);
        $question = Question::create([
            'questionnaire_id' => $questionnaire->questionnaire_id,
            'question_type' => 'multiple_choice',
            'question_skill_type' => 'reading',
            'question_order' => 1,
            'question_text' => 'Choose the correct answer.',
            'correct_answer' => null,
        ]);
        $correct = $question->options()->create([
            'option_text' => 'Correct',
            'is_correct' => true,
            'option_order' => 1,
        ]);
        $question->options()->create([
            'option_text' => 'Wrong',
            'is_correct' => false,
            'option_order' => 2,
        ]);
        $question->setAttribute('correct_option_id', $correct->option_id);

        return [$lesson, $question];
    }

    /**
     * @return array{Lesson, Question}
     */
    private function speakingLesson(): array
    {
        [$lesson, $questionnaire] = $this->lessonAndQuestionnaire('B1');
        $question = Question::create([
            'questionnaire_id' => $questionnaire->questionnaire_id,
            'question_type' => 'speaking',
            'question_skill_type' => 'speaking',
            'question_order' => 1,
            'question_text' => 'Introduce yourself.',
            'correct_answer' => 'My name is Student.',
        ]);

        return [$lesson, $question];
    }

    /**
     * @return array{Lesson, Questionnaire}
     */
    private function lessonAndQuestionnaire(string $level): array
    {
        $lesson = Lesson::create([
            'lesson_cefr_level' => $level,
            'lesson_sub_level' => 1,
            'lesson_prompt_payload' => ['topic' => 'Exam security'],
        ]);
        $questionnaire = Questionnaire::create([
            'lesson_id' => $lesson->lesson_id,
            'title' => 'Exam security',
        ]);

        return [$lesson, $questionnaire];
    }

    private function makeStudent(string $placementLevel = 'A1'): User
    {
        $role = Role::firstOrCreate(
            ['role_name' => 'student'],
            ['role_description' => 'Student'],
        );
        $student = User::factory()->create();
        $student->roles()->attach($role->role_id);
        PlacementTest::create([
            'student_id' => $student->user_id,
            'result_level' => $placementLevel,
            'score' => 80,
        ]);

        return $student;
    }
}
