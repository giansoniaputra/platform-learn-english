<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SentenceCheck extends Model
{
    protected $fillable = [
        'input_hash',
        'input_text',
        'input_language',
        'output_en',
        'is_correct',
        'explanation',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
        ];
    }

    public static function hashFor(string $text, string $inputLanguage): string
    {
        return hash('sha256', mb_strtolower(trim($text)).'|'.$inputLanguage);
    }

    public function toApp(): array
    {
        return [
            'output_en' => $this->output_en,
            'is_correct' => $this->is_correct,
            'explanation' => $this->explanation,
        ];
    }

    public function toHistoryApp(): array
    {
        return [
            'id' => $this->id,
            'input_text' => $this->input_text,
            'input_language' => $this->input_language,
            'output_en' => $this->output_en,
            'is_correct' => $this->is_correct,
            'explanation' => $this->explanation,
        ];
    }
}
