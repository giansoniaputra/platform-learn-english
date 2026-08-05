<?php

namespace App\Http\Controllers;

use App\Services\OpenAiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(private readonly OpenAiClient $openAi) {}

    public function reply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'partner' => ['required', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:120'],
            'blurb' => ['nullable', 'string', 'max:300'],
            'message' => ['required', 'string', 'max:500'],
            'history' => ['array', 'max:20'],
            'history.*.role' => ['required_with:history', 'in:user,assistant'],
            'history.*.text' => ['required_with:history', 'string', 'max:1000'],
        ]);

        $system = sprintf(
            <<<'PROMPT'
            Kamu berperan sebagai %s dalam sebuah aplikasi belajar bahasa Inggris untuk penutur Bahasa Indonesia.
            Konteks latihan: "%s" — %s
            Balaslah SELALU dalam Bahasa Inggris, natural, level CEFR sekitar B1, 1-2 kalimat pendek saja
            supaya sesuai gaya bubble chat singkat di aplikasi. Tetap dalam peran dan jangan menyebut dirimu AI.
            Jika kalimat terakhir dari pelajar mengandung kesalahan tata bahasa atau kosakata yang jelas,
            beri koreksi singkat (maksimal satu kalimat, dalam Bahasa Indonesia, nada suportif). Jika tidak
            ada kesalahan berarti, biarkan correction bernilai null.
            PROMPT,
            $validated['partner'],
            $validated['title'],
            $validated['blurb'] ?? '',
        );

        $messages = collect($validated['history'] ?? [])
            ->map(fn (array $line) => ['role' => $line['role'], 'content' => $line['text']])
            ->push(['role' => 'user', 'content' => $validated['message']])
            ->values()
            ->all();

        $schema = [
            'type' => 'object',
            'properties' => [
                'reply' => [
                    'type' => 'string',
                    'description' => 'Balasan dalam Bahasa Inggris, 1-2 kalimat, sesuai peran.',
                ],
                'translation' => [
                    'type' => 'string',
                    'description' => 'Terjemahan Bahasa Indonesia dari reply.',
                ],
                'correction' => [
                    'type' => ['string', 'null'],
                    'description' => 'Koreksi singkat Bahasa Indonesia untuk kesalahan pada pesan pelajar, atau null jika tidak ada.',
                ],
            ],
            'required' => ['reply', 'translation', 'correction'],
            'additionalProperties' => false,
        ];

        $result = $this->openAi->generate($system, $messages, $schema, 'chat_reply', maxTokens: 400);

        if (! $result['ok']) {
            return response()->json(['error' => $result['error']], $result['status']);
        }

        $data = $result['data'];

        if (! isset($data['reply'], $data['translation'])) {
            return response()->json(['error' => 'Format balasan AI tidak terbaca.'], 502);
        }

        return response()->json([
            'reply' => $data['reply'],
            'translation' => $data['translation'],
            'correction' => $data['correction'] ?? null,
        ]);
    }
}
