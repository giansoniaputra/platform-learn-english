<?php

namespace App\Http\Controllers;

use App\Models\Keyword;
use App\Models\Topic;
use App\Services\ConversationGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TopicController extends Controller
{
    public function __construct(private readonly ConversationGenerator $conversation) {}

    public function generateMore(Request $request, Keyword $keyword): JsonResponse
    {
        if (! $request->user()->hasTokenBudget()) {
            return response()->json(['error' => 'Batas token kamu sudah habis. Hubungi admin untuk menambah token.'], 403);
        }

        $result = $this->conversation->generate($keyword);

        if (! $result['ok']) {
            return response()->json(['error' => $result['error']], $result['status']);
        }

        $request->user()->recordTokenUsage($result['usage']);

        /** @var Topic $topic */
        $topic = $result['topic'];
        $caseNumber = $keyword->topics()->count();

        return response()->json([
            'topic' => [
                'id' => $topic->id,
                ...$topic->toApp($caseNumber),
            ],
        ]);
    }

    public function destroy(Request $request, Topic $topic): JsonResponse
    {
        abort_unless($topic->keyword->user_id === $request->user()->id, 403);

        $topic->delete();

        return response()->json(['ok' => true]);
    }
}
