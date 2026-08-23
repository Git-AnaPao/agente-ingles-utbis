<?php

namespace Tests\Feature;

use App\Models\AttemptLog;
use App\Models\Lesson;
use App\Models\Role;
use App\Models\StudentProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfessorDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_professor_dashboard_paginates_and_searches_institutional_students(): void
    {
        $professor = $this->userWithRole('professor');

        foreach (range(1, 18) as $index) {
            $student = User::factory()->create([
                'user_name' => $index === 18 ? 'Objetivo' : "Alumno {$index}",
                'user_email' => "student{$index}@example.com",
            ]);
            $student->roles()->attach($this->role('student'));
        }

        $response = $this->actingAs($professor)->get(route('professor.dashboard'));

        $response->assertOk();
        $students = $response->viewData('students');
        $this->assertSame(18, $students->total());
        $this->assertCount(15, $students->items());

        $searchResponse = $this->actingAs($professor)->get(route('professor.dashboard', ['q' => 'Objetivo']));
        $searchResponse
            ->assertOk()
            ->assertSee('Objetivo')
            ->assertViewHas('students', fn ($paginator) => $paginator->total() === 1);
    }

    public function test_professor_metrics_use_xp_and_passed_attempts(): void
    {
        $professor = $this->userWithRole('professor');
        $student = $this->userWithRole('student', ['xp' => 240]);
        $lesson = Lesson::create([
            'lesson_cefr_level' => 'A1',
            'lesson_sub_level' => 1,
            'lesson_prompt_payload' => ['topic' => 'Introductions'],
        ]);
        StudentProgress::create([
            'student_id' => $student->getKey(),
            'lesson_id' => $lesson->getKey(),
            'student_cefr_level' => 'A1',
            'student_sub_level' => 1,
            'student_skill_type' => 'reading',
        ]);
        AttemptLog::create([
            'user_id' => $student->getKey(),
            'lesson_id' => $lesson->getKey(),
            'attempt_score' => 90,
            'passed' => true,
            'attempted_at' => now(),
        ]);
        AttemptLog::create([
            'user_id' => $student->getKey(),
            'lesson_id' => $lesson->getKey(),
            'attempt_score' => 40,
            'passed' => false,
            'attempted_at' => now(),
        ]);

        $response = $this->actingAs($professor)->get(route('professor.dashboard'));

        $response
            ->assertOk()
            ->assertViewHas('totalXp', 240)
            ->assertViewHas('approvalRate', 50.0)
            ->assertSee('Tasa de aprobación de intentos');

        $detailResponse = $this->actingAs($professor)->get(route('professor.student-progress', $student));
        $detailResponse
            ->assertOk()
            ->assertViewHas('approvalRate', 50.0)
            ->assertSee('240')
            ->assertSee('Tasa de aprobación');
    }

    private function userWithRole(string $roleName, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->roles()->attach($this->role($roleName));

        return $user;
    }

    private function role(string $name): Role
    {
        return Role::firstOrCreate(
            ['role_name' => $name],
            ['role_description' => ucfirst($name)],
        );
    }
}
