<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentResponse extends Model
{
    use HasUuids;

    protected $table = 'student_responses';
    protected $primaryKey = 'response_id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'attempt_id',
        'question_id',
        'student_answer_text',
        'is_correct',
        'ai_question_feedback',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(AttemptLog::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id');
    }
}
