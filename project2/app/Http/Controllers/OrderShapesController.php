<?php

namespace App\Http\Controllers;

use App\Models\ActivityResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OrderShapesController extends Controller
{
    public function evaluateorder(Request $request, $enrollment_id)
    {
        $request->validate([
            'activity_id' => 'required|exists:activities,id',
            'displayed_ids' => 'required|array',
            'selected_ids' => 'required|array',
            'target_ids' => 'required|array',
            'response_time' => 'required|numeric'
        ]);

        $user = auth()->user();
        $enrollment = $user->enrollments()->where('id', $enrollment_id)->first();

        if (!$enrollment || !$enrollment->classRoom) {
            return response()->json(['error' => 'التسجيل غير موجود أو الصف غير محدد'], 404);
        }

        $age_map = ['KG1' => 4, 'KG2' => 5, 'KG3' => 6];
        $level = strtoupper($enrollment->classRoom->level);
        $age = $age_map[$level] ?? null;

        if (!$age) {
            return response()->json(['error' => 'مستوى غير معروف'], 400);
        }

        // إرسال البيانات إلى Python API
        $response = Http::post('http://localhost:5000/api/mot/evaluate', [
            'age' => $age,
            'displayed_ids' => $request->displayed_ids,
            'selected_ids' => $request->selected_ids,
            'target_ids' => $request->target_ids,
            'response_time' => $request->response_time
        ]);

        $result = $response->json();

        // تحقق من أن النتيجة تحتوي على 'analysis' و 'final_classification'
        if (!isset($result['analysis']) || !is_array($result['analysis'])) {
            return response()->json(['error' => 'تحليل غير صالح من الخدمة الخلفية'], 500);
        }

        $analysis = $result['analysis'];
        $score = $analysis['accuracy (%)'] ?? null;
        $final_classification = $analysis['final_classification'] ?? null;
        $passed = $final_classification === 'Within normal range';

        ActivityResult::create([
            'enrollment_id' => $enrollment->id,
            'activity_id' => $request->activity_id,
            'score' => $score,
            'passed' => $passed,
            'raw_result' => json_encode($result)
        ]);

        return response()->json($result);
    }
}
