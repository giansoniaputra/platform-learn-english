<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatSession extends Model
{
    protected $fillable = [
        'topic_id',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->orderBy('id');
    }

    public function toHistoryApp(): array
    {
        $first = $this->messages->first();

        return [
            'id' => $this->id,
            'preview' => $first?->text ?? '',
            'message_count' => $this->messages->count(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }

    public function toFullApp(): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at->toIso8601String(),
            'messages' => $this->messages->map->toApp()->values()->all(),
        ];
    }
}
