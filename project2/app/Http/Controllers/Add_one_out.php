<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Activity;
use App\Models\ActivityResult;

class Add_one_out extends Controller
{
    // جلب نشاط
    public function generate(Request $request, $enrollment_id)
    {
        $user = auth()->user();
        $enrollment = $user->enrollments()->where('id', $enrollment_id)->first();

        if (!$enrollment || !$enrollment->classRoom) {
            return response()->json(['error' => 'التسجيل غير موجود أو الصف غير محدد'], 404);
        }

        $level = strtolower($enrollment->classRoom->level); // تحويل المستوى إلى صيغة صغيرة

        // استدعاء Flask
        $response = Http::post('http://127.0.0.1:5000/classify/generate', [
            'level' => $level,
        ]);

        $data = $response->json();

        if (!$data || isset($data['error'])) {
            return response()->json(['error' => 'فشل في جلب النشاط من Flask'], 500);
        }

        return response()->json([
            'id' => $data['id'],
            'title' => $data['title'],
            'items' => $data['items'],
            'category' => $data['category'],
            'correct_answer' => $data['correct_answer'],
            'max_time' => $data['max_time'],
        ]);
    }

    // تقييم النشاط وحفظ النتيجة
    public function evaluate(Request $request, $enrollment_id)
    {
        $request->validate([
            'activity_id' => 'required|exists:activities,id',
            'items' => 'required|array',
            'selected' => 'required|string',
            'correct_answer' => 'required|string',
            'time_taken' => 'required|numeric',
            'click_times' => 'nullable|array',
        ]);

        $user = auth()->user();
        $enrollment = $user->enrollments()->where('id', $enrollment_id)->first();

        if (!$enrollment || !$enrollment->classRoom) {
            return response()->json(['error' => 'التسجيل غير موجود أو الصف غير محدد'], 404);
        }

        $level = strtolower($enrollment->classRoom->level); // تحويل المستوى

        // استدعاء Flask
        $response = Http::post('http://127.0.0.1:5000/api/classify/evaluate', [
            'items' => $request->items,
            'selected' => $request->selected,
            'correct_answer' => $request->correct_answer,
            'time_taken' => $request->time_taken,
            'click_times' => $request->click_times ?? [],
            'level' => $level,
        ]);

        $result = $response->json();

        // حفظ النتيجة
        ActivityResult::create([
            'enrollment_id' => $enrollment->id,
            'activity_id'   => $request->activity_id,
            'score'         => $result['result'] === 'صحيح' ? 1 : 0,
            'passed'        => $result['result'] === 'صحيح',
            'raw_result'    => json_encode($result, JSON_UNESCAPED_UNICODE),
        ]);

        return response()->json($result);
    }
}
