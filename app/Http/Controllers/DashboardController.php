<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $keywords = $request->user()->keywords()->orderBy('term')->get();

        $selected = null;

        if ($slug = $request->query('keyword')) {
            $selected = $keywords->firstWhere('slug', $slug);
        }

        $selected ??= $keywords->firstWhere('is_active', true) ?? $keywords->first();

        $exerciseCounts = $selected
            ? $selected->exercises()->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type')
            : collect();

        return view('dashboard.index', [
            'keywords' => $keywords,
            'selected' => $selected,
            'words' => $selected?->words()->orderBy('id')->get() ?? collect(),
            'topics' => $selected?->topics()->orderBy('id')->get() ?? collect(),
            'exerciseCounts' => $exerciseCounts,
            'tokenUsage' => $request->user()->isAdmin() ? null : [
                'used' => $request->user()->tokens_used,
                'limit' => $request->user()->token_limit,
            ],
        ]);
    }
}
