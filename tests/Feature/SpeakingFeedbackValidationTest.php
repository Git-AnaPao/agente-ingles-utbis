<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\ListeningLesson;
use App\Models\PlacementTest;
use App\Models\Role;
use App\Models\User;
use App\Contracts\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SpeakingFeedbackValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_audio_larger_than_limit(): void
    {
        [$student, $lesson, $content] = $this->speakingScenario();

        $this->actingAs($student)->postJson(
            route('lessons.speaking-feedback', [$lesson, $content]),
            ['audio_base64' => str_repeat('a', 4000001), 'mime_type' => 'audio/webm'],
        )->assertUnprocessable();
    }

    public function test_rejects_invalid_mime_and_invalid_base64(): void
    {
        [$student, $lesson, $content] = $this->speakingScenario();

        $this->actingAs($student)->postJson(
            route('lessons.speaking-feedback', [$lesson, $content]),
            ['audio_base64' => 'dGVzdA==', 'mime_type' => 'text/html'],
        )->assertUnprocessable();

        $gemini = Mockery::mock(AiProvider::class);
        $gemini->shouldReceive('isConfigured')->once()->andReturnTrue();
        $gemini->shouldNotReceive('evaluateSpeakingAudio');
        $this->app->instance(AiProvider::class, $gemini);

        $this->actingAs($student)->postJson(
            route('lessons.speaking-feedback', [$lesson, $content]),
            ['audio_base64' => 'not-base64!', 'mime_type' => 'audio/webm'],
        )->assertUnprocessable();
    }

    public function test_rejects_content_owned_by_another_lesson(): void
    {
        [$student, $lesson] = $this->speakingScenario();
        $otherLesson = Lesson::create([
            'lesson_cefr_level' => 'A1',
            'lesson_sub_level' => 2,
            'lesson_prompt_payload' => ['topic' => 'Other'],
        ]);
        $foreign = ListeningLesson::create([
            'lesson_id' => $otherLesson->lesson_id,
            'cefr_level' => 'A1',
            'sub_level' => 2,
            'title' => 'Foreign content',
            'speaking_text' => 'Hello',
        ]);

        $this->actingAs($student)->postJson(
            route('lessons.speaking-feedback', [$lesson, $foreign]),
            ['audio_base64' => 'dGVzdA==', 'mime_type' => 'audio/webm'],
        )->assertForbidden();
    }

    public function test_approved_speaking_persists_attempt_progress_and_xp_once(): void
    {
        [$student, $lesson, $content] = $this->speakingScenario();
        $gemini = Mockery::mock(AiProvider::class);
        $gemini->shouldReceive('isConfigured')->twice()->andReturnTrue();
        $gemini->shouldReceive('evaluateSpeakingAudio')->twice()->andReturn([
            'transcription' => 'Hello, my name is Ada.',
            'is_correct' => true,
            'feedback' => 'Clear pronunciation.',
        ]);
        $this->app->instance(AiProvider::class, $gemini);

        $payload = ['audio_base64' => 'dGVzdA==', 'mime_type' => 'audio/webm'];
        $this->actingAs($student)
            ->postJson(route('lessons.speaking-feedback', [$lesson, $content]), $payload)
            ->assertOk()
            ->assertJsonPath('evaluated', true)
            ->assertJsonPath('passed', true)
            ->assertJsonPath('xp_awarded', 40);

        $this->assertDatabaseHas('attempt_logs', [
            'user_id' => $student->user_id,
            'lesson_id' => $lesson->lesson_id,
            'passed' => true,
        ]);
        $this->assertDatabaseHas('student_progress', [
            'student_id' => $student->user_id,
            'lesson_id' => $lesson->lesson_id,
            'student_skill_type' => 'speaking',
        ]);
        $this->assertSame(40, $student->fresh()->xp);

        $this->actingAs($student)
            ->postJson(route('lessons.speaking-feedback', [$lesson, $content]), $payload)
            ->assertOk()
            ->assertJsonPath('xp_awarded', 0);
        $this->assertSame(40, $student->fresh()->xp);
        $this->assertDatabaseCount('attempt_logs', 2);
    }

    public function test_null_ai_result_is_not_recorded_as_failed(): void
    {
        [$student, $lesson, $content] = $this->speakingScenario();
        $gemini = Mockery::mock(AiProvider::class);
        $gemini->shouldReceive('isConfigured')->once()->andReturnTrue();
        $gemini->shouldReceive('evaluateSpeakingAudio')->once()->andReturn([
            'transcription' => '',
            'is_correct' => null,
            'feedback' => 'No se pudo evaluar.',
        ]);
        $this->app->instance(AiProvider::class, $gemini);

        $this->actingAs($student)
            ->postJson(route('lessons.speaking-feedback', [$lesson, $content]), [
                'audio_base64' => 'dGVzdA==',
                'mime_type' => 'audio/webm',
            ])
            ->assertOk()
            ->assertJsonPath('is_correct', null)
            ->assertJsonPath('evaluated', false)
            ->assertJsonPath('passed', null);

        $this->assertDatabaseCount('attempt_logs', 0);
        $this->assertDatabaseCount('student_progress', 0);
    }

    /**
     * @return array{User, Lesson, ListeningLesson}
     */
    private function speakingScenario(): array
    {
        $role = Role::firstOrCreate(
            ['role_name' => 'student'],
            ['role_description' => 'Student'],
        );
        $student = User::factory()->create();
        $student->roles()->attach($role->role_id);
        PlacementTest::create([
            'student_id' => $student->user_id,
            'result_level' => 'A1',
            'score' => 80,
        ]);
        $lesson = Lesson::create([
            'lesson_cefr_level' => 'A1',
            'lesson_sub_level' => 1,
            'lesson_prompt_payload' => ['topic' => 'Speaking'],
        ]);
        $content = ListeningLesson::create([
            'lesson_id' => $lesson->lesson_id,
            'cefr_level' => 'A1',
            'sub_level' => 1,
            'title' => 'Speaking practice',
            'speaking_text' => 'Hello, my name is Ada.',
        ]);

        return [$student, $lesson, $content];
    }
}
