<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $fillable = [
        'chat_session_id',
        'role',
        'text',
        'translation',
        'correction',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class, 'chat_session_id');
    }

    public function toApp(): array
    {
        return [
            'role' => $this->role,
            'text' => $this->text,
            'translation' => $this->translation,
            'correction' => $this->correction,
        ];
    }
}
