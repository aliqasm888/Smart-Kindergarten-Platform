<?php

namespace App\Services;

use App\Models\ActivityResult;
use Illuminate\Support\Facades\Http;

class AiReportService
{
    // تأكد من أن هذا هو endpoint الصحيح للتحليل
    private $apiUrl = 'https://chat.deepseek.com/';
    private $apiKey;

    public function __construct()
    {
        // يمكن أن يكون المفتاح مخصص لـ DeepSeek
        $this->apiKey = env('DEEPSEEK_API_KEY');
    }

    public function generateReport($enrollment_id)
    {
        $results = ActivityResult::where('enrollment_id', $enrollment_id)
            ->latest()
            ->take(3)
            ->get();

        if ($results->count() < 3) {
            return null;
        }

        $activities = $results->map(fn ($res) => [
            'activity_id' => $res->activity_id,
            'score'       => $res->score,
            'passed'      => $res->passed,
        ]);

        // اضبط حسب المطلوب من DeepSeek
        $payload = [
            'activities' => $activities->values(),
            'request_type' => 'child_assessment',
            'language' => 'ar',
            'instructions' => 'Provide summary, identify difficulties, suggest improvement activities, in simplified Arabic.',
        ];

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Accept'        => 'application/json',
        ])->post($this->apiUrl, $payload);

        if ($response->failed()) {
            return [
                'summary' => '❌ تعذّر توليد التقرير بسبب خطأ في API.',
                'details' => $activities,
            ];
        }

        $responseData = $response->json();

        $summary = $responseData['summary'] ?? 'لا توجد استجابة ملخصة من API.';
        $details = $responseData['details'] ?? $activities;

        return [
            'summary' => $summary,
            'details' => $details,
        ];
    }
}
