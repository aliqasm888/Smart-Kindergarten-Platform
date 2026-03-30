<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Activity;
use App\Models\ActivityResult;

class RavenController extends Controller
{
    private $flask_url = "http://127.0.0.1:5000";

    private function getAgeFromLevel($level)
    {
        if (!$level) return null;
        $map = ['KG1' => 3, 'KG2' => 4, 'KG3' => 5];
        return $map[strtoupper(trim($level))] ?? null;
    }

    // جلب أسئلة حسب العمر
    public function generate($enrollment_id)
    {
        $user = auth()->user();

        $enrollment = $user->enrollments()->where('id', $enrollment_id)->first();
        if (!$enrollment || !$enrollment->classRoom) {
            return response()->json(['error' => 'التسجيل غير موجود أو الصف غير محدد'], 404);
        }

        $level = strtoupper($enrollment->classRoom->level);

        // ✅ إعدادات حسب المستوى
        $settings = match ($level) {
            'KG1' => ['count' => 5, 'duration' => 3, 'activity_level' => 'easy'],
            'KG2' => ['count' => 7, 'duration' => 4, 'activity_level' => 'medium'],
            'KG3' => ['count' => 9, 'duration' => 5, 'activity_level' => 'hard'],
            default => null,
        };

        if (!$settings) {
            return response()->json(['error' => 'مستوى غير معروف'], 400);
        }

        // ✅ جلب النشاط من قاعدة البيانات
        $activity = Activity::where('python_script_name', 'raven')
            ->where('level', $settings['activity_level'])
            ->first();

        if (!$activity) {
            return response()->json(['error' => 'النشاط غير موجود في قاعدة البيانات'], 404);
        }

        // ✅ تحميل الأسئلة من JSON
        $jsonPath = storage_path('app/public/raven_questions.json');
        if (!file_exists($jsonPath)) {
            return response()->json(['error' => 'ملف الأسئلة غير موجود'], 500);
        }

        $questions = json_decode(file_get_contents($jsonPath), true);

        // ✅ اختيار N أسئلة حسب المستوى
        $selected = array_slice($questions, 0, $settings['count']);

        $formatted = collect($selected)->map(function ($q) {
            return [
                'id' => $q['id'],
                'pattern_image' => url('storage/Raven/' . $q['pattern_image']),
                'options' => collect($q['options'])->mapWithKeys(function ($img, $id) {
                    return [$id => url('storage/Raven/' . $img)];
                }),
                'correct_option' => $q['correct_option'],
            ];
        })->values();

        return response()->json([
            'activity_id' => $activity->id,
            'activity_level' => $settings['activity_level'],
            'duration' => $settings['duration'],
            'questions' => $formatted,
        ]);
    }



    // إرسال إجابات الدفعة وتحليل النتائج مباشرة
    public function submit(Request $request, $enrollment_id)
    {
        $user = auth()->user();

        // ✅ التأكد أن التسجيل يخص المستخدم
        $enrollment = $user->enrollments()->where('id', $enrollment_id)->first();
        if (!$enrollment) {
            return response()->json(['error' => 'التسجيل غير موجود أو لا يخص هذا المستخدم'], 404);
        }

        // ✅ التحقق من صحة الإدخال
        $request->validate([
            'activity_id' => 'required|exists:activities,id',
            'answers' => 'required|array',   // صيغة: {"1":2,"2":3,...}
        ]);

        $answers = $request->input('answers');

        // ✅ تحميل الأسئلة من JSON
        $jsonPath = storage_path('app/public/raven_questions.json');
        if (!file_exists($jsonPath)) {
            return response()->json(['error' => 'ملف الأسئلة غير موجود'], 500);
        }
        $questions = json_decode(file_get_contents($jsonPath), true);

        $correct = 0;
        $details = [];

        // ✅ فحص الإجابات
        foreach ($questions as $q) {
            if (isset($answers[$q['id']])) {
                $isCorrect = ($answers[$q['id']] == $q['correct_option']);
                if ($isCorrect) {
                    $correct++;
                }
                $details[] = [
                    'question_id' => $q['id'],
                    'user_answer' => $answers[$q['id']],
                    'correct_answer' => $q['correct_option'],
                    'is_correct' => $isCorrect,
                ];
            }
        }

        $total = count($answers);
        $percentage = $total > 0 ? ($correct / $total) * 100 : 0;
        $passed = ($percentage >= 80); // ✅ النجاح إذا النسبة ≥ 80%

        // ✅ تسجيل النتيجة في قاعدة البيانات
       ActivityResult::create([
            'enrollment_id' => $enrollment->id,
            'activity_id' => $request->activity_id,
            'score' => round($percentage, 2),
            'passed' => $passed,
            'raw_result' => json_encode([
                'answers' => $answers,
                'details' => $details,
                'score' => round($percentage, 2),
                'total' => $total,
                'passed' => $passed
            ])
        ]);

        return response()->json([
            'score' => round($percentage, 2),
            'total' => $total,
            'correct' => $correct,
            'passed' => $passed,
            'details' => $details
        ]);
    }



}
