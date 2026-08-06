<?php

namespace App\Services;

use App\Models\Keyword;
use App\Models\Word;
use Illuminate\Support\Collection;

class VocabularyGenerator
{
    public function __construct(private readonly OpenAiClient $openAi) {}

    /**
     * @return array{ok: true, words: Collection<int, Word>, usage: int}|array{ok: false, error: string, status: int}
     */
    public function generate(Keyword $keyword, int $count = 10): array
    {
        $existing = $keyword->words()->pluck('en')->all();

        $system = sprintf(
            <<<'PROMPT'
            Kamu adalah penyusun materi kosakata bahasa Inggris untuk pelajar Indonesia level CEFR B1.
            Buatkan %d kata/frasa bahasa Inggris yang relevan dengan tema "%s". Setiap kata sertakan:
            ucapan IPA (ipa), jenis kata dalam Bahasa Indonesia (pos, mis. "kata benda"/"kata kerja"/"kata sifat"/"frasa kerja"),
            terjemahan Bahasa Indonesia (translation), satu contoh kalimat bahasa Inggris (example), dan terjemahan
            Bahasa Indonesia dari contoh kalimat itu (example_translation).
            Kalau kata itu kata kerja, isi juga bentuk verb1 (bentuk dasar), verb2 (past tense), verb3 (past participle);
            kalau bukan kata kerja, biarkan verb1/verb2/verb3 bernilai null.
            Jangan mengulang kata-kata berikut yang sudah pernah dibuat untuk tema ini: %s
            PROMPT,
            $count,
            $keyword->term,
            $existing ? implode(', ', $existing) : '(belum ada)',
        );

        $schema = [
            'type' => 'object',
            'properties' => [
                'words' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'en' => ['type' => 'string'],
                            'ipa' => ['type' => 'string'],
                            'pos' => ['type' => 'string'],
                            'translation' => ['type' => 'string'],
                            'example' => ['type' => 'string'],
                            'example_translation' => ['type' => 'string'],
                            'verb1' => ['type' => ['string', 'null']],
                            'verb2' => ['type' => ['string', 'null']],
                            'verb3' => ['type' => ['string', 'null']],
                        ],
                        'required' => ['en', 'ipa', 'pos', 'translation', 'example', 'example_translation', 'verb1', 'verb2', 'verb3'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['words'],
            'additionalProperties' => false,
        ];

        $result = $this->openAi->generate(
            $system,
            [['role' => 'user', 'content' => "Buatkan {$count} kosakata baru untuk tema \"{$keyword->term}\"."]],
            $schema,
            'vocabulary_list',
            maxTokens: 2200,
        );

        if (! $result['ok']) {
            return $result;
        }

        $words = collect($result['data']['words'] ?? [])->map(fn (array $item) => Word::create([
            'keyword_id' => $keyword->id,
            'en' => $item['en'] ?? '',
            'ipa' => $item['ipa'] ?? null,
            'pos' => $item['pos'] ?? null,
            'translation' => $item['translation'] ?? '',
            'example' => $item['example'] ?? null,
            'example_translation' => $item['example_translation'] ?? null,
            'verb1' => $item['verb1'] ?? null,
            'verb2' => $item['verb2'] ?? null,
            'verb3' => $item['verb3'] ?? null,
        ]));

        return ['ok' => true, 'words' => $words, 'usage' => $result['usage']];
    }
}
