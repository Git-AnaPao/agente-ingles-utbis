<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ListeningLesson extends Model
{
    use HasUuids;

    protected $table = 'listening_lessons';
    protected $primaryKey = 'listening_lesson_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'lesson_id',
        'cefr_level',
        'sub_level',
        'title',
        'description',
        'reading_text',
        'listening_script',
        'speaking_text',
        'questions_data',
        'answers_data',
        'audio_drive_file_id',
        'audio_drive_url',
        'audio_local_path',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'questions_data' => 'array',
            'answers_data' => 'array',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }

    public function questionnaire(): HasOne
    {
        return $this->hasOne(Questionnaire::class, 'listening_lesson_id');
    }

    /**
     * Scope para filtrar por nivel CEFR.
     */
    public function scopeByLevel($query, string $level)
    {
        return $query->where('cefr_level', $level);
    }

    /**
     * Scope para filtrar por sub-nivel.
     */
    public function scopeBySubLevel($query, int $subLevel)
    {
        return $query->where('sub_level', $subLevel);
    }

    /**
     * Scope para ordenar por nivel y sub-nivel.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('cefr_level')->orderBy('sub_level')->orderBy('sort_order');
    }

    /**
     * Obtiene el identificador completo del nivel (ej: A1.1, A1.2).
     */
    public function getFullLevelAttribute(): string
    {
        return "{$this->cefr_level}.{$this->sub_level}";
    }

    /**
     * Obtiene la URL del audio.
     * Para archivos de Drive se usa el proxy del backend (más confiable que drive.google.com).
     */
    public function getAudioUrlAttribute(): ?string
    {
        if ($this->audio_local_path) {
            return asset('storage/' . $this->audio_local_path);
        }

        if ($this->audio_drive_file_id) {
            return route('listening.audio', $this);
        }

        if ($this->audio_drive_url) {
            return $this->audio_drive_url;
        }

        return null;
    }

    /**
     * Obtiene las preguntas formateadas.
     */
    public function getFormattedQuestionsAttribute(): array
    {
        if (!$this->questions_data) {
            return [];
        }

        return $this->questions_data;
    }
}
