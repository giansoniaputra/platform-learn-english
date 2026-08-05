<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Word extends Model
{
    protected $fillable = [
        'keyword_id',
        'en',
        'ipa',
        'pos',
        'translation',
        'example',
        'example_translation',
        'verb1',
        'verb2',
        'verb3',
    ];

    public function keyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class);
    }

    /**
     * Shape used by the frontend JS (WORDS array).
     */
    public function toApp(): array
    {
        return [
            'en' => $this->en,
            'ipa' => $this->ipa,
            'pos' => $this->pos,
            'id' => $this->translation,
            'ex' => $this->example,
            'ex_id' => $this->example_translation,
            'verb1' => $this->verb1,
            'verb2' => $this->verb2,
            'verb3' => $this->verb3,
        ];
    }
}
