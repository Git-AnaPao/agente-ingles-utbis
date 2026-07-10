<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttemptLog;
use App\Models\Lesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    public function levels(): JsonResponse
    {
        $user = auth('api')->user();

        $lessons = Lesson::orderBy('lesson_cefr_level')->orderBy('lesson_sub_level')->get()
            ->groupBy('lesson_cefr_level');

        $completedLessonIds = $user->progress()
            ->pluck('lesson_id')
            ->toArray();

        return response()->json([
            'levels' => $lessons,
            'completed_lesson_ids' => $completedLessonIds,
            'total_xp' => $user->progress()->count(),
        ]);
    }

    public function completeSublevel(Lesson $lesson): JsonResponse
    {
        $user = auth('api')->user();

        $progress = $user->progress()->updateOrCreate(
            [
                'student_id' => $user->user_id,
                'lesson_id' => $lesson->lesson_id,
            ],
            [
                'student_cefr_level' => $lesson->lesson_cefr_level,
                'student_skill_type' => 'reading',
                'student_sub_level' => $lesson->lesson_sub_level,
            ]
        );

        $topic = $lesson->lesson_prompt_payload['topic'] ?? 'Lección';

        return response()->json([
            'message' => "Lección '{$topic}' completada.",
            'total_xp' => $user->progress()->count(),
        ]);
    }

    public function submitAttempt(Request $request, Lesson $lesson): JsonResponse
    {
        $request->validate([
            'exercise_type' => 'required|string|max:30',
            'question' => 'required|string',
            'user_answer' => 'nullable|string',
            'correct' => 'required|boolean',
            'time_spent' => 'nullable|integer',
        ]);

        $user = auth('api')->user();

        $attempt = AttemptLog::create([
            'user_id' => $user->user_id,
            'lesson_id' => $lesson->lesson_id,
            'attempt_score' => $request->correct ? 100 : 0,
            'passed' => $request->correct,
        ]);

        return response()->json([
            'attempt' => $attempt,
            'message' => $request->correct ? '¡Correcto!' : 'Respuesta incorrecta.',
        ]);
    }

    public function submitBatch(Request $request): JsonResponse
    {
        $request->validate([
            'attempts' => 'required|array',
            'attempts.*.lesson_id' => 'required|exists:lessons,lesson_id',
            'attempts.*.correct' => 'required|boolean',
        ]);

        $user = auth('api')->user();
        $attempts = [];

        foreach ($request->attempts as $data) {
            $attempts[] = AttemptLog::create([
                'user_id' => $user->user_id,
                'lesson_id' => $data['lesson_id'],
                'attempt_score' => $data['correct'] ? 100 : 0,
                'passed' => $data['correct'],
            ]);
        }

        return response()->json([
            'attempts' => $attempts,
            'count' => count($attempts),
        ]);
    }

    public function stats(): JsonResponse
    {
        $user = auth('api')->user();

        $totalAttempts = $user->attemptLogs()->count();
        $correctAttempts = $user->attemptLogs()->where('passed', true)->count();
        $accuracy = $totalAttempts > 0 ? round(($correctAttempts / $totalAttempts) * 100, 1) : 0;

        $completedCount = $user->progress()->count();

        return response()->json([
            'total_xp' => $completedCount,
            'completed_lessons' => $completedCount,
            'total_attempts' => $totalAttempts,
            'correct_attempts' => $correctAttempts,
            'accuracy' => $accuracy,
        ]);
    }
}
