<?php

namespace Tests\Feature;

use App\Models\PlacementTest;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlacementRetakeTest extends TestCase
{
    use RefreshDatabase;

    public function test_retake_is_post_only_and_preserves_previous_result(): void
    {
        $student = $this->makeStudent(true);

        $this->actingAs($student)->get('/placement/retake')->assertMethodNotAllowed();
        $this->actingAs($student)
            ->post(route('placement.retake'))
            ->assertRedirect(route('placement.index'))
            ->assertSessionHas('placement.retake', true);

        $this->assertSame(1, $student->placementTests()->count());
    }

    public function test_skip_is_post_only_and_creates_result_for_authenticated_owner(): void
    {
        $student = $this->makeStudent(false);
        $otherStudent = $this->makeStudent(false);

        $this->actingAs($student)->get('/placement/skip')->assertMethodNotAllowed();
        $this->actingAs($student)->post(route('placement.skip'), [
            'student_id' => $otherStudent->user_id,
        ])->assertRedirect(route('placement.index'));

        $this->assertDatabaseHas('placement_tests', [
            'student_id' => $student->user_id,
            'result_level' => 'A1',
            'total_questions' => 0,
        ]);
        $this->assertDatabaseMissing('placement_tests', ['student_id' => $otherStudent->user_id]);
    }

    public function test_submit_uses_prg_and_retake_appends_history(): void
    {
        $student = $this->makeStudent(false);
        [$firstQuestion, $firstCorrect, $firstWrong] = $this->placementQuestion(1);
        [$secondQuestion, $secondCorrect] = $this->placementQuestion(2);

        $this->actingAs($student)->post(route('placement.submit'), [
            'answers' => [
                $firstQuestion->question_id => $firstCorrect,
                $secondQuestion->question_id => $secondCorrect,
            ],
        ])->assertRedirect(route('placement.index'));

        $this->assertSame(1, $student->placementTests()->count());
        $this->actingAs($student)
            ->get(route('placement.index'))
            ->assertOk()
            ->assertSee('Resultado guardado');

        $this->actingAs($student)->post(route('placement.retake'));
        $this->actingAs($student)->post(route('placement.submit'), [
            'answers' => [
                $firstQuestion->question_id => $firstWrong,
                $secondQuestion->question_id => $secondCorrect,
            ],
        ])->assertRedirect(route('placement.index'));

        $this->assertSame(2, $student->placementTests()->count());
    }

    public function test_incomplete_submission_is_rejected_without_creating_result(): void
    {
        $student = $this->makeStudent(false);
        [$question, $correct] = $this->placementQuestion(1);
        $this->placementQuestion(2);

        $this->actingAs($student)
            ->from(route('placement.index'))
            ->post(route('placement.submit'), [
                'answers' => [$question->question_id => $correct],
            ])
            ->assertRedirect(route('placement.index'))
            ->assertSessionHasErrors('answers');

        $this->assertDatabaseCount('placement_tests', 0);
    }

    public function test_non_student_cannot_start_retake(): void
    {
        $role = Role::create(['role_name' => 'professor', 'role_description' => 'Professor']);
        $professor = User::factory()->create();
        $professor->roles()->attach($role->role_id);

        $this->actingAs($professor)
            ->post(route('placement.retake'))
            ->assertRedirect(route('dashboard'));
    }

    private function makeStudent(bool $withResult): User
    {
        $role = Role::firstOrCreate(
            ['role_name' => 'student'],
            ['role_description' => 'Student'],
        );
        $student = User::factory()->create();
        $student->roles()->attach($role->role_id);

        if ($withResult) {
            PlacementTest::create([
                'student_id' => $student->user_id,
                'result_level' => 'B1',
                'score' => 80,
            ]);
        }

        return $student;
    }

    /**
     * @return array{Question, string, string}
     */
    private function placementQuestion(int $order): array
    {
        $questionnaire = Questionnaire::firstOrCreate(
            ['title' => 'Placement Test', 'lesson_id' => null],
        );
        $question = Question::create([
            'questionnaire_id' => $questionnaire->questionnaire_id,
            'question_type' => 'multiple_choice',
            'question_skill_type' => 'reading',
            'question_order' => $order,
            'question_text' => "Question {$order}",
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

        return [$question, $correct->option_id, $wrong->option_id];
    }
}
