<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Keyword extends Model
{
    protected $fillable = [
        'user_id',
        'term',
        'slug',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Slug is only unique per-user now, so implicit route-model-binding must
     * be scoped to the authenticated user too — otherwise two users with a
     * keyword of the same slug would make binding ambiguous.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('user_id', auth()->id())
            ->first();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function words(): HasMany
    {
        return $this->hasMany(Word::class);
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class);
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class);
    }

    public static function findOrCreateByTerm(User $user, string $term): self
    {
        $slug = Str::slug($term);

        return static::firstOrCreate(
            ['user_id' => $user->id, 'slug' => $slug],
            ['term' => trim($term)],
        );
    }
}
