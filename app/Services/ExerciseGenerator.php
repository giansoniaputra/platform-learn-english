<?php

namespace App\Services;

use App\Models\Exercise;
use App\Models\Keyword;
use App\Models\Word;
use Illuminate\Support\Collection;

class ExerciseGenerator
{
    /**
     * Derive exercises for every word in the keyword that doesn't already
     * have one, per type. Pure PHP — no OpenAI call, since everything needed
     * (example sentences, translations, sibling words for distractors) is
     * already sitting in the `words` table.
     *
     * @return array{arrange: int, fill_blank: int, match_meaning: int, listening: int, dictation: int}
     */
    public function generate(Keyword $keyword): array
    {
        $words = $keyword->words()->get();
        $existing = $keyword->exercises()->get(['word_id', 'type']);

        $hasExercise = fn (int $wordId, string $type) => $existing->contains(
            fn (Exercise $e) => $e->word_id === $wordId && $e->type === $type
        );

        $created = ['arrange' => 0, 'fill_blank' => 0, 'match_meaning' => 0, 'listening' => 0, 'dictation' => 0];
        $enoughForDistractors = $words->count() >= 4;

        foreach ($words as $word) {
            if (! $hasExercise($word->id, 'arrange') && $data = $this->buildArrange($word)) {
                $this->store($keyword, $word, 'arrange', $data);
                $created['arrange']++;
            }

            if (! $hasExercise($word->id, 'dictation') && $data = $this->buildDictation($word)) {
                $this->store($keyword, $word, 'dictation', $data);
                $created['dictation']++;
            }

            if (! $enoughForDistractors) {
                continue;
            }

            if (! $hasExercise($word->id, 'fill_blank') && $data = $this->buildFillBlank($word, $words)) {
                $this->store($keyword, $word, 'fill_blank', $data);
                $created['fill_blank']++;
            }

            if (! $hasExercise($word->id, 'match_meaning') && $data = $this->buildMatchMeaning($word, $words)) {
                $this->store($keyword, $word, 'match_meaning', $data);
                $created['match_meaning']++;
            }

            if (! $hasExercise($word->id, 'listening') && $data = $this->buildListening($word, $words)) {
                $this->store($keyword, $word, 'listening', $data);
                $created['listening']++;
            }
        }

        return $created;
    }

    private function store(Keyword $keyword, Word $word, string $type, array $data): void
    {
        Exercise::create([
            'keyword_id' => $keyword->id,
            'word_id' => $word->id,
            'type' => $type,
            'data' => $data,
        ]);
    }

    /**
     * Scramble the word's example sentence for the learner to reassemble.
     */
    private function buildArrange(Word $word): ?array
    {
        if (! $word->example) {
            return null;
        }

        $tokenCount = count(preg_split('/\s+/', trim($word->example)));

        if ($tokenCount < 3) {
            return null;
        }

        return [
            'sentence' => $word->example,
            'translation' => $word->example_translation,
        ];
    }

    /**
     * Blank out the target word inside its own example sentence, with 3
     * distractor words pulled from the same keyword's vocabulary list.
     */
    private function buildFillBlank(Word $word, Collection $words): ?array
    {
        if (! $word->example) {
            return null;
        }

        $pattern = '/\b'.preg_quote($word->en, '/').'\b/i';
        $blanked = preg_replace($pattern, '____', $word->example, 1);

        // The word doesn't appear verbatim in its own example (e.g. a
        // conjugated/plural form was used instead) — nothing to blank.
        if ($blanked === $word->example) {
            return null;
        }

        $distractors = $words
            ->where('id', '!=', $word->id)
            ->pluck('en')
            ->unique()
            ->shuffle()
            ->take(3)
            ->values();

        if ($distractors->count() < 3) {
            return null;
        }

        return [
            'sentence_with_blank' => $blanked,
            'answer' => $word->en,
            'options' => $distractors->push($word->en)->shuffle()->values()->all(),
            'translation' => $word->example_translation,
        ];
    }

    /**
     * English word → pick its correct Indonesian translation among 3
     * distractors pulled from sibling words in the same keyword.
     */
    private function buildMatchMeaning(Word $word, Collection $words): ?array
    {
        $distractors = $words
            ->where('id', '!=', $word->id)
            ->pluck('translation')
            ->unique()
            ->shuffle()
            ->take(3)
            ->values();

        if ($distractors->count() < 3) {
            return null;
        }

        return [
            'prompt' => $word->en,
            'answer' => $word->translation,
            'options' => $distractors->push($word->translation)->shuffle()->values()->all(),
        ];
    }

    /**
     * Audio-first comprehension: play the example sentence's TTS (already
     * cached from the vocabulary screen) and have the learner pick its
     * correct Indonesian meaning among 3 distractors — the English text is
     * never shown until after answering, so it actually tests listening.
     */
    private function buildListening(Word $word, Collection $words): ?array
    {
        if (! $word->example || ! $word->example_translation) {
            return null;
        }

        $distractors = $words
            ->where('id', '!=', $word->id)
            ->pluck('example_translation')
            ->filter()
            ->unique()
            ->shuffle()
            ->take(3)
            ->values();

        if ($distractors->count() < 3) {
            return null;
        }

        return [
            'sentence' => $word->example,
            'answer' => $word->example_translation,
            'options' => $distractors->push($word->example_translation)->shuffle()->values()->all(),
        ];
    }

    /**
     * Dictation: play the example sentence's TTS and have the learner type
     * back exactly what they heard — checked leniently (case/punctuation
     * insensitive) on the frontend. Same audio cache as every other type.
     */
    private function buildDictation(Word $word): ?array
    {
        if (! $word->example) {
            return null;
        }

        return [
            'sentence' => $word->example,
            'translation' => $word->example_translation,
        ];
    }
}
