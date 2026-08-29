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

    public function test_lessons_unlock_sequentially_within_the_same_cefr_level(): void
    {
        $student = $this->makeStudent('A1');
        $unit = $this->makeUnit('A1');
        $lessonOne = $this->makeListeningLesson($unit, 1, ['reading', 'writing', 'listening']);
        $lessonTwo = $this->makeListeningLesson($unit, 2, ['reading', 'writing', 'listening']);

        $this->actingAs($student)->get(route('lessons.learn', $lessonOne))->assertOk();
        $this->actingAs($student)->get(route('lessons.learn', $lessonTwo))->assertForbidden();

        StudentProgress::masterListeningLessonSkill($student, $lessonOne, 'reading');
        StudentProgress::masterListeningLessonSkill($student, $lessonOne, 'writing');
        $this->actingAs($student)->get(route('lessons.learn', $lessonTwo))->assertForbidden();

        StudentProgress::masterListeningLessonSkill($student, $lessonOne, 'listening');
        $this->actingAs($student)->get(route('lessons.learn', $lessonTwo))->assertOk();
    }

    public function test_map_reflects_unlocked_current_and_locked_lessons(): void
    {
        $student = $this->makeStudent('A1');
        $unit = $this->makeUnit('A1');
        $lessonOne = $this->makeListeningLesson($unit, 1, ['reading']);
        $lessonTwo = $this->makeListeningLesson($unit, 2, ['reading']);

        $this->actingAs($student)
            ->get(route('levels.index'))
            ->assertOk()
            ->assertViewHas('levels', fn (array $levels): bool => $levels[0]['lessons'][0]['unlocked']
                && ! $levels[0]['lessons'][1]['unlocked']);

        StudentProgress::masterListeningLessonSkill($student, $lessonOne, 'reading');

        $this->actingAs($student)
            ->get(route('levels.index'))
            ->assertViewHas('levels', fn (array $levels): bool => $levels[0]['lessons'][1]['unlocked']);
    }

    public function test_reading_and_writing_are_graded_independently(): void
    {
        $student = $this->makeStudent('A1');
        $unit = $this->makeUnit('A1');
        $lesson = $this->makeListeningLesson($unit, 1, ['reading', 'writing']);
        $readingQuestion = $lesson->questionnaire->questions->firstWhere('question_skill_type', 'reading');
        $writingQuestion = $lesson->questionnaire->questions->firstWhere('question_skill_type', 'writing');

        $this->actingAs($student)->postJson(route('lessons.check-practice', $lesson), [
            'skill' => 'reading',
            'answers' => [$readingQuestion->question_id => 'answer'],
        ])->assertOk()->assertJsonPath('mastered_skills.0', 'reading');

        $this->assertDatabaseHas('student_progress', [
            'student_id' => $student->user_id,
            'listening_lesson_id' => $lesson->listening_lesson_id,
            'student_skill_type' => 'reading',
        ]);
        $this->assertDatabaseMissing('student_progress', [
            'student_id' => $student->user_id,
            'listening_lesson_id' => $lesson->listening_lesson_id,
            'student_skill_type' => 'writing',
        ]);

        $response = $this->actingAs($student)->postJson(route('lessons.check-practice', $lesson), [
            'skill' => 'writing',
            'answers' => [$writingQuestion->question_id => 'answer'],
        ])->assertOk();
        $this->assertEqualsCanonicalizing(['reading', 'writing'], $response->json('mastered_skills'));

        $this->assertDatabaseHas('student_progress', [
            'student_id' => $student->user_id,
            'listening_lesson_id' => $lesson->listening_lesson_id,
            'student_skill_type' => 'writing',
        ]);
    }

    public function test_lesson_requires_reading_writing_and_listening_but_speaking_stays_optional(): void
    {
        $student = $this->makeStudent('A1');
        $unit = $this->makeUnit('A1');
        $lesson = $this->makeListeningLesson($unit, 1, ['reading', 'writing', 'listening'], withSpeaking: true);

        $this->assertSame(['reading', 'writing', 'listening'], StudentProgress::requiredSkillsForListeningLesson($lesson));
        $this->assertContains('speaking', StudentProgress::availableSkillsForListeningLesson($lesson));

        StudentProgress::masterListeningLessonSkill($student, $lesson, 'reading');
        StudentProgress::masterListeningLessonSkill($student, $lesson, 'writing');
        $mastered = $student->progress()->where('listening_lesson_id', $lesson->listening_lesson_id)->pluck('student_skill_type')->all();
        $this->assertFalse(StudentProgress::listeningLessonIsComplete($lesson, $mastered));

        StudentProgress::masterListeningLessonSkill($student, $lesson, 'listening');
        $mastered = $student->progress()->where('listening_lesson_id', $lesson->listening_lesson_id)->pluck('student_skill_type')->all();
        $this->assertTrue(StudentProgress::listeningLessonIsComplete($lesson, $mastered));
    }

    public function test_learn_defaults_to_the_first_pending_skill_but_allows_free_navigation_within_the_lesson(): void
    {
        $student = $this->makeStudent('A1');
        $unit = $this->makeUnit('A1');
        $lesson = $this->makeListeningLesson($unit, 1, ['reading', 'writing', 'listening']);

        $this->actingAs($student)
            ->get(route('lessons.learn', $lesson))
            ->assertOk()
            ->assertViewHas('activeTab', 'reading');

        // Skills inside the active lesson are freely navigable in any order.
        $this->actingAs($student)
            ->get(route('lessons.learn', ['listeningLesson' => $lesson, 'tab' => 'listening']))
            ->assertOk()
            ->assertViewHas('activeTab', 'listening');

        $this->actingAs($student)
            ->get(route('lessons.learn', ['listeningLesson' => $lesson, 'tab' => 'unknown']))
            ->assertOk()
            ->assertViewHas('activeTab', 'reading');
    }

    public function test_practice_check_requires_all_gradable_questions_to_be_answered(): void
    {
        $student = $this->makeStudent('A1');
        $unit = $this->makeUnit('A1');
        $lesson = $this->makeListeningLesson($unit, 1, ['reading']);

        $this->actingAs($student)->postJson(route('lessons.check-practice', $lesson), [
            'skill' => 'reading',
            'answers' => [],
        ])->assertUnprocessable();

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

    private function makeUnit(string $level, int $subLevel = 1): Lesson
    {
        return Lesson::create([
            'lesson_cefr_level' => $level,
            'lesson_sub_level' => $subLevel,
            'lesson_prompt_payload' => ['topic' => "{$level} topic"],
        ]);
    }

    /**
     * Mirrors the real import shape: one ListeningLesson per pedagogical
     * lesson, with a single questionnaire mixing all requested skills.
     *
     * @param  list<string>  $skills
     */
    private function makeListeningLesson(
        Lesson $unit,
        int $sortOrder,
        array $skills,
        bool $withSpeaking = false,
    ): ListeningLesson {
        $listeningLesson = ListeningLesson::create([
            'lesson_id' => $unit->lesson_id,
            'cefr_level' => $unit->lesson_cefr_level,
            'sub_level' => $unit->lesson_sub_level,
            'title' => "Lesson #{$sortOrder}",
            'reading_text' => in_array('reading', $skills, true) ? 'Read this text.' : null,
            'listening_script' => in_array('listening', $skills, true) ? 'Listen to this text.' : null,
            'speaking_text' => $withSpeaking ? 'Say this text.' : null,
            'sort_order' => $sortOrder,
        ]);

        $questionnaire = Questionnaire::create([
            'lesson_id' => $unit->lesson_id,
            'listening_lesson_id' => $listeningLesson->listening_lesson_id,
            'title' => "Lesson #{$sortOrder} questionnaire",
        ]);

        foreach ($skills as $skill) {
            Question::create([
                'questionnaire_id' => $questionnaire->questionnaire_id,
                'question_type' => 'fill_blank',
                'question_skill_type' => $skill,
                'question_order' => 1,
                'question_text' => "Write the {$skill} answer.",
                'correct_answer' => 'answer',
            ]);
        }

        $listeningLesson->setRelation('questionnaire', $questionnaire->load('questions.options'));

        return $listeningLesson;
    }
}
