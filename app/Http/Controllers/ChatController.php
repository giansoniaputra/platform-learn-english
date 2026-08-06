<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Models\Topic;
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
            'input_language' => ['nullable', 'in:en,id'],
            'history' => ['array', 'max:20'],
            'history.*.role' => ['required_with:history', 'in:user,assistant'],
            'history.*.text' => ['required_with:history', 'string', 'max:1000'],
            'topic_id' => ['required', 'integer', 'exists:topics,id'],
            'session_id' => ['nullable', 'integer', 'exists:chat_sessions,id'],
        ]);

        $topic = Topic::findOrFail($validated['topic_id']);
        abort_unless($topic->keyword->user_id === $request->user()->id, 403);

        if (! $request->user()->hasTokenBudget()) {
            return response()->json(['error' => 'Batas token kamu sudah habis. Hubungi admin untuk menambah token.'], 403);
        }

        $partner = $validated['partner'];
        $inputLanguageLabel = ($validated['input_language'] ?? 'en') === 'id' ? 'Bahasa Indonesia' : 'Bahasa Inggris';

        $system = <<<PROMPT
            Kamu berperan sebagai {$partner} dalam sebuah aplikasi belajar bahasa Inggris untuk penutur Bahasa Indonesia.
            Konteks latihan: "{$validated['title']}" — {$validated['blurb']}

            Pelajar mengetik pesannya (message) dalam {$inputLanguageLabel}.
            - Jika dalam Bahasa Indonesia: terjemahkan secara natural ke Bahasa Inggris sesuai konteks percakapan
              ini, jadikan message_en. Ini murni terjemahan bukan kesalahan, jadi correction WAJIB null.
            - Jika dalam Bahasa Inggris: perbaiki SEMUA kesalahan tata bahasa, bentuk/konjugasi kata kerja
              (tenses), dan pilihan kosakata yang kurang tepat menjadi message_en yang benar. Jika ada yang
              diperbaiki, isi correction dengan penjelasan APA yang salah dan MENGAPA harus diperbaiki, maksimal
              2 kalimat pendek. PENTING: correction WAJIB ditulis dalam Bahasa Indonesia (bukan Bahasa Inggris),
              supaya mudah dipahami pelajar. Jika pesan sudah benar tanpa perlu perbaikan apapun, correction
              bernilai null.

            Balaslah SELALU dalam Bahasa Inggris sebagai {$partner}, natural, level CEFR sekitar B1, 1-2 kalimat
            pendek saja supaya sesuai gaya bubble chat singkat di aplikasi — balasanmu merespons message_en
            (versi yang sudah benar), bukan pesan asli pelajar apa adanya. Tetap dalam peran dan jangan
            menyebut dirimu AI.

            Sertakan juga message_translation (terjemahan Bahasa Indonesia dari message_en) dan translation
            (terjemahan Bahasa Indonesia dari reply).
            PROMPT;

        $messages = collect($validated['history'] ?? [])
            ->map(fn (array $line) => ['role' => $line['role'], 'content' => $line['text']])
            ->push(['role' => 'user', 'content' => $validated['message']])
            ->values()
            ->all();

        $schema = [
            'type' => 'object',
            'properties' => [
                'message_en' => [
                    'type' => 'string',
                    'description' => 'Pesan pelajar dalam Bahasa Inggris yang benar — hasil terjemahan (jika pesan asli Bahasa Indonesia) atau hasil koreksi tata bahasa/kata kerja/kosakata (jika pesan asli Bahasa Inggris).',
                ],
                'message_translation' => [
                    'type' => 'string',
                    'description' => 'Terjemahan Bahasa Indonesia dari message_en.',
                ],
                'correction' => [
                    'type' => ['string', 'null'],
                    'description' => 'Penjelasan APA yang salah dan MENGAPA diperbaiki pada pesan asli (hanya jika pesan asli Bahasa Inggris dan ada kesalahan), atau null jika tidak ada koreksi. WAJIB dalam Bahasa Indonesia, tidak boleh dalam Bahasa Inggris.',
                ],
                'reply' => [
                    'type' => 'string',
                    'description' => 'Balasan dalam Bahasa Inggris, 1-2 kalimat, sesuai peran, merespons message_en.',
                ],
                'translation' => [
                    'type' => 'string',
                    'description' => 'Terjemahan Bahasa Indonesia dari reply.',
                ],
            ],
            'required' => ['message_en', 'message_translation', 'correction', 'reply', 'translation'],
            'additionalProperties' => false,
        ];

        $result = $this->openAi->generate($system, $messages, $schema, 'chat_reply', maxTokens: 400);

        if (! $result['ok']) {
            return response()->json(['error' => $result['error']], $result['status']);
        }

        $data = $result['data'];

        if (! isset($data['message_en'], $data['message_translation'], $data['reply'], $data['translation'])) {
            return response()->json(['error' => 'Format balasan AI tidak terbaca.'], 502);
        }

        $request->user()->recordTokenUsage($result['usage']);

        $session = ($validated['session_id'] ?? null)
            ? ChatSession::where('id', $validated['session_id'])->where('topic_id', $validated['topic_id'])->first()
            : null;

        $session ??= ChatSession::create(['topic_id' => $validated['topic_id']]);

        $session->messages()->create([
            'role' => 'user',
            'text' => $data['message_en'],
            'translation' => $data['message_translation'],
            'correction' => $data['correction'] ?? null,
        ]);

        $session->messages()->create([
            'role' => 'assistant',
            'text' => $data['reply'],
            'translation' => $data['translation'],
        ]);

        return response()->json([
            'session_id' => $session->id,
            'message_en' => $data['message_en'],
            'message_translation' => $data['message_translation'],
            'correction' => $data['correction'] ?? null,
            'reply' => $data['reply'],
            'translation' => $data['translation'],
        ]);
    }

    public function history(Request $request, Topic $topic): JsonResponse
    {
        abort_unless($topic->keyword->user_id === $request->user()->id, 403);

        $page = max(1, (int) $request->query('page', 1));
        $search = trim((string) $request->query('search', ''));

        $query = $topic->chatSessions()->with('messages')->orderByDesc('id');

        if ($search !== '') {
            $needle = '%'.mb_strtolower($search).'%';
            $query->whereHas('messages', function ($q) use ($needle) {
                $q->whereRaw('LOWER(text) LIKE ?', [$needle]);
            });
        }

        $paginator = $query->paginate(10, ['*'], 'page', $page);

        return response()->json([
            'data' => $paginator->getCollection()->map->toHistoryApp(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function showSession(Request $request, ChatSession $chatSession): JsonResponse
    {
        abort_unless($chatSession->topic->keyword->user_id === $request->user()->id, 403);

        return response()->json($chatSession->toFullApp());
    }
}
