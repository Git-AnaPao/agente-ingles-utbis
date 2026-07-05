<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionnaireOption extends Model
{
    use HasUuids;

    protected $table = 'questionnaire_options';
    protected $primaryKey = 'option_id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'questionnaire_id',
        'option_text',
        'is_correct',
        'option_order',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Questionnaire::class, 'questionnaire_id');
    }
}
