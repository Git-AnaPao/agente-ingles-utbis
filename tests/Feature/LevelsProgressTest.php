<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\ListeningLesson;
use App\Models\PlacementTest;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\Role;
use App\Models\StudentProgress;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LevelsProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_completion_route_is_not_exposed(): void
    {
        $this->assertFalse(Route::has('lessons.complete'));
    }

    public function test_progress_requires_deterministic_skills_but_speaking_is_optional_for_unlocking(): void
    {
        $student = $this->makeStudent('A1');
        $lesson = $this->makeLesson('A1');
        $this->makeQuestionnaireActivity($lesson, 'reading', 'Reading check');
        $this->makeQuestionnaireActivity($lesson, 'listening', 'Listening check');
        ListeningLesson::create([
            'lesson_id' => $lesson->lesson_id,
            'cefr_level' => 'A1',
            'sub_level' => 1,
            'title' => 'Integrated lesson',
            'reading_text' => 'Read this text.',
            'listening_script' => 'Listen to this text.',
            'speaking_text' => 'Say this text.',
        ]);

        StudentProgress::masterSkill($student, $lesson, 'reading');

        $this->assertSame(['reading', 'listening'], StudentProgress::requiredSkillsForLesson($lesson));
        $this->assertDatabaseCount('student_progress', 1);
        $this->actingAs($student)
            ->get(route('levels.index'))
            ->assertViewHas('levels', fn (array $levels): bool => ! $levels[0]['sub_levels'][0]['completed']);

        StudentProgress::masterSkill($student, $lesson, 'listening');

        $this->assertDatabaseCount('student_progress', 2);
        $this->actingAs($student)
            ->get(route('levels.index'))
            ->assertViewHas('levels', fn (array $levels): bool => $levels[0]['sub_levels'][0]['completed']);
    }

    public function test_passing_one_of_multiple_questionnaires_does_not_master_the_skill(): void
    {
        config(['services.gemini.api_key' => null]);
        $student = $this->makeStudent('A1');
        $lesson = $this->makeLesson('A1');
        [$firstQuestionnaire, $firstQuestion] = $this->makeQuestionnaireActivity(
            $lesson,
            'reading',
            'First reading activity',
        );
        [$secondQuestionnaire, $secondQuestion] = $this->makeQuestionnaireActivity(
            $lesson,
            'reading',
            'Second reading activity',
        );

        $this->actingAs($student)->postJson(route('lessons.check-practice', $lesson), [
            'questionnaire_id' => $firstQuestionnaire->questionnaire_id,
            'skill' => 'reading',
            'answers' => [$firstQuestion->question_id => 'answer'],
        ])->assertOk()
            ->assertJsonPath('passed', true)
            ->assertJsonPath('mastered_skills', [])
            ->assertJsonPath('xp_awarded', 0);

        $this->assertDatabaseMissing('student_progress', [
            'student_id' => $student->user_id,
            'lesson_id' => $lesson->lesson_id,
            'student_skill_type' => 'reading',
        ]);
        $this->assertDatabaseHas('attempt_logs', [
            'attempt_skill_type' => 'reading',
            'questionnaire_id' => $firstQuestionnaire->questionnaire_id,
            'passed' => true,
        ]);

        $this->actingAs($student)->postJson(route('lessons.check-practice', $lesson), [
            'questionnaire_id' => $secondQuestionnaire->questionnaire_id,
            'skill' => 'reading',
            'answers' => [$secondQuestion->question_id => 'answer'],
        ])->assertOk()
            ->assertJsonPath('mastered_skills.0', 'reading')
            ->assertJsonPath('xp_awarded', 50);

        $this->assertDatabaseHas('student_progress', [
            'student_id' => $student->user_id,
            'lesson_id' => $lesson->lesson_id,
            'student_skill_type' => 'reading',
        ]);
    }

    public function test_database_rejects_duplicate_progress_for_the_same_skill(): void
    {
        $student = $this->makeStudent('A1');
        $lesson = $this->makeLesson('A1');
        $attributes = [
            'student_id' => $student->user_id,
            'lesson_id' => $lesson->lesson_id,
            'student_cefr_level' => 'A1',
            'student_sub_level' => 1,
            'student_skill_type' => 'reading',
        ];

        StudentProgress::create($attributes);
        StudentProgress::create([...$attributes, 'student_skill_type' => 'listening']);
        $this->assertDatabaseCount('student_progress', 2);

        $this->expectException(QueryException::class);
        StudentProgress::create($attributes);
    }

    public function test_dashboard_counts_complete_lessons_and_continues_with_the_missing_skill(): void
    {
        $student = $this->makeStudent('A1');
        $lesson = $this->makeLesson('A1');
        ListeningLesson::create([
            'lesson_id' => $lesson->lesson_id,
            'cefr_level' => 'A1',
            'sub_level' => 1,
            'title' => 'Integrated lesson',
            'reading_text' => 'Read this.',
            'listening_script' => 'Listen to this.',
        ]);
        $this->makeQuestionnaireActivity($lesson, 'reading', 'Reading check');
        $this->makeQuestionnaireActivity($lesson, 'listening', 'Listening check');
        StudentProgress::masterSkill($student, $lesson, 'reading');

        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('completedCount', 0)
            ->assertViewHas('completionPct', 0)
            ->assertViewHas(
                'nextActivityUrl',
                route('lessons.learn', ['lesson' => $lesson, 'tab' => 'listening']),
            );

        StudentProgress::masterSkill($student, $lesson, 'listening');

        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertViewHas('completedCount', 1)
            ->assertViewHas('completionPct', 100);
    }

    public function test_placement_sets_entry_level_and_server_blocks_the_next_level(): void
    {
        $student = $this->makeStudent('B1');
        $this->makeLesson('A1');
        $this->makeLesson('A2');
        $entry = $this->makeLesson('B1');
        $locked = $this->makeLesson('B2');

        foreach ([$entry, $locked] as $lesson) {
            ListeningLesson::create([
                'lesson_id' => $lesson->lesson_id,
                'cefr_level' => $lesson->lesson_cefr_level,
                'sub_level' => 1,
                'title' => $lesson->lesson_cefr_level,
                'reading_text' => 'Required reading.',
            ]);
            $this->makeQuestionnaireActivity($lesson, 'reading', $lesson->lesson_cefr_level.' check');
        }

        $this->actingAs($student)->get(route('lessons.learn', $entry))->assertOk();
        $this->actingAs($student)->get(route('lessons.learn', $locked))->assertForbidden();
        $this->actingAs($student)->get(route('levels.index'))->assertSee('Punto de entrada');

        StudentProgress::masterSkill($student, $entry, 'reading');

        $this->actingAs($student)->get(route('lessons.learn', $locked))->assertOk();
    }

    public function test_learn_renders_real_content_objective_and_query_tab(): void
    {
        $student = $this->makeStudent('A1');
        $lesson = Lesson::create([
            'lesson_cefr_level' => 'A1',
            'lesson_sub_level' => 1,
            'lesson_prompt_payload' => [
                'topic' => 'Introductions',
                'prompt' => 'Introduce yourself clearly.',
            ],
        ]);
        ListeningLesson::create([
            'lesson_id' => $lesson->lesson_id,
            'cefr_level' => 'A1',
            'sub_level' => 1,
            'title' => 'Personal introductions',
            'reading_text' => 'My name is Ada.',
            'listening_script' => 'Listen to Ada.',
            'speaking_text' => 'My name is Ada.',
        ]);

        $this->actingAs($student)
            ->get(route('lessons.learn', ['lesson' => $lesson, 'tab' => 'speaking']))
            ->assertOk()
            ->assertSee('Personal introductions')
            ->assertSee('Introduce yourself clearly.')
            ->assertSee('My name is Ada.')
            ->assertSee('Speaking');
    }

    public function test_learn_selects_the_first_available_tab_when_tab_is_missing_or_invalid(): void
    {
        $student = $this->makeStudent('A1');
        $lesson = $this->makeLesson('A1');
        ListeningLesson::create([
            'lesson_id' => $lesson->lesson_id,
            'cefr_level' => 'A1',
            'sub_level' => 1,
            'title' => 'Listening only',
            'listening_script' => 'Listen carefully.',
        ]);

        $this->actingAs($student)
            ->get(route('lessons.learn', $lesson))
            ->assertOk()
            ->assertViewHas('activeTab', 'listening');
        $this->actingAs($student)
            ->get(route('lessons.learn', ['lesson' => $lesson, 'tab' => 'unknown']))
            ->assertOk()
            ->assertViewHas('activeTab', 'listening');
    }

    public function test_fallback_listening_is_evaluable_but_unsendable_speaking_is_not_required(): void
    {
        config(['services.gemini.api_key' => null]);
        $student = $this->makeStudent('A1');
        $lesson = $this->makeLesson('A1');
        $content = ListeningLesson::create([
            'lesson_id' => null,
            'cefr_level' => 'A1',
            'sub_level' => 1,
            'title' => 'Legacy fallback',
            'speaking_text' => 'Repeat this sentence.',
            'questions_data' => [['number' => 1, 'text' => 'Complete the phrase']],
            'answers_data' => [1 => 'hello'],
        ]);

        $this->assertSame(['listening'], StudentProgress::requiredSkillsForLesson($lesson));
        $this->assertContains('speaking', StudentProgress::availableSkillsForLesson($lesson));

        $this->actingAs($student)->postJson(route('listening.check', $content), [
            'answers' => [1 => 'hello'],
        ])->assertOk()
            ->assertJsonPath('passed', true)
            ->assertJsonPath('lesson_completed', true);

        $this->assertDatabaseHas('student_progress', [
            'student_id' => $student->user_id,
            'lesson_id' => $lesson->lesson_id,
            'student_skill_type' => 'listening',
        ]);
        $this->assertDatabaseMissing('student_progress', [
            'student_id' => $student->user_id,
            'lesson_id' => $lesson->lesson_id,
            'student_skill_type' => 'speaking',
        ]);
    }

    public function test_dashboard_uses_only_evaluable_lessons_at_or_above_placement(): void
    {
        $student = $this->makeStudent('B1');
        $lowerLesson = $this->makeLesson('A1');
        $this->makeQuestionnaireActivity($lowerLesson, 'reading', 'Lower activity');
        $textOnly = $this->makeLesson('B1');
        ListeningLesson::create([
            'lesson_id' => $textOnly->lesson_id,
            'cefr_level' => 'B1',
            'sub_level' => 1,
            'title' => 'Text only',
            'reading_text' => 'This has no evaluation.',
        ]);
        $evaluable = $this->makeLesson('B1', 2);
        $this->makeQuestionnaireActivity($evaluable, 'reading', 'Current activity');

        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('totalLessons', 1)
            ->assertViewHas('completedCount', 0)
            ->assertViewHas('completionPct', 0);

        StudentProgress::masterSkill($student, $evaluable, 'reading');

        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertViewHas('totalLessons', 1)
            ->assertViewHas('completedCount', 1)
            ->assertViewHas('completionPct', 100);
    }

    public function test_practice_questionnaire_must_belong_to_requested_lesson(): void
    {
        $student = $this->makeStudent('A1');
        $lesson = $this->makeLesson('A1');
        $otherLesson = $this->makeLesson('A1', 2);
        $questionnaire = Questionnaire::create([
            'lesson_id' => $otherLesson->lesson_id,
            'title' => 'Foreign questionnaire',
        ]);
        $question = Question::create([
            'questionnaire_id' => $questionnaire->questionnaire_id,
            'question_type' => 'fill_blank',
            'question_skill_type' => 'reading',
            'question_text' => 'Hello ___',
            'correct_answer' => 'world',
        ]);

        $this->actingAs($student)->postJson(route('lessons.check-practice', $lesson), [
            'questionnaire_id' => $questionnaire->questionnaire_id,
            'skill' => 'reading',
            'answers' => [$question->question_id => 'world'],
        ])->assertForbidden();

        $this->assertDatabaseCount('attempt_logs', 0);
    }

    private function makeStudent(string $placementLevel): User
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

    private function makeLesson(string $level, int $subLevel = 1): Lesson
    {
        return Lesson::create([
            'lesson_cefr_level' => $level,
            'lesson_sub_level' => $subLevel,
            'lesson_prompt_payload' => ['topic' => "{$level} topic"],
        ]);
    }

    /**
     * @return array{Questionnaire, Question}
     */
    private function makeQuestionnaireActivity(Lesson $lesson, string $skill, string $title): array
    {
        $questionnaire = Questionnaire::create([
            'lesson_id' => $lesson->lesson_id,
            'title' => $title,
        ]);
        $question = Question::create([
            'questionnaire_id' => $questionnaire->questionnaire_id,
            'question_type' => $skill === 'listening' ? 'listening' : 'fill_blank',
            'question_skill_type' => $skill,
            'question_order' => 1,
            'question_text' => 'Write answer.',
            'correct_answer' => 'answer',
        ]);

        return [$questionnaire, $question];
    }
}
