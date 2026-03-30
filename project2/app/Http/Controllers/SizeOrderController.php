<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Activity;
use App\Models\ActivityResult;
use Illuminate\Support\Facades\Storage;

class SizeOrderController extends Controller
{
    public function generate($enrollment_id)
    {
        $user = auth()->user();
        $enrollment = $user->enrollments()->where('id', $enrollment_id)->first();

        if (!$enrollment || !$enrollment->classRoom) {
            return response()->json(['error' => 'التسجيل غير موجود أو الصف غير محدد'], 404);
        }

        $level = strtoupper($enrollment->classRoom->level);

        // خريطة الصفوف إلى مستويات النشاط في قاعدة البيانات
        $levelMap = [
            'KG1' => 'easy',
            'KG2' => 'medium',
            'KG3' => 'hard'
        ];

        if (!isset($levelMap[$level])) {
            return response()->json(['error' => 'مستوى غير معروف'], 400);
        }

        $activityLevel = $levelMap[$level];

        // جلب النشاط من قاعدة البيانات
        $activity = Activity::where('python_script_name', 'size_order')
            ->where('level', $activityLevel)
            ->first();

        if (!$activity) {
            return response()->json(['error' => 'النشاط غير موجود'], 404);
        }

        // قراءة ملف JSON
        $jsonPath = storage_path('app/public/size_order_sets.json');
        if (!file_exists($jsonPath)) {
            return response()->json(['error' => 'ملف الأسئلة غير موجود'], 500);
        }

        $json = json_decode(file_get_contents($jsonPath), true);
        $sets = $json['question_sets'];

        // اختيار مجموعة عشوائية
        $set = collect($sets)->random();

        return response()->json([
            'activity_id' => $activity->id,
            'set_id' => $set['id'],
            'images' => collect($set['images'])->map(fn($img) => asset("storage/SizeOrder/$img")),
            'duration' => match ($level) {
                'KG1' => 5,
                'KG2' => 7,
                'KG3' => 10
            }
        ]);
    }

    /**
     * تقييم الإجابات وحفظ النتيجة
     */
    public function submit(Request $request, $enrollment_id)
    {
        $user = auth()->user();
        $enrollment = $user->enrollments()->where('id', $enrollment_id)->first();

        if (!$enrollment) {
            return response()->json(['error' => 'التسجيل غير موجود أو لا يخص هذا المستخدم'], 404);
        }

        // التحقق من المدخلات
        $request->validate([
            'activity_id' => 'required|exists:activities,id',
            'set_id' => 'required',
            'user_order' => 'required|array',
        ]);

        $activityId = $request->input('activity_id');
        $setId = $request->input('set_id');
        $userOrder = $request->input('user_order');

        // جلب الترتيب الصحيح من JSON
        $jsonPath = storage_path('app/public/size_order_sets.json');
        $json = json_decode(file_get_contents($jsonPath), true);
        $sets = collect($json['question_sets']);
        $correctSet = $sets->firstWhere('id', $setId);

        if (!$correctSet) {
            return response()->json(['error' => 'المجموعة غير موجودة'], 404);
        }

        $correctOrder = $correctSet['images'];

        // حساب النقاط الجزئية
        $totalItems = count($correctOrder);
        $scorePerItem = 100 / $totalItems;
        $score = 0;

        foreach ($userOrder as $i => $img) {
            if (isset($correctOrder[$i]) && $img === $correctOrder[$i]) {
                $score += $scorePerItem;
            }
        }

        $score = round($score, 2);
        $passed = $score >= 80; // النجاح إذا النسبة >= 80%

        // تسجيل النتيجة
        ActivityResult::create([
            'enrollment_id' => $enrollment->id,
            'activity_id' => $activityId,
            'score' => $score,
            'passed' => $passed,
            'raw_result' => json_encode([
                'set_id' => $setId,
                'user_order' => $userOrder,
                'correct_order' => $correctOrder,
                'score' => $score,
                'passed' => $passed
            ])
        ]);

        return response()->json([
            'activity_id' => $activityId,
            'set_id' => $setId,
            'user_order' => $userOrder,
            'correct_order' => $correctOrder,
            'score' => $score,
            'passed' => $passed
        ]);
    }


}


