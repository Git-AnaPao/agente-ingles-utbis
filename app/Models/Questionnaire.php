<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Questionnaire extends Model
{
    use HasUuids;

    protected $table = 'questionnaire';
    protected $primaryKey = 'questionnaire_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'resource_id',
        'question_type',
        'question_text',
        'correct_answer',
        'question_order',
    ];

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class, 'resource_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionnaireOption::class, 'questionnaire_id');
    }
}
