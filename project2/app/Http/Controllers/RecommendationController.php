<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Enrollment;
use App\Models\Activity;
use App\Models\ActivityResult;

class RecommendationController extends Controller
{

public function recommend($enrollmentId)
    {
        $enrollment = Enrollment::findOrFail($enrollmentId);
        if (!$enrollment || !$enrollment->classRoom) {
            return response()->json(['error' => 'التسجيل غير موجود أو الصف غير محدد'], 404);
        }
        // تحديد فئة الطفل
        $levelMap = [
            'KG1' => 'easy',
            'KG2' => 'medium',
            'KG3' => 'hard'
        ];
        $childLevel = $levelMap[$enrollment->classRoom->level];

        // أنواع المهارات
        $types = ['memory', 'attention', 'intelligence'];
        $weakTypes = [];

        foreach ($types as $type) {
            // آخر 3 نتائج لكل نوع، وإذا أقل من 3 نأخذ الموجود فقط
            $lastThree = ActivityResult::where('enrollment_id', $enrollment->id)
                ->whereHas('activity', fn($q) => $q->where('type', $type))
                ->latest()
                ->take(3)
                ->pluck('score');


            if ($lastThree->count() > 0) {
                $avg = $lastThree->avg();
                if ($avg < 60) { // اعتبر أقل من 0.6 ضعف
                    $weakTypes[] = $type;
                }
            }
        }

        if (empty($weakTypes)) {
            $weakTypes = $types;
        }

        // جلب الأنشطة المناسبة للفئة ونقاط الضعف
        $recommended = Activity::whereIn('type', $weakTypes)
            ->where('level', $childLevel)
            ->get()
            ->map(function ($activity) use ($enrollment) {
                $result = ActivityResult::where('enrollment_id', $enrollment->id)
                    ->where('activity_id', $activity->id)
                    ->latest()
                    ->first();

                $activity->last_score = $result ? $result->score : null;
                $activity->passed = $result ? $result->passed : null;

                return $activity;
            })
            ->sortBy('last_score'); // ترتيب الأنشطة حسب أدنى نتيجة أولًا

        return response()->json([
            'child' => $enrollment->student_name,
            'recommended_activities' => $recommended
        ],200);
    }

}

    

