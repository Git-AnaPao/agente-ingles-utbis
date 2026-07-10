<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlacementTest extends Model
{
    use HasUuids;

    protected $table = 'placement_tests';
    protected $primaryKey = 'placement_test_id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'result_level',
        'score',
        'correct_answers',
        'total_questions',
        'level_breakdown',
        'taken_at',
    ];

    protected function casts(): array
    {
        return [
            'taken_at' => 'datetime',
            'score' => 'decimal:2',
            'correct_answers' => 'integer',
            'total_questions' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(StudentProgress::class, 'placement_test_id');
    }
}
