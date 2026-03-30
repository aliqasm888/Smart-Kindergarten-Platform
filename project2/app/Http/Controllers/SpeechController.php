<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Http;

class SpeechController extends Controller
{
    protected $flaskUrl = 'http://127.0.0.1:5000';

    public function uploadAudio(Request $request)
    {
        if (!$request->hasFile('audio')) {
            return response()->json(['error' => 'لا يوجد ملف صوت'], 400);
        }

        $file = $request->file('audio');

        $response = Http::attach(
            'audio',
            file_get_contents($file),
            $file->getClientOriginalName()
        )->post("{$this->flaskUrl}/upload-audio");

        if (!$response->successful()) {
            return response()->json(['error' => 'فشل رفع الصوت إلى Flask'], 500);
        }

        return response()->json(['message' => 'تم رفع الصوت بنجاح']);
    }

    public function analyze()
    {
        $expected = session('expected_words', []);
        if (empty($expected)) return response()->json(['error' => 'لا توجد كلمات محفوظة'], 400);

        $response = Http::get("{$this->flaskUrl}/analyze");

        if (!$response->successful()) {
            return response()->json(['error' => 'فشل تحليل الصوت'], 500);
        }

        $data = $response->json();
        $spoken_text = $data['text'] ?? '';
        $spoken_words = $this->extractWords($spoken_text);

        $expected_clean = collect($expected)->map(fn($w) => $this->normalize($w))->toArray();
        $spoken_clean = collect($spoken_words)->map(fn($w) => $this->normalize($w))->toArray();

        $correct = array_values(array_intersect($expected_clean, $spoken_clean));
        $missed = array_values(array_diff($expected_clean, $spoken_clean));
        $extra = array_values(array_diff($spoken_clean, $expected_clean));
        $accuracy = count($expected_clean) > 0
            ? round(count($correct) / count($expected_clean) * 100, 2)
            : 0;

        return response()->json([
            'spoken_text' => $spoken_text,
            'spoken_words' => $spoken_words,
            'original_words' => $expected,
            'correct' => $correct,
            'missed' => $missed,
            'extra' => $extra,
            'accuracy' => $accuracy
        ]);
    }

    private function normalize($text)
    {
        return mb_strtolower(trim(preg_replace('/[؟.,!،؛]/u', '', $text)));
    }

    private function extractWords($text)
    {
        return preg_split('/\s+/', str_replace(['؟', '.', ',', '!', '،', '؛'], '', $text));
    }
}

