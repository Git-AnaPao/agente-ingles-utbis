<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Questionnaire extends Model
{
    use HasUuids;

    protected $table = 'questionnaires';
    protected $primaryKey = 'questionnaire_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'lesson_id',
        'title',
        'listening_lesson_id',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }

    public function listeningLesson(): BelongsTo
    {
        return $this->belongsTo(ListeningLesson::class, 'listening_lesson_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'questionnaire_id');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class, 'questionnaire_id');
    }
}
