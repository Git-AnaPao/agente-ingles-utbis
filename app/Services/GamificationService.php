<?php

namespace App\Services;

use App\Models\StudentResponse;
use App\Models\User;
use Illuminate\Support\Carbon;

class GamificationService
{
    /** XP ganado al completar una lección de gramática por primera vez. */
    public const XP_LESSON_COMPLETE = 50;

    /** XP ganado al aprobar una lección de listening por primera vez. */
    public const XP_LISTENING_PASS = 30;

    /** XP ganado al dominar speaking por primera vez en una lección. */
    public const XP_SPEAKING_PASS = 40;

    /** XP necesarios por nivel de usuario (nivel N = N * XP_PER_LEVEL). */
    public const XP_PER_LEVEL = 200;

    /**
     * Otorga XP al usuario y devuelve el total acumulado.
     */
    public function awardXp(User $user, int $amount): int
    {
        $total = max(0, ($user->xp ?? 0) + $amount);
        $user->updateQuietly(['xp' => $total]);

        return $total;
    }

    /**
     * Nivel del usuario según su XP total.
     */
    public function levelForXp(int $xp): array
    {
        $level = intdiv(max(0, $xp), self::XP_PER_LEVEL) + 1;
        $current = $xp % self::XP_PER_LEVEL;
        $needed = self::XP_PER_LEVEL;

        return [
            'level' => $level,
            'current' => $current,
            'needed' => $needed,
            'progress' => round(($current / $needed) * 100),
        ];
    }

    /**
     * Registra una actividad del usuario y actualiza su racha.
     */
    public function recordActivity(User $user): void
    {
        $today = Carbon::today();
        $lastActivity = $user->last_activity_at;

        if ($lastActivity && Carbon::parse($lastActivity)->isSameDay($today)) {
            $user->updateQuietly(['last_activity_at' => now()]);

            return;
        }

        $newStreak = ($lastActivity && Carbon::parse($lastActivity)->isYesterday())
            ? $user->current_streak + 1
            : 1;

        $user->updateQuietly([
            'last_activity_at' => now(),
            'current_streak' => $newStreak,
            'longest_streak' => max($user->longest_streak ?? 0, $newStreak),
        ]);
    }

    /**
     * Cuenta las actividades (lecciones + intentos) de la semana actual.
     */
    public function weeklyActivityCount(User $user): int
    {
        $weekStart = Carbon::now()->startOfWeek();

        $progressCount = $user->progress()
            ->where('created_at', '>=', $weekStart)
            ->count();

        $attemptCount = $user->attemptLogs()
            ->where('attempted_at', '>=', $weekStart)
            ->count();

        return $progressCount + $attemptCount;
    }

    /**
     * Cuenta las respuestas verificadas del día de hoy (práctica diaria).
     */
    public function dailyResponsesCount(User $user): int
    {
        return StudentResponse::query()
            ->join('attempt_logs', 'attempt_logs.attempt_id', '=', 'student_responses.attempt_id')
            ->where('attempt_logs.user_id', $user->user_id)
            ->where('student_responses.created_at', '>=', Carbon::today())
            ->count();
    }

    /**
     * Datos completos de gamificación para vistas/API.
     */
    public function snapshot(User $user): array
    {
        return [
            'current_streak' => $user->current_streak,
            'longest_streak' => $user->longest_streak,
            'last_activity_at' => $user->last_activity_at,
            'weekly_activities' => $this->weeklyActivityCount($user),
            'daily_responses' => $this->dailyResponsesCount($user),
        ];
    }
}
