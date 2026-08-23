<?php

namespace App\Http\Controllers;

use App\Models\AttemptLog;
use App\Models\Lesson;
use App\Models\StudentProgress;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfessorController extends Controller
{
    public function dashboard(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $studentRole = fn ($query) => $query->where('role_name', 'student');
        $activeStudent = fn ($query) => $query
            ->where('user_status', 'active')
            ->whereHas('roles', $studentRole);
        $studentQuery = User::query()
            ->where('user_status', 'active')
            ->whereHas('roles', $studentRole);

        $totalStudents = (clone $studentQuery)->count();
        $lessons = Lesson::query()
            ->with([
                'listeningLessons.questionnaire.questions',
                'questionnaires.questions',
            ])
            ->orderBy('lesson_cefr_level')
            ->orderBy('lesson_sub_level')
            ->get();
        $totalLessons = $lessons->count();
        $totalXp = (int) (clone $studentQuery)->sum('xp');
        $progressByStudent = StudentProgress::whereHas('student', $activeStudent)
            ->get()
            ->groupBy('student_id');
        $totalCompleted = $progressByStudent->sum(
            fn ($progress): int => StudentProgress::completedLessonIds($lessons, $progress)->count(),
        );
        $attemptQuery = AttemptLog::whereHas('user', $activeStudent);
        $attemptCount = (clone $attemptQuery)->count();
        $passedAttemptCount = (clone $attemptQuery)->where('passed', true)->count();
        $approvalRate = $attemptCount > 0
            ? round(($passedAttemptCount / $attemptCount) * 100, 1)
            : null;

        if ($search !== '') {
            $studentQuery->where(function ($query) use ($search) {
                $query
                    ->where('user_name', 'like', "%{$search}%")
                    ->orWhere('user_last_name', 'like', "%{$search}%")
                    ->orWhere('user_middle_name', 'like', "%{$search}%")
                    ->orWhere('user_email', 'like', "%{$search}%");
            });
        }

        $students = $studentQuery
            ->with('progress')
            ->withCount([
                'attemptLogs as attempts_count',
                'attemptLogs as passed_attempts_count' => fn ($query) => $query->where('passed', true),
            ])
            ->orderBy('user_name')
            ->orderBy('user_last_name')
            ->paginate(15)
            ->withQueryString();

        $students->getCollection()->each(function (User $student) use ($lessons): void {
            $student->setAttribute(
                'completed_lessons_count',
                StudentProgress::completedLessonIds($lessons, $student->progress)->count(),
            );
        });

        return view('professor.dashboard', compact(
            'approvalRate',
            'search',
            'students',
            'totalCompleted',
            'totalLessons',
            'totalStudents',
            'totalXp',
        ));
    }

    public function studentProgress(User $user): View
    {
        if (! $user->isStudent() || $user->user_status !== 'active') {
            abort(404);
        }

        $user->loadCount([
            'attemptLogs',
            'attemptLogs as passed_attempts_count' => fn ($query) => $query->where('passed', true),
        ]);

        $progress = $user->progress()->with('lesson')->orderBy('created_at')->get();
        $lessons = Lesson::query()
            ->with([
                'listeningLessons.questionnaire.questions',
                'questionnaires.questions',
            ])
            ->orderBy('lesson_cefr_level')
            ->orderBy('lesson_sub_level')
            ->get();
        $completedIds = StudentProgress::completedLessonIds($lessons, $progress)->all();
        $completedCount = count($completedIds);
        $attemptCount = $user->attempt_logs_count;
        $approvalRate = $attemptCount > 0
            ? round(($user->passed_attempts_count / $attemptCount) * 100, 1)
            : null;
        $highestProgress = $progress
            ->filter(fn (StudentProgress $item): bool => in_array(
                strtoupper(trim((string) $item->student_cefr_level)),
                StudentProgress::CEFR_LEVELS,
                true,
            ))
            ->sortByDesc(function (StudentProgress $item): int {
                $levelIndex = array_search(
                    strtoupper(trim((string) $item->student_cefr_level)),
                    StudentProgress::CEFR_LEVELS,
                    true,
                );

                return ((int) $levelIndex * 1000) + (int) $item->student_sub_level;
            })
            ->first();
        $currentCefr = $highestProgress?->student_cefr_level
            ?? $user->placementTests()->latest('taken_at')->value('result_level');

        return view('professor.student-progress', compact(
            'approvalRate',
            'attemptCount',
            'completedCount',
            'completedIds',
            'currentCefr',
            'lessons',
            'progress',
            'user',
        ));
    }
}
