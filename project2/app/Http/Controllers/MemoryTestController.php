<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityResult;
use Illuminate\Http\Request;

class MemoryTestController extends Controller
{
    public function generate($enrollment_id)
    {
        $user = auth()->user(); // الأب المسجل دخول

        // جلب التسجيل المحدد بشرط أن يكون فعلاً لهذا المستخدم
        $enrollment = $user->enrollments()->where('id', $enrollment_id)->first();

        if (!$enrollment || !$enrollment->classRoom) {
            return response()->json(['error' => 'التسجيل غير موجود أو الصف غير محدد'], 404);
        }

        $level = strtoupper($enrollment->classRoom->level);

        $settings = match ($level) {
            'KG1' => ['count' => 3, 'duration' => 3, 'activity_level' => 'easy'],
            'KG2' => ['count' => 5, 'duration' => 4, 'activity_level' => 'medium'],
            'KG3' => ['count' => 6, 'duration' => 5, 'activity_level' => 'hard'],
            default => null,
        };

        if (!$settings) {
            return response()->json(['error' => 'مستوى غير معروف'], 400);
        }

        // جلب النشاط المناسب من قاعدة البيانات
        $activity = Activity::where('python_script_name', 'memory_numbers')
            ->where('level', $settings['activity_level'])
            ->first();

        if (!$activity) {
            return response()->json(['error' => 'النشاط غير موجود في قاعدة البيانات'], 404);
        }

        $numbers = [];
        for ($i = 0; $i < $settings['count']; $i++) {
            $numbers[] = rand(0, 9);
        }

        return response()->json([
            'numbers' => $numbers,
            'duration' => $settings['duration'],
            'level' => $level,
            'count' => $settings['count'],
            'activity_id' => $activity->id,
            'enrollment_id' => $enrollment->id
        ]);
    }
    public function checkAnswer(Request $request, $enrollment_id)
    {
        $request->validate([
            'original_numbers' => 'required|array',
            'original_numbers.*' => 'integer|min:0|max:9',
            'numbers' => 'required|array',
            'numbers.*' => 'integer|min:0|max:9',
            'activity_id' => 'required|exists:activities,id'
        ]);

        $user = auth()->user();

        // التحقق أن هذا التسجيل يخص المستخدم الحالي
        $enrollment = $user->enrollments()->where('id', $enrollment_id)->first();

        if (!$enrollment) {
            return response()->json(['error' => 'التسجيل غير موجود أو لا يخص هذا المستخدم'], 404);
        }

        $userInput = $request->input('numbers');
        $original = $request->input('original_numbers');

        if (empty($original)) {
            return response()->json(['error' => 'لا توجد أرقام أصلية لإجراء المقارنة.'], 400);
        }

        $usedIndices = [];
        $score = 0;

        // ✅ نقاط كاملة (الرقم في نفس الموقع)
        foreach ($userInput as $i => $num) {
            if (isset($original[$i]) && $num == $original[$i]) {
                $score += 1;
                $usedIndices[] = $i;
            }
        }

        // ✅ نصف نقطة (الرقم صحيح لكن في موقع مختلف ولم يُحسب سابقًا)
        foreach ($userInput as $i => $num) {
            if (isset($original[$i]) && $num == $original[$i]) continue;
            $idx = array_search($num, $original);
            if ($idx !== false && !in_array($idx, $usedIndices)) {
                $score += 0.5;
                $usedIndices[] = $idx;
            }
        }

        $total = count($original);
        $percentage = ($score / $total) * 100; // النسبة المئوية
        $passed = ($percentage >= 80); // تمر إذا النسبة >= 80%

        ActivityResult::create([
            'enrollment_id' => $enrollment->id,
            'activity_id' => $request->activity_id,
            'score' => round($percentage, 2), // تخزين النسبة المئوية
            'passed' => $passed,
            'raw_result' => json_encode([
                'original_numbers' => $original,
                'user_input' => $userInput,
                'score' => round($percentage, 2),
                'total' => $total,
                'passed' => $passed
            ])
        ]);

        return response()->json([
            'original_numbers' => $original,
            'user_input' => $userInput,
            'score' => round($percentage, 2), // النسبة المئوية
            'total' => $total,
            'passed' => $passed
        ]);
    }
}
