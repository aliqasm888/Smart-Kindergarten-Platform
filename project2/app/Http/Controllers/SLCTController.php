<?php
namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SLCTController extends Controller
{
    public function generate($enrollment_id)
    {
        $user = auth()->user();
        $enrollment = $user->enrollments()->where('id', $enrollment_id)->first();

        if (!$enrollment || !$enrollment->classRoom) {
            return response()->json(['error' => 'التسجيل غير موجود أو الصف غير محدد'], 404);
        }

        $level = strtoupper($enrollment->classRoom->level);

        // تحويل مستوى الطفل إلى عدد الحروف ومستوى النشاط
        $levelMap = [
            'KG1' => ['count' => 3, 'activity_level' => 'easy'],
            'KG2' => ['count' => 5, 'activity_level' => 'medium'],
            'KG3' => ['count' => 6, 'activity_level' => 'hard']
        ];

        $settings = $levelMap[$level] ?? null;
        if (!$settings) {
            return response()->json(['error' => 'مستوى غير معروف'], 400);
        }

        // اختيار النشاط المناسب من جدول الأنشطة
        $activity = Activity::where('python_script_name', 'slct')
            ->where('level', $settings['activity_level'])
            ->first();

        if (!$activity) {
            return response()->json(['error' => 'النشاط غير موجود'], 404);
        }

        // استدعاء Flask لإعطاء التحدي
        $response = Http::post('http://localhost:5000/api/slct/generate', [
            'count' => $settings['count']
        ]);

        $data = $response->json();

        return response()->json([
            'target_letters' => $data['target_letters'],
            'grid' => $data['grid'],
            'activity_id' => $activity->id // نعيده ليُستخدم في التسجيل لاحقًا
        ]);
    }



    public function evaluate(Request $request, $enrollment_id)
    {
        $request->validate([
            'activity_id' => 'required|exists:activities,id',
            'target_letters' => 'required|array',
            'cancelled_letters_1' => 'required|array',
            'cancelled_letters_2' => 'required|array',
            'time1' => 'nullable|numeric',
            'time2' => 'nullable|numeric',
        ]);

        $user = auth()->user();
        $enrollment = $user->enrollments()->where('id', $enrollment_id)->first();

        if (!$enrollment || !$enrollment->classRoom) {
            return response()->json(['error' => 'التسجيل غير موجود أو الصف غير محدد'], 404);
        }

        $level = strtoupper($enrollment->classRoom->level);
        $levels = ['KG1' => 3, 'KG2' => 5, 'KG3' => 6];
        $count = $levels[$level] ?? null;

        if (!$count) {
            return response()->json(['error' => 'مستوى غير معروف'], 400);
        }

        // استدعاء التقييم من Flask
        $response = Http::post('http://localhost:5000/api/slct/evaluate', [
            'target_letters' => $request->target_letters,
            'cancelled_letters_1' => $request->cancelled_letters_1,
            'cancelled_letters_2' => $request->cancelled_letters_2,
            'gender' => $enrollment->gender,
            'time1' => $request->time1 ?? 60,
            'time2' => $request->time2 ?? 60,
            'count' => $count
        ]);

        $result = $response->json();

        // حفظ النتيجة
        ActivityResult::create([
            'enrollment_id' => $enrollment->id,
            'activity_id' => $request->activity_id,
            'score' => $result['analysis']['Round 2']['Net Score'] ?? null,
            'passed' => $result['analysis']['Round 2']['Performance Level'] !== 'Below Average',
            'raw_result' => json_encode($result)
        ]);

        return response()->json($result);
    }
}

