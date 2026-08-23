<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\StudentProgress;
use App\Services\GamificationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        $user = Auth::user();

        // Redirigir según rol
        if ($user->role === 'professor') {
            return redirect()->route('professor.dashboard');
        }
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        // Redirigir al placement test si no lo ha completado
        if (! $user->placementTests()->exists()) {
            return redirect()->route('placement.index');
        }

        $lessons = Lesson::query()
            ->with([
                'listeningLessons.questionnaire.questions.options',
                'questionnaires.questions.options',
            ])
            ->orderBy('lesson_cefr_level')
            ->orderBy('lesson_sub_level')
            ->get();
        StudentProgress::prepareLessonsForProgress($lessons);
        $placement = StudentProgress::latestPlacementFor($user);
        $placementIndex = array_search(
            $placement?->result_level ?? StudentProgress::CEFR_LEVELS[0],
            StudentProgress::CEFR_LEVELS,
            true,
        );
        $placementIndex = $placementIndex === false ? 0 : $placementIndex;
        $lessons = $lessons
            ->filter(function (Lesson $lesson) use ($placementIndex): bool {
                $lessonIndex = array_search($lesson->lesson_cefr_level, StudentProgress::CEFR_LEVELS, true);

                return $lessonIndex !== false
                    && $lessonIndex >= $placementIndex
                    && StudentProgress::requiredSkillsForLesson($lesson) !== [];
            })
            ->values();
        $progress = $user->progress()->get();
        $completedLessonIds = StudentProgress::completedLessonIds($lessons, $progress);
        $completedCount = $completedLessonIds->count();
        $totalLessons = $lessons->count();
        $completionPct = $totalLessons > 0
            ? min(100, (int) round(($completedCount / $totalLessons) * 100))
            : 0;

        $nextLesson = null;
        foreach (array_reverse(StudentProgress::unlockedCefrLevels($user)) as $level) {
            $nextLesson = $lessons
                ->where('lesson_cefr_level', $level)
                ->first(fn (Lesson $lesson): bool => ! $completedLessonIds->contains($lesson->lesson_id)
                    && StudentProgress::requiredSkillsForLesson($lesson) !== []);

            if ($nextLesson) {
                break;
            }
        }

        $nextSkill = 'reading';
        if ($nextLesson) {
            $masteredSkills = $progress
                ->where('lesson_id', $nextLesson->lesson_id)
                ->pluck('student_skill_type');
            $nextSkill = collect(StudentProgress::requiredSkillsForLesson($nextLesson))
                ->first(fn (string $skill): bool => ! $masteredSkills->contains($skill))
                ?? 'reading';
        }

        $nextActivityUrl = $nextLesson
            ? route('lessons.learn', ['lesson' => $nextLesson, 'tab' => $nextSkill])
            : route('levels.index');
        $nextActivityName = $nextLesson
            ? ($nextLesson->lesson_prompt_payload['topic'] ?? "Nivel {$nextLesson->lesson_cefr_level}.{$nextLesson->lesson_sub_level}")
            : 'repasar el mapa de aprendizaje';
        $gamification = app(GamificationService::class)->snapshot($user);
        $levelInfo = app(GamificationService::class)->levelForXp($user->xp ?? 0);
        $streak = $gamification['current_streak'];
        $totalXp = $user->xp ?? 0;
        $userName = $user->name;
        $lastActivity = $user->last_activity_at
            ? Carbon::parse($user->last_activity_at)->diffForHumans()
            : null;

        return view('dashboard', compact(
            'completedCount',
            'totalLessons',
            'completionPct',
            'levelInfo',
            'streak',
            'lastActivity',
            'nextActivityName',
            'nextActivityUrl',
            'nextLesson',
            'totalXp',
            'userName',
        ));
    }
}
