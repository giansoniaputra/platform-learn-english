<?php

namespace App\Http\Controllers;

use App\Models\Keyword;
use App\Models\Word;
use App\Services\VocabularyGenerator;
use Illuminate\Http\JsonResponse;

class WordController extends Controller
{
    public function __construct(private readonly VocabularyGenerator $vocabulary) {}

    public function generateMore(Keyword $keyword): JsonResponse
    {
        $result = $this->vocabulary->generate($keyword, 10);

        if (! $result['ok']) {
            return response()->json(['error' => $result['error']], $result['status']);
        }

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

    public function destroy(Word $word): JsonResponse
    {
        $word->delete();

        return response()->json(['ok' => true]);
    }
}
