<?php

namespace Tests\Feature\Api;

use App\Models\Lesson;
use App\Models\PlacementTest;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgressSubmissionSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_correct_flag_is_rejected_and_wrong_answer_is_graded_on_the_server(): void
    {
        $user = $this->makeStudent();
        [$lesson, $questions] = $this->lessonWithQuestions(1);
        $question = $questions[0];

        $this->actingAs($user, 'api')
            ->postJson("/api/progress/lessons/{$lesson->lesson_id}/attempt", [
                'question_id' => $question->question_id,
                'answer' => $question->wrong_option_id,
                'correct' => true,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('correct');

        $this->assertDatabaseCount('attempt_logs', 0);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/progress/lessons/{$lesson->lesson_id}/attempt", [
                'question_id' => $question->question_id,
                'answer' => $question->wrong_option_id,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('attempt.passed', false)
            ->assertJsonPath('attempt.score', 0)
            ->assertJsonPath('result.is_correct', false);
        $this->assertDatabaseHas('student_responses', [
            'question_id' => $question->question_id,
            'is_correct' => false,
        ]);
    }

    public function test_question_must_belong_to_the_lesson_in_single_and_batch_submissions(): void
    {
        $user = $this->makeStudent();
        [$lesson] = $this->lessonWithQuestions(1);
        [, $foreignQuestions] = $this->lessonWithQuestions(1, 'A2');
        $foreignQuestion = $foreignQuestions[0];

        $this->actingAs($user, 'api')
            ->postJson("/api/progress/lessons/{$lesson->lesson_id}/attempt", [
                'question_id' => $foreignQuestion->question_id,
                'answer' => $foreignQuestion->correct_option_id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('question_id');

        $this->actingAs($user, 'api')
            ->postJson('/api/progress/attempts/batch', [
                'attempts' => [[
                    'lesson_id' => $lesson->lesson_id,
                    'question_id' => $foreignQuestion->question_id,
                    'answer' => $foreignQuestion->correct_option_id,
                ]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('attempts.0.question_id');

        $this->assertDatabaseCount('attempt_logs', 0);
    }

    public function test_completion_requires_a_complete_passing_attempt_as_evidence(): void
    {
        $user = $this->makeStudent();
        [$lesson, $questions] = $this->lessonWithQuestions(2);

        $this->actingAs($user, 'api')
            ->postJson("/api/progress/lessons/{$lesson->lesson_id}/complete")
            ->assertUnprocessable();
        $this->assertDatabaseCount('student_progress', 0);

        $attempts = collect($questions)
            ->map(fn (Question $question): array => [
                'lesson_id' => $lesson->lesson_id,
                'question_id' => $question->question_id,
                'answer' => $question->correct_option_id,
            ])
            ->all();

        $this->actingAs($user, 'api')
            ->postJson('/api/progress/attempts/batch', ['attempts' => $attempts])
            ->assertOk()
            ->assertJsonPath('attempts.0.passed', true)
            ->assertJsonCount(2, 'results');

        $this->actingAs($user, 'api')
            ->postJson("/api/progress/lessons/{$lesson->lesson_id}/complete")
            ->assertOk()
            ->assertJsonPath('completed', true);

        $this->assertDatabaseHas('student_progress', [
            'student_id' => $user->user_id,
            'lesson_id' => $lesson->lesson_id,
            'student_skill_type' => 'reading',
        ]);
    }

    public function test_batch_rejects_duplicate_question_ids(): void
    {
        $user = $this->makeStudent();
        [$lesson, $questions] = $this->lessonWithQuestions(1);
        $question = $questions[0];
        $item = [
            'lesson_id' => $lesson->lesson_id,
            'question_id' => $question->question_id,
            'answer' => $question->correct_option_id,
        ];

        $this->actingAs($user, 'api')
            ->postJson('/api/progress/attempts/batch', [
                'attempts' => [$item, $item],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('attempts.1.question_id');

        $this->assertDatabaseCount('attempt_logs', 0);
    }

    public function test_api_requires_every_activity_before_awarding_skill_progress_and_xp(): void
    {
        $user = $this->makeStudent();
        [$lesson, $questions] = $this->lessonWithQuestions(1);
        $first = $questions[0];
        $secondQuestionnaire = Questionnaire::create([
            'lesson_id' => $lesson->lesson_id,
            'title' => 'Second activity',
        ]);
        $second = Question::create([
            'questionnaire_id' => $secondQuestionnaire->questionnaire_id,
            'question_type' => 'multiple_choice',
            'question_skill_type' => 'reading',
            'question_order' => 1,
            'question_text' => 'Second question',
            'correct_answer' => null,
        ]);
        $secondCorrect = $second->options()->create([
            'option_text' => 'Correct',
            'is_correct' => true,
            'option_order' => 1,
        ]);
        $second->options()->create([
            'option_text' => 'Wrong',
            'is_correct' => false,
            'option_order' => 2,
        ]);

        $this->actingAs($user, 'api')->postJson('/api/progress/attempts/batch', [
            'attempts' => [[
                'lesson_id' => $lesson->lesson_id,
                'question_id' => $first->question_id,
                'answer' => $first->correct_option_id,
            ]],
        ])->assertOk()
            ->assertJsonPath('attempts.0.passed', true)
            ->assertJsonPath('xp_awarded', 0);

        $this->assertDatabaseMissing('student_progress', [
            'student_id' => $user->user_id,
            'lesson_id' => $lesson->lesson_id,
        ]);

        $this->actingAs($user, 'api')->postJson('/api/progress/attempts/batch', [
            'attempts' => [[
                'lesson_id' => $lesson->lesson_id,
                'question_id' => $second->question_id,
                'answer' => $secondCorrect->option_id,
            ]],
        ])->assertOk()
            ->assertJsonPath('attempts.0.passed', true)
            ->assertJsonPath('xp_awarded', 50)
            ->assertJsonPath('total_xp', 50);

        $this->assertDatabaseHas('student_progress', [
            'student_id' => $user->user_id,
            'lesson_id' => $lesson->lesson_id,
            'student_skill_type' => 'reading',
        ]);
        $this->assertDatabaseHas('attempt_logs', [
            'attempt_skill_type' => 'reading',
            'questionnaire_id' => $secondQuestionnaire->questionnaire_id,
            'passed' => true,
        ]);
    }

    /**
     * @return array{Lesson, array<int, Question>}
     */
    private function lessonWithQuestions(int $count, string $level = 'A1'): array
    {
        $lesson = Lesson::create([
            'lesson_cefr_level' => $level,
            'lesson_sub_level' => 1,
            'lesson_prompt_payload' => ['topic' => 'Security'],
        ]);
        $questionnaire = Questionnaire::create([
            'lesson_id' => $lesson->lesson_id,
            'title' => 'Security questionnaire',
        ]);
        $questions = [];

        for ($index = 0; $index < $count; $index++) {
            $question = Question::create([
                'questionnaire_id' => $questionnaire->questionnaire_id,
                'question_type' => 'multiple_choice',
                'question_skill_type' => 'reading',
                'question_order' => $index,
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
            $question->setAttribute('correct_option_id', $correct->option_id);
            $question->setAttribute('wrong_option_id', $wrong->option_id);
            $questions[] = $question;
        }

        return [$lesson, $questions];
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
