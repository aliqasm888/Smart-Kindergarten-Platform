<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ExpertSystemController extends Controller
{
    private $flask_url = "http://127.0.0.1:5000";
    // إذا السيرفر Flask منشور بـ ngrok حط رابط ngrok مكان localhost

    // 🔹 بدء جلسة جديدة
    public function startSession()
    {
        $response = Http::get($this->flask_url . '/start');

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to connect to Flask API'], 500);
        }

        return $response->json();
    }

    // 🔹 إرسال الإجابة
    public function sendAnswer(Request $request)
    {
        $response = Http::post($this->flask_url . '/answer', [
            'session_id' => $request->input('session_id'),
            'ident'      => $request->input('ident'),
            'answer'     => $request->input('answer'),
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to connect to Flask API'], 500);
        }

        return $response->json();
    }
}
