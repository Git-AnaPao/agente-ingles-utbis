<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class ProfessorController extends Controller
{
    public function dashboard()
    {
        $studentRole = Role::where('role_name', 'student')->first();
        $students = User::whereHas('roles', fn($q) => $q->where('user_roles.role_id', $studentRole->role_id))
            ->withCount(['progress' => fn($q) => $q->where('student_current_status', 'completed')])
            ->orderBy('user_name')
            ->get();

        $totalStudents = $students->count();

        return view('professor.dashboard', compact('students', 'totalStudents'));
    }

    public function studentProgress(User $user)
    {
        if (!$user->isStudent()) {
            abort(404);
        }

        $progress = $user->progress()->with('lesson')->get();
        $lessons = Lesson::orderBy('lesson_cefr_level')->orderBy('lesson_sub_level')->get();
        $completedIds = $progress->where('student_current_status', 'completed')->pluck('lesson_id')->toArray();

        return view('professor.student-progress', compact('user', 'lessons', 'completedIds', 'progress'));
    }
}
