<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LevelController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $lessons = Lesson::orderBy('lesson_cefr_level')->orderBy('lesson_sub_level')->get()
            ->groupBy('lesson_cefr_level');

        $cefrOrder = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
        $levels = [];

        foreach ($cefrOrder as $level) {
            $levelLessons = $lessons->get($level, collect());
            $completedCount = 0;

            if ($user) {
                $completedCount = $user->progress()
                    ->where('student_cefr_level', $level)
                    ->count();
            }

            $levels[] = [
                'cefr' => $level,
                'lessons' => $levelLessons,
                'total' => $levelLessons->count(),
                'completed' => $completedCount,
            ];
        }

        return view('levels.index', compact('levels'));
    }

    public function complete(Lesson $lesson)
    {
        $user = Auth::user();

        $user->progress()->updateOrCreate(
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

        return redirect()->route('levels.index')
            ->with('success', "Lección completada!");
    }
}
