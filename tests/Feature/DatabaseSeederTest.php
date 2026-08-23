<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestionOption;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_and_placement_import_are_safe_to_run_repeatedly(): void
    {
        $this->seed();

        $lesson = Lesson::query()->where('lesson_cefr_level', 'A1')->where('lesson_sub_level', 1)->firstOrFail();
        $lesson->update(['lesson_prompt_payload' => ['topic' => 'Customized']]);

        $this->seed();

        $this->assertSame(3, Role::count());
        $this->assertSame(12, Lesson::count());
        $this->assertSame(['topic' => 'Customized'], $lesson->fresh()->lesson_prompt_payload);
        $this->assertSame(0, User::count());

        $this->artisan('import:placement-questions')->assertSuccessful();
        $this->artisan('import:placement-questions')->assertSuccessful();

        $questionnaire = Questionnaire::query()
            ->where('title', 'Placement Test')
            ->whereNull('lesson_id')
            ->firstOrFail();

        $this->assertSame(75, Question::where('questionnaire_id', $questionnaire->questionnaire_id)->count());
        $this->assertSame(300, QuestionOption::count());
    }
}
