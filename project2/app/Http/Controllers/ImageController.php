<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityResult;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use function PHPUnit\Framework\isEmpty;

class ImageController extends Controller
{
    public function getImages($enrollment_id)
    {
        $user = auth()->user();

        $enrollment = $user->enrollments()->where('id', $enrollment_id)->first();

        if (!$enrollment || !$enrollment->classRoom) {
            return response()->json(['error' => 'التسجيل غير موجود أو الصف غير محدد'], 404);
        }

        $level = strtoupper($enrollment->classRoom->level);
        $levelMap = [
            'KG1' => 3,
            'KG2' => 5,
            'KG3' => 6
        ];

        $count = $levelMap[$level] ?? 3;

        $activityLevelMap = [
            'KG1' => 'easy',
            'KG2' => 'medium',
            'KG3' => 'hard'
        ];

        $activity = Activity::where('python_script_name', 'voice_sequence')
            ->where('level', $activityLevelMap[$level] ?? 'easy')
            ->first();

        if (!$activity) {
            return response()->json(['error' => 'النشاط غير موجود'], 404);
        }

        $files = File::files(storage_path("app/images/"));
        if (count($files) < $count) {
            return response()->json(['error' => 'عدد الصور غير كافٍ للمستوى المطلوب'], 500);
        }

        $selected = collect($files)->random($count)->map(function ($file) {
            $filename = $file->getFilename();
            $label = pathinfo($filename, PATHINFO_FILENAME);
            return [
                'image_url' => url("/api/image/$filename"),
                'label' => $this->arabicLabel($label)
            ];
        });

        return response()->json([
            'level' => $level,
            'count' => $count,
            'images' => $selected->values(),
            'expected_words' => $selected->pluck('label')->toArray(),
            'activity_id' => $activity->id // ✅ لإرساله مع النتيجة لاحقًا
        ]);
    }

    public function serveImage($filename)
    {
        $path = storage_path("app/images/$filename");
        if (!file_exists($path)) return response()->json(['error' => 'الصورة غير موجودة'], 404);
        return response()->file($path);
    }

    private function arabicLabel($label)
    {
        return [
                'cat' => 'قطة',
                'book' => 'كتاب',
                'apple' => 'تفاحة',
                'car' => 'سيارة',
                'banana' => 'موز',
     "ball"=> 'كرة',
     "phone"=> "هاتف",
    "tree"=> "شجرة",
     "fish"=> "سمكة",
     "house"=> "بيت"
            ][$label] ?? $label;
    }
    public function analyzeAudio(Request $request, $enrollment_id)
    {
        $request->validate([
            'expected_words' => 'required|array|min:1',
            'expected_words.*' => 'string',
            'activity_id' => 'required|exists:activities,id'
        ]);

        $user = auth()->user();
        $enrollment = $user->enrollments()->where('id', $enrollment_id)->first();

        if (!$enrollment || !$enrollment->classRoom) {
            return response()->json(['error' => 'التسجيل غير موجود أو الصف غير محدد'], 404);
        }

        $level = strtoupper($enrollment->classRoom->level);
        $levelMap = [
            'KG1' => 'easy',
            'KG2' => 'medium',
            'KG3' => 'hard'
        ];
        $activity = Activity::find($request->input('activity_id'));
        $activity_level = $levelMap[$level] ?? null;

        if (!$activity_level) {
            return response()->json(['error' => 'مستوى غير معروف'], 400);
        }

        // جلب النشاط من قاعدة البيانات
        $activity = Activity::where('python_script_name', 'voice_sequence')
            ->where('level', $activity_level)
            ->first();

        if (!$activity) {
            return response()->json(['error' => 'النشاط غير موجود'], 404);
        }

        // حفظ الملف مؤقتًا
        $audio = $request->file('audio');
        $path = $audio->storeAs('temp', 'recording.mp3');

        // إرسال الملف إلى Flask
        $response = Http::attach(
            'audio', file_get_contents(storage_path('app/temp/recording.mp3')), 'recording.mp3'
        )->post('http://localhost:5000/analyzeVoice');

        if (!$response->ok()) {
            return response()->json(['error' => 'فشل في التحليل'], 500);
        }

        $extractedText = $response->json('text');
        $expectedWords = $request->input('expected_words', []);

        // ✅ تطبيع النصوص
        $normalize = function ($text) {
            $text = preg_replace('~[ًٌٍَُِّْـ]~u', '', $text); // إزالة التشكيل
            $text = str_replace(['أ', 'إ', 'آ'], 'ا', $text);
            $text = str_replace(['ة'], 'ه', $text);
            return trim($text);
        };

        $normalizedText = $normalize($extractedText);
        $userWords = preg_split('/[\s,،.]+/u', $normalizedText);

        $score = 0;
        $matchedList = [];
        $usedIndexes = [];

        foreach ($expectedWords as $index => $expected) {
            $expectedNorm = $normalize($expected);

            if (isset($userWords[$index]) && $normalize($userWords[$index]) === $expectedNorm) {
                $score += 1; // ✅ ترتيب صحيح
                $matchedList[] = ['word' => $expected, 'match' => 'full'];
                $usedIndexes[] = $index;
            } else {
                foreach ($userWords as $i => $word) {
                    if (in_array($i, $usedIndexes)) continue;
                    if ($normalize($word) === $expectedNorm) {
                        $score += 0.5; // ✅ ترتيب خاطئ
                        $matchedList[] = ['word' => $expected, 'match' => 'partial'];
                        $usedIndexes[] = $i;
                        break;
                    }
                }
            }
        }

        $maxScore = count($expectedWords);
        $finalScore = round($score, 2);
        $percentage = round($score / max(1, $maxScore) * 100, 2);
        $passed = $percentage >= 80;

        // ✅ حفظ النتيجة
        ActivityResult::create([
            'enrollment_id' => $enrollment->id,
            'activity_id' => $activity->id,
            'score' => $percentage,
            'passed' => $passed,
            'raw_result' => json_encode([
                'expected' => $expectedWords,
                'recognized_text' => $extractedText,
                'matches' => $matchedList,
                'score_value' => $score,
                'max_score' => $maxScore,
                'percentage' => $percentage,
                'passed' => $passed
            ])
        ]);

        return response()->json([
            'expected' => $expectedWords,
            'recognized_text' => $extractedText,
            'matches' => $matchedList,
            'score_value' => $score,
            'max_score' => $maxScore,
            'score' => $percentage . '%',
            'passed' => $passed
        ]);
    }

}

