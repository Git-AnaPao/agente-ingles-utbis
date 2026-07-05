<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProgress extends Model
{
    use HasUuids;

    protected $table = 'student_progress';
    protected $primaryKey = 'student_progress_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'student_id',
        'placement_test_id',
        'lesson_id',
        'student_cefr_level',
        'student_sub_level',
        'student_skill_type',
        'student_current_status',
        'student_xp_earned',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function placementTest(): BelongsTo
    {
        return $this->belongsTo(PlacementTest::class, 'placement_test_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }
}
