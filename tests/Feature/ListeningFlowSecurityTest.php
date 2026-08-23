<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\ListeningLesson;
use App\Models\PlacementTest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ListeningFlowSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_locked_listening_content_is_forbidden_on_show_check_and_audio(): void
    {
        $student = $this->makeStudent('A1');
        $lesson = Lesson::create([
            'lesson_cefr_level' => 'B1',
            'lesson_sub_level' => 1,
            'lesson_prompt_payload' => ['topic' => 'Locked'],
        ]);
        $content = ListeningLesson::create([
            'lesson_id' => $lesson->lesson_id,
            'cefr_level' => 'B1',
            'sub_level' => 1,
            'title' => 'Locked listening',
            'questions_data' => [['number' => 1, 'text' => 'Question']],
            'answers_data' => [1 => 'answer'],
            'audio_drive_file_id' => 'locked-file',
        ]);

        $this->actingAs($student)->get(route('listening.show', $content))->assertForbidden();
        $this->actingAs($student)->postJson(route('listening.check', $content), [
            'answers' => [1 => 'answer'],
        ])->assertForbidden();
        $this->actingAs($student)->get(route('listening.audio', $content))->assertForbidden();
        $this->assertDatabaseCount('attempt_logs', 0);
    }

    public function test_incomplete_listening_evaluation_creates_no_attempt(): void
    {
        $student = $this->makeStudent('A1');
        $lesson = Lesson::create([
            'lesson_cefr_level' => 'A1',
            'lesson_sub_level' => 1,
            'lesson_prompt_payload' => ['topic' => 'Listening'],
        ]);
        $content = ListeningLesson::create([
            'lesson_id' => $lesson->lesson_id,
            'cefr_level' => 'A1',
            'sub_level' => 1,
            'title' => 'Two questions',
            'questions_data' => [
                ['number' => 1, 'text' => 'First'],
                ['number' => 2, 'text' => 'Second'],
            ],
            'answers_data' => [1 => 'one', 2 => 'two'],
        ]);

        $this->actingAs($student)->postJson(route('listening.check', $content), [
            'answers' => [1 => 'one'],
        ])->assertUnprocessable()->assertJsonValidationErrors('answers');

        $this->assertDatabaseCount('attempt_logs', 0);
        $this->assertDatabaseCount('student_progress', 0);
    }

    public function test_audio_proxy_forwards_and_returns_a_single_byte_range(): void
    {
        $student = $this->makeStudent('A1');
        $lesson = Lesson::create([
            'lesson_cefr_level' => 'A1',
            'lesson_sub_level' => 1,
            'lesson_prompt_payload' => ['topic' => 'Audio'],
        ]);
        $content = ListeningLesson::create([
            'lesson_id' => $lesson->lesson_id,
            'cefr_level' => 'A1',
            'sub_level' => 1,
            'title' => 'Ranged audio',
            'audio_drive_file_id' => 'drive-file',
        ]);

        Http::fake(function (ClientRequest $request) {
            if (str_contains($request->url(), 'fields=')) {
                return Http::response(['mimeType' => 'audio/mpeg', 'size' => '10']);
            }

            return Http::response('0123', 206, [
                'Content-Type' => 'audio/mpeg',
                'Content-Length' => '4',
                'Content-Range' => 'bytes 0-3/10',
            ]);
        });

        $response = $this->actingAs($student)
            ->withHeader('Range', 'bytes=0-3')
            ->get(route('listening.audio', $content));

        $response->assertStatus(206)
            ->assertHeader('Accept-Ranges', 'bytes')
            ->assertHeader('Content-Range', 'bytes 0-3/10');
        Http::assertSent(fn (ClientRequest $request): bool => str_contains($request->url(), 'alt=media')
            && $request->hasHeader('Range', 'bytes=0-3'));
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
}
