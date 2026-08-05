<?php

namespace App\Http\Controllers;

use App\Models\Keyword;
use App\Models\Topic;
use App\Services\ConversationGenerator;
use Illuminate\Http\JsonResponse;

class TopicController extends Controller
{
    public function __construct(private readonly ConversationGenerator $conversation) {}

    public function generateMore(Keyword $keyword): JsonResponse
    {
        $result = $this->conversation->generate($keyword);

        if (! $result['ok']) {
            return response()->json(['error' => $result['error']], $result['status']);
        }

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

    public function destroy(Topic $topic): JsonResponse
    {
        $topic->delete();

        return response()->json(['ok' => true]);
    }
}
