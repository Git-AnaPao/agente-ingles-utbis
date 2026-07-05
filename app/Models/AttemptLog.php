<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttemptLog extends Model
{
    use HasUuids;

    protected $table = 'attempt_logs';
    protected $primaryKey = 'attempt_id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'lesson_id',
        'attempt_score',
        'ai_feedback',
        'passed',
        'attempted_at',
    ];

    protected function casts(): array
    {
        return [
            'attempt_score' => 'decimal:2',
            'passed' => 'boolean',
            'attempted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }
}
