<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Exercise extends Model
{
    protected $fillable = [
        'keyword_id',
        'word_id',
        'type',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    public function keyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class);
    }

    public function word(): BelongsTo
    {
        return $this->belongsTo(Word::class);
    }

    /**
     * Shape used by the frontend JS (EXERCISES array).
     */
    public function toApp(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            ...$this->data,
        ];
    }
}
