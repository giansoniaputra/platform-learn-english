<?php

namespace App\Http\Controllers;

use App\Models\Keyword;
use App\Models\Word;
use App\Services\VocabularyGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WordController extends Controller
{
    public function __construct(private readonly VocabularyGenerator $vocabulary) {}

    public function generateMore(Request $request, Keyword $keyword): JsonResponse
    {
        if (! $request->user()->hasTokenBudget()) {
            return response()->json(['error' => 'Batas token kamu sudah habis. Hubungi admin untuk menambah token.'], 403);
        }

        $result = $this->vocabulary->generate($keyword, 10);

        if (! $result['ok']) {
            return response()->json(['error' => $result['error']], $result['status']);
        }

        $request->user()->recordTokenUsage($result['usage']);

        return response()->json([
            'words' => $result['words']->map(fn (Word $word) => [
                'id' => $word->id,
                'en' => $word->en,
                'ipa' => $word->ipa,
                'pos' => $word->pos,
                'translation' => $word->translation,
                'example' => $word->example,
                'example_translation' => $word->example_translation,
                'verb1' => $word->verb1,
                'verb2' => $word->verb2,
                'verb3' => $word->verb3,
            ])->values(),
        ]);
    }

    public function destroy(Request $request, Word $word): JsonResponse
    {
        abort_unless($word->keyword->user_id === $request->user()->id, 403);

        $word->delete();

        return response()->json(['ok' => true]);
    }
}
