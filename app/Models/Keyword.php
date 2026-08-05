<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Keyword extends Model
{
    protected $fillable = [
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

    public function words(): HasMany
    {
        return $this->hasMany(Word::class);
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class);
    }

    public static function findOrCreateByTerm(string $term): self
    {
        $slug = Str::slug($term);

        return static::firstOrCreate(
            ['slug' => $slug],
            ['term' => trim($term)],
        );
    }
}
