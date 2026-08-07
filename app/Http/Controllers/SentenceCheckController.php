<?php

namespace App\Http\Controllers;

use App\Models\SentenceCheck;
use App\Services\OpenAiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SentenceCheckController extends Controller
{
    public function __construct(private readonly OpenAiClient $openAi) {}

    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:500'],
            'input_language' => ['required', 'in:en,id'],
        ]);

        $text = trim($validated['text']);
        $inputLanguage = $validated['input_language'];
        $hash = SentenceCheck::hashFor($text, $inputLanguage);

        $cached = SentenceCheck::where('input_hash', $hash)->first();

        if ($cached) {
            return response()->json($cached->toApp());
        }

        if (! $request->user()->hasTokenBudget()) {
            return response()->json(['error' => 'Batas token kamu sudah habis. Hubungi admin untuk menambah token.'], 403);
        }

        $inputLanguageLabel = $inputLanguage === 'id' ? 'Bahasa Indonesia' : 'Bahasa Inggris';

        $system = <<<PROMPT
            Kamu adalah asisten belajar bahasa Inggris untuk penutur Bahasa Indonesia. Pelajar mengetik satu
            kalimat bebas (bukan bagian dari skenario percakapan tertentu, berdiri sendiri) dalam {$inputLanguageLabel}.

            - Jika Bahasa Indonesia: terjemahkan ke Bahasa Inggris yang natural dan gramatikal, jadikan output_en.
              Ini murni terjemahan bukan koreksi, jadi is_correct WAJIB null.
            - Jika Bahasa Inggris: periksa apakah kalimat sudah benar secara tata bahasa.
              - Jika sudah benar: output_en = kalimat asli (boleh rapikan tanda baca/kapitalisasi kecil saja),
                is_correct = true.
              - Jika ada kesalahan tata bahasa, bentuk/konjugasi kata kerja, atau kosakata: output_en = versi
                yang sudah diperbaiki, is_correct = false.

            Isi juga translation: terjemahan Bahasa Indonesia dari output_en, supaya pelajar tahu artinya —
            WAJIB diisi baik untuk kasus terjemahan (input Bahasa Indonesia) maupun koreksi/konfirmasi
            (input Bahasa Inggris).

            Kemudian isi explanation: penjelasan singkat dalam Bahasa Indonesia yang mudah dipahami pelajar,
            membahas unsur tata bahasa penting pada output_en — kata kerja (verb) yang dipakai beserta
            bentuknya (present/past/dst), kata sifat, atau struktur kalimat lain yang relevan, supaya pelajar
            belajar dari situ. Jika is_correct = false, explanation WAJIB juga menjelaskan APA yang salah pada
            kalimat asli dan MENGAPA, beserta konteks penggunaan yang tepat. Maksimal 3-4 kalimat pendek.
            PROMPT;

        $schema = [
            'type' => 'object',
            'properties' => [
                'output_en' => [
                    'type' => 'string',
                    'description' => 'Kalimat Bahasa Inggris yang benar — hasil terjemahan (jika input Bahasa Indonesia) atau hasil koreksi/konfirmasi (jika input Bahasa Inggris).',
                ],
                'translation' => [
                    'type' => 'string',
                    'description' => 'Terjemahan Bahasa Indonesia dari output_en. WAJIB selalu diisi.',
                ],
                'is_correct' => [
                    'type' => ['boolean', 'null'],
                    'description' => 'Hanya diisi true/false jika input asli Bahasa Inggris (true jika sudah benar, false jika ada kesalahan). Null jika input Bahasa Indonesia.',
                ],
                'explanation' => [
                    'type' => 'string',
                    'description' => 'Penjelasan tata bahasa (verb, kata sifat, struktur) dalam Bahasa Indonesia, dan jika is_correct false juga jelaskan apa yang salah, mengapa, dan konteks yang tepat.',
                ],
            ],
            'required' => ['output_en', 'translation', 'is_correct', 'explanation'],
            'additionalProperties' => false,
        ];

        $result = $this->openAi->generate(
            $system,
            [['role' => 'user', 'content' => $text]],
            $schema,
            'sentence_check',
            maxTokens: 400,
        );

        if (! $result['ok']) {
            return response()->json(['error' => $result['error']], $result['status']);
        }

        $data = $result['data'];

        if (! isset($data['output_en'], $data['translation'], $data['explanation']) || ! array_key_exists('is_correct', $data)) {
            return response()->json(['error' => 'Format hasil AI tidak terbaca.'], 502);
        }

        $request->user()->recordTokenUsage($result['usage']);

        $record = SentenceCheck::create([
            'user_id' => $request->user()->id,
            'input_hash' => $hash,
            'input_text' => $text,
            'input_language' => $inputLanguage,
            'output_en' => $data['output_en'],
            'translation' => $data['translation'],
            'is_correct' => $data['is_correct'],
            'explanation' => $data['explanation'],
        ]);

        return response()->json($record->toApp());
    }

    public function history(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $search = trim((string) $request->query('search', ''));

        $query = $request->user()->sentenceChecks()->orderByDesc('id');

        if ($search !== '') {
            $needle = '%'.mb_strtolower($search).'%';
            $query->where(function ($q) use ($needle) {
                $q->whereRaw('LOWER(input_text) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(output_en) LIKE ?', [$needle]);
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
}
