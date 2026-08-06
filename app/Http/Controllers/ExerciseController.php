<?php

namespace App\Http\Controllers;

use App\Models\Keyword;
use App\Services\ExerciseGenerator;
use Illuminate\Http\RedirectResponse;

class ExerciseController extends Controller
{
    public function __construct(private readonly ExerciseGenerator $generator) {}

    public function generate(Keyword $keyword): RedirectResponse
    {
        $created = $this->generator->generate($keyword);
        $total = array_sum($created);

        $status = $total > 0
            ? "Berhasil membuat {$total} latihan baru (susun kata: {$created['arrange']}, lengkapi kalimat: {$created['fill_blank']}, padankan arti: {$created['match_meaning']}, menyimak: {$created['listening']}, dikte: {$created['dictation']})."
            : 'Tidak ada latihan baru — semua kata sudah punya latihan, atau kosakata kunci ini belum cukup (minimal 4 kata untuk lengkapi kalimat, padankan arti & menyimak).';

        return redirect()
            ->route('dashboard', ['keyword' => $keyword->slug])
            ->with('status', $status);
    }
}
