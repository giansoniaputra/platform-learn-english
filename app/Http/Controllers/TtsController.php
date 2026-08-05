<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TtsController extends Controller
{
    private const VOICE = 'nova';

    private const MODEL = 'gpt-4o-mini-tts';

    public function speak(Request $request): Response
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:500'],
        ]);

        $text = trim($validated['text']);
        $cacheKey = 'tts/'.self::VOICE.'/'.hash('sha256', $text).'.mp3';
        $disk = Storage::disk('local');

        if ($disk->exists($cacheKey)) {
            return $this->audioResponse($disk->get($cacheKey));
        }

        $apiKey = config('services.openai.api_key');

        if (! $apiKey) {
            return response('Server belum dikonfigurasi dengan OPENAI_KEY.', 500);
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post('https://api.openai.com/v1/audio/speech', [
                    'model' => self::MODEL,
                    'voice' => self::VOICE,
                    'input' => $text,
                    'instructions' => 'Speak clearly at a moderate, teacherly pace, as if helping an Indonesian learner practice English pronunciation.',
                    'response_format' => 'mp3',
                ]);
        } catch (ConnectionException $e) {
            Log::warning('OpenAI TTS connection error: '.$e->getMessage());

            return response('Tidak bisa terhubung ke server audio.', 502);
        }

        if ($response->failed()) {
            Log::warning('OpenAI TTS API error: '.$response->body());

            return response('Gagal membuat audio.', 502);
        }

        $audio = $response->body();

        $disk->put($cacheKey, $audio);

        return $this->audioResponse($audio);
    }

    private function audioResponse(string $audio): Response
    {
        return response($audio, 200, [
            'Content-Type' => 'audio/mpeg',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
