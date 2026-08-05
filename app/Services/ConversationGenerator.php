<?php

namespace App\Services;

use App\Models\Keyword;
use App\Models\Topic;

class ConversationGenerator
{
    public function __construct(private readonly OpenAiClient $openAi) {}

    /**
     * @return array{ok: true, topic: Topic}|array{ok: false, error: string, status: int}
     */
    public function generate(Keyword $keyword): array
    {
        $existingTitles = $keyword->topics()->pluck('title')->all();

        $system = sprintf(
            <<<'PROMPT'
            Kamu adalah penyusun materi latihan percakapan bahasa Inggris untuk pelajar Indonesia level CEFR B1.
            Buatkan SATU skenario percakapan realistis yang relevan dengan tema "%s". Sertakan:
            title (judul singkat dalam Bahasa Indonesia), blurb (satu kalimat deskripsi skenario dalam Bahasa Indonesia),
            partner (nama & peran lawan bicara, mis. "Rani (manajer)"),
            lines (6-10 baris dialog bergantian, tiap baris punya "me" [true kalau giliran pelajar bicara, false kalau
            lawan bicara], "en" [teks Inggris], "id" [terjemahan Indonesia]),
            keys (3-4 frasa kunci penting dari dialog, tiap item punya "en" [frasa Inggris] dan "id" [arti/catatan singkat]).
            Jangan membuat skenario dengan judul yang sama seperti yang sudah ada: %s
            PROMPT,
            $keyword->term,
            $existingTitles ? implode(', ', $existingTitles) : '(belum ada)',
        );

        $schema = [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string'],
                'blurb' => ['type' => 'string'],
                'partner' => ['type' => 'string'],
                'lines' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'me' => ['type' => 'boolean'],
                            'en' => ['type' => 'string'],
                            'id' => ['type' => 'string'],
                        ],
                        'required' => ['me', 'en', 'id'],
                        'additionalProperties' => false,
                    ],
                ],
                'keys' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'en' => ['type' => 'string'],
                            'id' => ['type' => 'string'],
                        ],
                        'required' => ['en', 'id'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['title', 'blurb', 'partner', 'lines', 'keys'],
            'additionalProperties' => false,
        ];

        $result = $this->openAi->generate(
            $system,
            [['role' => 'user', 'content' => "Buatkan satu skenario percakapan baru untuk tema \"{$keyword->term}\"."]],
            $schema,
            'conversation_case',
            maxTokens: 1800,
        );

        if (! $result['ok']) {
            return $result;
        }

        $data = $result['data'];

        $topic = Topic::create([
            'keyword_id' => $keyword->id,
            'title' => $data['title'] ?? 'Percakapan',
            'blurb' => $data['blurb'] ?? null,
            'partner' => $data['partner'] ?? null,
            'lines' => $data['lines'] ?? [],
            'keys' => $data['keys'] ?? [],
        ]);

        return ['ok' => true, 'topic' => $topic];
    }
}
