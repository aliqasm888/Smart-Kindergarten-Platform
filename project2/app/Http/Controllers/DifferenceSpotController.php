<?php

namespace App\Http\Controllers;

use App\Models\ActivityResult;
use App\Models\DifferenceImagePair;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DifferenceSpotController extends Controller
{
    public function getPair()
    {
        $pair = DifferenceImagePair::inRandomOrder()->first();

        return response()->json([
            'pair_id' => $pair->id,
            'image_1_url' => asset('storage/' . $pair->image_1),
            'image_2_url' => asset('storage/' . $pair->image_2),
        ]);
    }
    public function evaluateDifferenceSpot(Request $request, $enrollment_id)
    {
        $request->validate([
            'activity_id' => 'required|exists:activities,id',
            'pair_id' => 'required|exists:difference_image_pairs,id',
            'spots_pressed' => 'required|array',
            'times' => 'required|array'
        ]);

        $user = auth()->user();
        $enrollment = $user->enrollments()->where('id', $enrollment_id)->first();

        if (!$enrollment || !$enrollment->classRoom) {
            return response()->json(['error' => 'Invalid enrollment or class'], 404);
        }

        $level = strtoupper($enrollment->classRoom->level);
        $age_map = ['KG1' => 4, 'KG2' => 5, 'KG3' => 6];
        $age = $age_map[$level] ?? null;

        if (!$age) {
            return response()->json(['error' => 'Unknown level'], 400);
        }

        $pair = DifferenceImagePair::findOrFail($request->pair_id);
        $image1_path = storage_path('app/public/' . $pair->image_1);
        $image2_path = storage_path('app/public/' . $pair->image_2);

        // إرسال الصور + البيانات
        $response = Http::attach(
            'img1', fopen($image1_path, 'r'), basename($image1_path)
        )->attach(
            'img2', fopen($image2_path, 'r'), basename($image2_path)
        )->post('http://127.0.0.1:5000/api/difference/evaluate', [
            'age' => $age,
            'spots_pressed' => json_encode($request->spots_pressed), // نرسل JSON
            'times' => json_encode($request->times) // نرسل JSON
        ]);

        $result = $response->json();

        ActivityResult::create([
            'enrollment_id' => $enrollment->id,
            'activity_id' => $request->activity_id,
            'score' => isset($result['Accuracy']['Value']) ? $result['Accuracy']['Value'] * 100 : null,
            'passed' => ($result['Accuracy']['Assessment'] ?? '') === 'Within normal range',
            'raw_result' => json_encode($result),
        ]);

        return response()->json($result);
    }


}
