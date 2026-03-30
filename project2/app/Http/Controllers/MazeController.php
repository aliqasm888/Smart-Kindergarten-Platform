<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\ActivityResult;
use App\Models\Activity;

class MazeController extends Controller
{
    public function generate($enrollment_id)
    {
        $user = auth()->user();
        $enrollment = $user->enrollments()->where('id', $enrollment_id)->first();

        if (!$enrollment || !$enrollment->classRoom) {
            return response()->json(['error' => 'التسجيل غير موجود أو الصف غير محدد'], 404);
        }

        $level = strtoupper($enrollment->classRoom->level);

        // خريطة الصفوف إلى مستويات النشاط
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
        $activity = Activity::where('python_script_name', 'maze')
            ->where('level', $activityLevel)
            ->first();

        if (!$activity) {
            return response()->json(['error' => 'النشاط غير موجود'], 404);
        }

        // قراءة ملف JSON الخاص بالمستوى
        $jsonPath = storage_path("app/public/maze_sets.json");
        if (!file_exists($jsonPath)) {
            return response()->json(['error' => 'ملف المسارات غير موجود'], 500);
        }

        $json = json_decode(file_get_contents($jsonPath), true);
        $sets = $json['mazes'];

        // اختيار عشوائي لمجموعة
        $mazeSet = collect($sets)->random();

        return response()->json([
            'activity_id' => $activity->id,
            'maze' => [
                'set_id' => $mazeSet['id'],
                'image' => asset("storage/maze/{$mazeSet['image']}"),
                'correct_path' => $mazeSet['correct_path'],
                'num_paths' => $mazeSet['num_paths']
            ]
        ]);
    }

    /**
     * تقييم الإجابة وتسجيل النتيجة
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
            'child_choice' => 'required|integer',
            'correct_path' => 'required|integer',
            'time_taken' => 'nullable|numeric',
        ]);

        $activityId = $request->input('activity_id');
        $childChoice = $request->input('child_choice');
        $correctPath = $request->input('correct_path');
        $timeTaken = $request->input('time_taken', null);

        $passed = ($childChoice === $correctPath);
        $score = $passed ? 100 : 0;

        // تسجيل النتيجة
        ActivityResult::create([
            'enrollment_id' => $enrollment->id,
            'activity_id' => $activityId,
            'score' => $score,
            'passed' => $passed,
            'raw_result' => json_encode([
                'child_choice' => $childChoice,
                'correct_path' => $correctPath,
                'time_taken' => $timeTaken,
                'passed' => $passed
            ])
        ]);

        return response()->json([
            'activity_id' => $activityId,
            'child_choice' => $childChoice,
            'correct_path' => $correctPath,
            'score' => $score,
            'passed' => $passed,
            'time_taken' => $timeTaken
        ]);
    }
}
