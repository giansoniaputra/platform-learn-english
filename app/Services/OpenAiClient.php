<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiClient
{
    /**
     * Call the OpenAI Chat Completions API with a strict JSON schema and
     * return the parsed response body.
     *
     * @return array{ok: true, data: array}|array{ok: false, error: string, status: int}
     */
    public function generate(
        string $system,
        array $messages,
        array $schema,
        string $schemaName,
        int $maxTokens = 1024,
    ): array {
        $apiKey = config('services.openai.api_key');

        if (! $apiKey) {
            return ['ok' => false, 'error' => 'Server belum dikonfigurasi dengan OPENAI_KEY.', 'status' => 500];
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(60)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [['role' => 'system', 'content' => $system], ...$messages],
                    'max_tokens' => $maxTokens,
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => [
                            'name' => $schemaName,
                            'strict' => true,
                            'schema' => $schema,
                        ],
                    ],
                ]);
        } catch (ConnectionException $e) {
            Log::warning('OpenAI connection error: '.$e->getMessage());

            return ['ok' => false, 'error' => 'Tidak bisa terhubung ke server AI. Coba lagi sebentar.', 'status' => 502];
        }

        if ($response->failed()) {
            Log::warning('OpenAI API error: '.$response->body());

            return ['ok' => false, 'error' => 'AI sedang tidak bisa merespons. Coba lagi sebentar.', 'status' => 502];
        }

        $choice = $response->json('choices.0');

        if (($choice['finish_reason'] ?? null) === 'content_filter') {
            return ['ok' => false, 'error' => 'AI menolak merespons permintaan ini.', 'status' => 422];
        }

        $text = $choice['message']['content'] ?? null;

        if ($text === null) {
            return ['ok' => false, 'error' => 'AI tidak mengembalikan hasil yang valid.', 'status' => 502];
        }

        $data = json_decode($text, true);

        if (! is_array($data)) {
            return ['ok' => false, 'error' => 'Format hasil AI tidak terbaca.', 'status' => 502];
        }

        return ['ok' => true, 'data' => $data];
    }
}
