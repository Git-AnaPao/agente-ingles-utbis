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
        'lesson_skill_type',
        'lesson_prompt_payload',
    ];

    protected function casts(): array
    {
        return [
            'lesson_prompt_payload' => 'array',
        ];
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class, 'lesson_id');
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
