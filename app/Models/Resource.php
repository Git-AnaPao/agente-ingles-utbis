<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resource extends Model
{
    use HasUuids;

    protected $table = 'resources';
    protected $primaryKey = 'resource_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'lesson_id',
        'resource_type',
        'resource_url',
        'resource_title',
        'resource_transcript',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Questionnaire::class, 'resource_id');
    }
}
