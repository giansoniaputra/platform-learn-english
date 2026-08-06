<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Topic extends Model
{
    protected $fillable = [
        'keyword_id',
        'title',
        'blurb',
        'partner',
        'lines',
        'keys',
    ];

    protected function casts(): array
    {
        return [
            'lines' => 'array',
            'keys' => 'array',
        ];
    }

    public function keyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class);
    }

    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class);
    }

    /**
     * Shape used by the frontend JS (TOPICS array). `$caseNumber` is the
     * 1-based position of this topic within its keyword's list, used to
     * label it "Kasus N" since there's no more manual day/state scheduling.
     *
     * `lines` are stored as {me, en, id} objects (same shape the frontend
     * expects). `keys` are stored as {en, id} objects, same shape the AI
     * generation schema produces.
     */
    public function toApp(?int $caseNumber = null): array
    {
        return [
            'id' => 't'.$this->id,
            'day' => $caseNumber ? "Kasus {$caseNumber}" : null,
            'title' => $this->title,
            'blurb' => $this->blurb,
            'partner' => $this->partner,
            'lines' => $this->lines ?? [],
            'keys' => collect($this->keys ?? [])
                ->map(fn (array $k) => [$k['en'] ?? '', $k['id'] ?? ''])
                ->values()
                ->all(),
        ];
    }
}
