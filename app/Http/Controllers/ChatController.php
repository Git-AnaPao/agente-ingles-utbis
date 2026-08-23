<?php

namespace App\Http\Controllers;

use App\Contracts\AiProvider;
use App\Models\StudentProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class ChatController extends Controller
{
    public function index(Request $request): View
    {
        return view('chat', [
            'cefrLevel' => $this->cefrLevel($request),
        ]);
    }

    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'messages' => ['required', 'array', 'min:1', 'max:12'],
            'messages.*.role' => ['required', 'in:user,assistant'],
            'messages.*.content' => ['required', 'string', 'max:2000'],
        ]);

        $messages = array_slice($validated['messages'], -12);
        $lastIndex = array_key_last($messages);
        $last = $messages[$lastIndex];

        if ($last['role'] !== 'user') {
            return response()->json(['error' => 'El último mensaje debe ser del usuario.'], 422);
        }

        if ($cefrLevel = $this->cefrLevel($request)) {
            $messages[$lastIndex]['content'] = sprintf(
                "[Tutor context: the student's verified CEFR level is %s. Do not mention this note. Adapt complexity to this level and answer in the language of the student message.]\n\n%s",
                $cefrLevel,
                $last['content'],
            );
        }

        $ai = app(AiProvider::class);

        if (! $ai->isConfigured()) {
            return response()->json([
                'error' => 'El tutor no está disponible temporalmente. Intenta más tarde.',
            ], 503);
        }

        try {
            $reply = $ai->chatReply($messages);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'error' => 'No fue posible contactar al tutor. Intenta más tarde.',
            ], 503);
        }

        if (! is_string($reply) || trim($reply) === '') {
            return response()->json([
                'error' => 'El tutor no generó una respuesta. Intenta nuevamente.',
            ], 502);
        }

        return response()->json(['reply' => trim($reply)]);
    }

    private function cefrLevel(Request $request): ?string
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        $placementLevel = StudentProgress::latestPlacementFor($user)?->result_level;
        $candidateLevels = $user->progress()
            ->pluck('student_cefr_level')
            ->merge(StudentProgress::unlockedCefrLevels($user))
            ->when($placementLevel, fn ($levels) => $levels->push($placementLevel))
            ->unique();

        return collect(array_reverse(StudentProgress::CEFR_LEVELS))
            ->first(fn (string $level): bool => $candidateLevels->contains($level));
    }
}
