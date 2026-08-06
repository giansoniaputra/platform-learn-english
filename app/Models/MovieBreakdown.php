<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieBreakdown extends Model
{
    protected $fillable = [
        'user_id',
        'input_hash',
        'movie_title',
        'scene_description',
        'scene_summary',
        'lines',
    ];

    protected function casts(): array
    {
        return [
            'lines' => 'array',
        ];
    }

    public static function hashFor(string $movieTitle, string $sceneDescription): string
    {
        return hash('sha256', mb_strtolower(trim($movieTitle)).'|'.mb_strtolower(trim($sceneDescription)));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toApp(): array
    {
        return [
            'id' => $this->id,
            'movie_title' => $this->movie_title,
            'scene_description' => $this->scene_description,
            'scene_summary' => $this->scene_summary,
            'lines' => $this->lines,
        ];
    }

    public function toHistoryApp(): array
    {
        return [
            'id' => $this->id,
            'movie_title' => $this->movie_title,
            'scene_description' => $this->scene_description,
        ];
    }
}
