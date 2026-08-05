<?php

namespace App\Http\Controllers;

use App\Models\Keyword;
use App\Services\ConversationGenerator;
use App\Services\VocabularyGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class KeywordController extends Controller
{
    public function __construct(
        private readonly VocabularyGenerator $vocabulary,
        private readonly ConversationGenerator $conversation,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'term' => ['required', 'string', 'max:80'],
        ]);

        $keyword = Keyword::findOrCreateByTerm($validated['term']);

        if ($keyword->wasRecentlyCreated) {
            $wordsResult = $this->vocabulary->generate($keyword, 10);
            $topicResult = $this->conversation->generate($keyword);

            if (! Keyword::where('is_active', true)->exists()) {
                $keyword->update(['is_active' => true]);
            }

            $errors = collect([$wordsResult, $topicResult])
                ->filter(fn (array $r) => ! $r['ok'])
                ->pluck('error');

            if ($errors->isNotEmpty()) {
                return redirect()
                    ->route('dashboard', ['keyword' => $keyword->slug])
                    ->with('status', 'Kunci dibuat, tapi sebagian gagal digenerate: '.$errors->implode(' '));
            }
        }

        return redirect()->route('dashboard', ['keyword' => $keyword->slug]);
    }

    public function activate(Keyword $keyword): RedirectResponse
    {
        // Plain query-builder updates throughout — $keyword's in-memory
        // is_active may already read true, which would make Eloquent's
        // update() skip the column as "not dirty" and silently no-op.
        Keyword::query()->update(['is_active' => false]);
        Keyword::where('id', $keyword->id)->update(['is_active' => true]);

        return redirect()
            ->route('dashboard', ['keyword' => $keyword->slug])
            ->with('status', 'Kunci "'.$keyword->term.'" sekarang aktif di aplikasi.');
    }
}
