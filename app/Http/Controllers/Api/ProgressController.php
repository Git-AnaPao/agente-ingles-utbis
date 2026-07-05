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
            ->where('student_current_status', 'completed')
            ->pluck('lesson_id')
            ->toArray();

        $totalXp = $user->progress()
            ->where('student_current_status', 'completed')
            ->sum('student_xp_earned');

        return response()->json([
            'levels' => $lessons,
            'completed_lesson_ids' => $completedLessonIds,
            'total_xp' => $totalXp,
        ]);
    }

    public function completeSublevel(Lesson $lesson): JsonResponse
    {
        $user = auth('api')->user();

        $progress = $user->progress()->updateOrCreate(
            [
                'student_id' => $user->user_id,
                'lesson_id' => $lesson->lesson_id,
                'student_cefr_level' => $lesson->lesson_cefr_level,
                'student_skill_type' => $lesson->lesson_skill_type,
            ],
            [
                'student_sub_level' => $lesson->lesson_sub_level,
                'student_current_status' => 'completed',
            ]
        );

        $topic = $lesson->lesson_prompt_payload['topic'] ?? 'Lección';

        return response()->json([
            'message' => "Lección '{$topic}' completada.",
            'xp_earned' => $progress->student_xp_earned,
            'total_xp' => $user->progress()->where('student_current_status', 'completed')->sum('student_xp_earned'),
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
            'student_id' => $user->user_id,
            'lesson_id' => $lesson->lesson_id,
            'exercise_type' => $request->exercise_type,
            'question' => $request->question,
            'user_answer' => $request->user_answer,
            'correct' => $request->correct,
            'time_spent' => $request->time_spent,
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
            'attempts.*.exercise_type' => 'required|string|max:30',
            'attempts.*.question' => 'required|string',
            'attempts.*.user_answer' => 'nullable|string',
            'attempts.*.correct' => 'required|boolean',
            'attempts.*.time_spent' => 'nullable|integer',
        ]);

        $user = auth('api')->user();
        $attempts = [];

        foreach ($request->attempts as $data) {
            $attempts[] = AttemptLog::create([
                'student_id' => $user->user_id,
                'lesson_id' => $data['lesson_id'],
                'exercise_type' => $data['exercise_type'],
                'question' => $data['question'],
                'user_answer' => $data['user_answer'],
                'correct' => $data['correct'],
                'time_spent' => $data['time_spent'],
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
        $correctAttempts = $user->attemptLogs()->where('correct', true)->count();
        $accuracy = $totalAttempts > 0 ? round(($correctAttempts / $totalAttempts) * 100, 1) : 0;

        $completedCount = $user->progress()
            ->where('student_current_status', 'completed')
            ->count();

        $totalXp = $user->progress()
            ->where('student_current_status', 'completed')
            ->sum('student_xp_earned');

        return response()->json([
            'total_xp' => $totalXp,
            'completed_lessons' => $completedCount,
            'total_attempts' => $totalAttempts,
            'correct_attempts' => $correctAttempts,
            'accuracy' => $accuracy,
        ]);
    }
}
