<?php

namespace App\Http\Controllers;

use App\Models\MovieBreakdown;
use App\Services\OpenAiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MovieBreakdownController extends Controller
{
    public function __construct(private readonly OpenAiClient $openAi) {}

    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'movie_title' => ['required', 'string', 'max:150'],
            'scene_description' => ['required', 'string', 'max:300'],
        ]);

        $movieTitle = trim($validated['movie_title']);
        $sceneDescription = trim($validated['scene_description']);
        $hash = MovieBreakdown::hashFor($movieTitle, $sceneDescription);

        $cached = MovieBreakdown::where('input_hash', $hash)->first();

        if ($cached) {
            return response()->json($cached->toApp());
        }

        if (! $request->user()->hasTokenBudget()) {
            return response()->json(['error' => 'Batas token kamu sudah habis. Hubungi admin untuk menambah token.'], 403);
        }

        $system = <<<'PROMPT'
            Kamu adalah asisten belajar bahasa Inggris untuk penutur Bahasa Indonesia, ahli dalam film dan
            budaya pop berbahasa Inggris. Pelajar menyebutkan judul film dan deskripsi adegan tertentu.

            Tugasmu: rekonstruksi dialog adegan tersebut SEAKURAT MUNGKIN berdasarkan pengetahuanmu, ambil
            sekitar 6-12 baris paling representatif dari adegan itu (tidak perlu seluruh adegan kalau panjang),
            lalu bedah tiap baris untuk pembelajaran bahasa.

            PENTING: jika kamu TIDAK yakin atau tidak mengenali film atau adegan yang dimaksud, set
            recognized=false dan lines=[] — JANGAN mengarang dialog yang tidak berdasar sama sekali.

            Jika yakin (recognized=true), isi scene_summary (1-2 kalimat konteks adegan dalam Bahasa Indonesia),
            lalu untuk SETIAP baris dialog isi:
            - speaker: nama karakter yang bicara
            - en: baris dialog asli dalam Bahasa Inggris
            - translation: terjemahan Bahasa Indonesia
            - vocab_notes: penjelasan kosakata/idiom/slang penting di baris itu (Bahasa Indonesia), atau null
              kalau tidak ada yang perlu dijelaskan
            - grammar_notes: catatan struktur tata bahasa yang dipakai, terutama yang informal/tidak baku
              seperti gaya bicara natural di film, atau null kalau tidak ada yang menonjol
            - tone: nada bicara dan konteks emosi baris itu (Bahasa Indonesia), atau null kalau netral
            PROMPT;

        $schema = [
            'type' => 'object',
            'properties' => [
                'recognized' => [
                    'type' => 'boolean',
                    'description' => 'False kalau AI tidak yakin/tidak mengenali film atau adegannya.',
                ],
                'scene_summary' => [
                    'type' => ['string', 'null'],
                    'description' => 'Konteks singkat adegan dalam Bahasa Indonesia, null kalau recognized=false.',
                ],
                'lines' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'speaker' => ['type' => 'string'],
                            'en' => ['type' => 'string'],
                            'translation' => ['type' => 'string'],
                            'vocab_notes' => ['type' => ['string', 'null']],
                            'grammar_notes' => ['type' => ['string', 'null']],
                            'tone' => ['type' => ['string', 'null']],
                        ],
                        'required' => ['speaker', 'en', 'translation', 'vocab_notes', 'grammar_notes', 'tone'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['recognized', 'scene_summary', 'lines'],
            'additionalProperties' => false,
        ];

        $result = $this->openAi->generate(
            $system,
            [['role' => 'user', 'content' => "Film: {$movieTitle}\nAdegan: {$sceneDescription}"]],
            $schema,
            'movie_breakdown',
            maxTokens: 2400,
        );

        if (! $result['ok']) {
            return response()->json(['error' => $result['error']], $result['status']);
        }

        $data = $result['data'];

        if (! isset($data['recognized'])) {
            return response()->json(['error' => 'Format hasil AI tidak terbaca.'], 502);
        }

        $request->user()->recordTokenUsage($result['usage']);

        if (! $data['recognized']) {
            return response()->json([
                'error' => 'AI tidak yakin mengenali film atau adegan ini. Coba judul atau deskripsi yang lebih spesifik.',
            ], 422);
        }

        $record = MovieBreakdown::create([
            'user_id' => $request->user()->id,
            'input_hash' => $hash,
            'movie_title' => $movieTitle,
            'scene_description' => $sceneDescription,
            'scene_summary' => $data['scene_summary'] ?? null,
            'lines' => $data['lines'] ?? [],
        ]);

        return response()->json($record->toApp());
    }

    public function history(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $search = trim((string) $request->query('search', ''));

        $query = $request->user()->movieBreakdowns()->orderByDesc('id');

        if ($search !== '') {
            $needle = '%'.mb_strtolower($search).'%';
            $query->where(function ($q) use ($needle) {
                $q->whereRaw('LOWER(movie_title) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(scene_description) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(CAST(`lines` AS CHAR)) LIKE ?', [$needle]);
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

    public function show(Request $request, MovieBreakdown $movieBreakdown): JsonResponse
    {
        abort_unless($movieBreakdown->user_id === $request->user()->id, 403);

        return response()->json($movieBreakdown->toApp());
    }
}
