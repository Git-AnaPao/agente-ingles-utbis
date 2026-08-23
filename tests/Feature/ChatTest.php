<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\PlacementTest;
use App\Models\StudentProgress;
use App\Models\User;
use App\Contracts\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_chat_rejects_more_than_twelve_messages(): void
    {
        $user = User::factory()->create();
        $messages = collect(range(1, 13))->map(fn (int $index) => [
            'role' => $index === 13 ? 'user' : ($index % 2 === 0 ? 'user' : 'assistant'),
            'content' => "Message {$index}",
        ])->all();

        $response = $this->actingAs($user)->postJson(route('chat.send'), [
            'messages' => $messages,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('messages');
    }

    public function test_chat_sends_at_most_twelve_messages_and_adds_known_cefr_context(): void
    {
        $user = User::factory()->create();
        PlacementTest::create([
            'student_id' => $user->getKey(),
            'result_level' => 'B1',
            'score' => 72,
            'taken_at' => now(),
        ]);

        $messages = collect(range(1, 12))->map(fn (int $index) => [
            'role' => $index % 2 === 0 ? 'user' : 'assistant',
            'content' => "Message {$index}",
        ])->all();

        $gemini = Mockery::mock(AiProvider::class);
        $gemini->shouldReceive('isConfigured')->once()->andReturnTrue();
        $gemini->shouldReceive('chatReply')
            ->once()
            ->with(Mockery::on(function (array $payload): bool {
                $lastMessage = $payload[array_key_last($payload)];

                return count($payload) <= 12
                    && $lastMessage['role'] === 'user'
                    && str_contains($lastMessage['content'], 'CEFR level is B1')
                    && str_contains($lastMessage['content'], 'Message 12');
            }))
            ->andReturn('Keep practicing.');
        $this->app->instance(AiProvider::class, $gemini);

        $response = $this->actingAs($user)->postJson(route('chat.send'), [
            'messages' => $messages,
        ]);

        $response
            ->assertOk()
            ->assertExactJson(['reply' => 'Keep practicing.']);
    }

    public function test_chat_requires_the_last_message_to_be_from_the_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('chat.send'), [
            'messages' => [[
                'role' => 'assistant',
                'content' => 'How can I help?',
            ]],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJson(['error' => 'El último mensaje debe ser del usuario.']);
    }

    public function test_chat_prefers_the_highest_mastered_level_over_placement(): void
    {
        $user = User::factory()->create();
        PlacementTest::create([
            'student_id' => $user->getKey(),
            'result_level' => 'A1',
            'score' => 80,
            'taken_at' => now(),
        ]);
        $lesson = Lesson::create([
            'lesson_cefr_level' => 'B2',
            'lesson_sub_level' => 1,
            'lesson_prompt_payload' => ['topic' => 'Advanced practice'],
        ]);
        StudentProgress::create([
            'student_id' => $user->getKey(),
            'lesson_id' => $lesson->lesson_id,
            'student_cefr_level' => 'B2',
            'student_sub_level' => 1,
            'student_skill_type' => 'reading',
        ]);

        $gemini = Mockery::mock(AiProvider::class);
        $gemini->shouldReceive('isConfigured')->once()->andReturnTrue();
        $gemini->shouldReceive('chatReply')
            ->once()
            ->with(Mockery::on(fn (array $messages): bool => str_contains(
                $messages[array_key_last($messages)]['content'],
                'CEFR level is B2',
            )))
            ->andReturn('Advanced reply.');
        $this->app->instance(AiProvider::class, $gemini);

        $this->actingAs($user)->postJson(route('chat.send'), [
            'messages' => [['role' => 'user', 'content' => 'Help me.']],
        ])->assertOk()->assertJsonPath('reply', 'Advanced reply.');
    }

    public function test_chat_returns_service_unavailable_when_the_tutor_is_not_configured(): void
    {
        $user = User::factory()->create();
        $gemini = Mockery::mock(AiProvider::class);
        $gemini->shouldReceive('isConfigured')->once()->andReturnFalse();
        $this->app->instance(AiProvider::class, $gemini);

        $response = $this->actingAs($user)->postJson(route('chat.send'), [
            'messages' => [[
                'role' => 'user',
                'content' => 'Help me practice.',
            ]],
        ]);

        $response
            ->assertServiceUnavailable()
            ->assertJsonStructure(['error']);
    }
}
