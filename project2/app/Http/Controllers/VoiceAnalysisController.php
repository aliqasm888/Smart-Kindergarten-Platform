<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class VoiceAnalysisController extends Controller
{
//    public function analyze(Request $request)
//    {
//        // التحقق من وجود الملف
//        if (!$request->hasFile('audio')) {
//            return response()->json(['error' => 'لا يوجد ملف صوتي'], 400);
//        }
//
//        $audio = $request->file('audio');
//
//        $response = Http::attach(
//            'audio',
//            file_get_contents($audio->getRealPath()),
//            $audio->getClientOriginalName()
//        )->post('http://127.0.0.1:5000/transcribe');
//
//        if ($response->failed()) {
//            return response()->json(['error' => 'فشل الاتصال بخدمة الصوت', 'details' => $response->body()], 500);
//        }
//
//        $spoken_text = $response['text'];
//
//        // تحميل الكلمات الأصلية التي عُرضت (مثلاً من session أو قاعدة بيانات)
//        $originalWords = ['قطة', 'موز', 'كرة'];  // ← هذا مثال، غيّره حسب حالتك
//
//        // مقارنة الكلمات
//        $spokenWords = collect(explode(' ', $this->normalize($spoken_text)))->unique();
//        $expectedWords = collect($originalWords)->map(fn($w) => $this->normalize($w));
//
//        $correct = $spokenWords->intersect($expectedWords);
//        $missed = $expectedWords->diff($spokenWords);
//        $extra = $spokenWords->diff($expectedWords);
//
//        $accuracy = $expectedWords->count() > 0
//            ? round(($correct->count() / $expectedWords->count()) * 100, 2)
//            : 0;
//
//        return response()->json([
//            'spoken_text'    => $spoken_text,
//            'spoken_words'   => $spokenWords->values(),
//            'original_words' => $expectedWords->values(),
//            'correct'        => $correct->values(),
//            'missed'         => $missed->values(),
//            'extra'          => $extra->values(),
//            'accuracy'       => $accuracy
//        ]);
//    }
//
//    private function normalize($text)
//    {
//        $text = preg_replace('/[؟.,!،؛]/u', '', $text);
//        return mb_strtolower(trim($text), 'UTF-8');
//    }
}
