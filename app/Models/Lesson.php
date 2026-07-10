<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    use HasUuids;

    protected $table = 'lessons';
    protected $primaryKey = 'lesson_id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'lesson_cefr_level',
        'lesson_sub_level',
        'lesson_prompt_payload',
    ];

    protected function casts(): array
    {
        return [
            'lesson_prompt_payload' => 'array',
        ];
    }

    public function questionnaires(): HasMany
    {
        return $this->hasMany(Questionnaire::class, 'lesson_id');
    }

    public function resources(): HasMany
    {
        return $this->hasManyThrough(Resource::class, Questionnaire::class, 'lesson_id', 'questionnaire_id');
    }

    public function attemptLogs(): HasMany
    {
        return $this->hasMany(AttemptLog::class, 'lesson_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(StudentProgress::class, 'lesson_id');
    }
}
