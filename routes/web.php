<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KeywordController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\TtsController;
use App\Http\Controllers\WordController;
use App\Models\Keyword;
use Illuminate\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store']);
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        $keyword = Keyword::where('is_active', true)->first();

        $topics = $keyword
            ? $keyword->topics()->orderBy('id')->get()
            : collect();

        return view('welcome', [
            'activeKeyword' => $keyword,
            'words' => $keyword ? $keyword->words()->orderBy('id')->get()->map->toApp() : collect(),
            'topics' => $topics->values()->map(fn ($topic, $i) => $topic->toApp($i + 1)),
        ]);
    })->name('app');

    Route::post('/api/percakapan/chat', [ChatController::class, 'reply'])->name('percakapan.chat');
    Route::post('/api/tts', [TtsController::class, 'speak'])->name('tts.speak');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/keywords', [KeywordController::class, 'store'])->name('dashboard.keywords.store');
    Route::post('/dashboard/keywords/{keyword}/activate', [KeywordController::class, 'activate'])->name('dashboard.keywords.activate');

    Route::post('/dashboard/keywords/{keyword}/words', [WordController::class, 'generateMore'])->name('dashboard.words.generate');
    Route::delete('/dashboard/words/{word}', [WordController::class, 'destroy'])->name('dashboard.words.destroy');

    Route::post('/dashboard/keywords/{keyword}/topics', [TopicController::class, 'generateMore'])->name('dashboard.topics.generate');
    Route::delete('/dashboard/topics/{topic}', [TopicController::class, 'destroy'])->name('dashboard.topics.destroy');
});
