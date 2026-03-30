<?php
namespace App\Services;

use App\Models\ActivityResult;
use App\Models\Enrollment;

class ReportService
{
    public function generateReport($enrollment_id)
    {
        $enrollment = Enrollment::find($enrollment_id);
        if (!$enrollment) return null;

        // استرجاع آخر 3 نتائج مع النشاط المرتبط
        $results = ActivityResult::with('activity')
            ->where('enrollment_id', $enrollment_id)
            ->latest()
            ->take(3)
            ->get();

        if ($results->count() < 3) {
            return null; // لم يكمل 3 أنشطة بعد
        }

        // حساب المعدل العام
        $avgScore = $results->avg('score');

        // تحديد المجالات الضعيفة
        $weakAreas = $results->filter(fn($r) => $r->score < 0.5)
            ->pluck('activity.type')
            ->unique();

        // بناء الملخص بشكل واضح
        $summary = "تقرير أداء الطفل " . $enrollment->student_name . "\n\n";
        $summary .= "معدل الأداء العام للطفل في آخر ثلاثة أنشطة هو: " . round($avgScore , 1) . " من 100.\n\n";

        if ($avgScore >= 0.8) {
            $summary .= "يظهر الطفل أداءً ممتازاً في هذه الأنشطة، ولا توجد مؤشرات على وجود صعوبات واضحة.\n";
        } elseif ($avgScore >= 0.5) {
            $summary .= "أداء الطفل متوسط، وقد تظهر بعض الصعوبات في المجالات التالية: " . ($weakAreas->isNotEmpty() ? implode(', ', $weakAreas->toArray()) : 'لا توجد') . ".\n";
        } else {
            $summary .= "الطفل يظهر صعوبات واضحة ويحتاج إلى متابعة ودعم إضافي في المجالات التالية: " . implode(', ', $weakAreas->toArray()) . ".\n";
        }

        // توصيات حسب نوع النشاط
        $recommendations = [];
        foreach ($weakAreas as $type) {
            if ($type === 'memory') {
                $recommendations[] = "التركيز على أنشطة تنمي الذاكرة مثل تذكر الأرقام والكلمات وإعادة ترتيب الصور.";
            }
            if ($type === 'attention') {
                $recommendations[] = "التركيز على أنشطة تحسين الانتباه مثل البحث عن الفروقات في الصور وحل المتاهات البسيطة.";
            }
            if ($type === 'intelligence') {
                $recommendations[] = "تقديم أنشطة لتنمية التفكير وحل الألغاز مثل أنشطة Raven.";
            }
        }

        if (empty($recommendations)) {
            $recommendations[] = "يمكن الاستمرار في تقديم أنشطة متنوعة للحفاظ على المستوى الحالي للطفل.";
        }

        // تفصيل الأنشطة بشكل نصي واضح
        $details = $results->map(function ($res) {
            $scoreText = round($res->score , 1) . " من 100";
            $passedText = $res->passed ? "نجح الطفل في هذا النشاط" : "لم ينجح الطفل في هذا النشاط";
            $weakText = $res->score < 0.5 ? "يحتاج إلى دعم في هذا المجال" : "أداء الطفل جيد في هذا المجال";

            return "النشاط: " . ($res->activity->name ?? '-') .
                "\nنوع النشاط: " . ($res->activity->type ?? '-') .
                "\nالنتيجة: " . $scoreText .
                "\nحالة النجاح: " . $passedText .
                "\nتقييم الأداء: " . $weakText . "\n";
        })->toArray();

        return [
            'child_name' => $enrollment->name,
            'summary' => $summary,
            'recommendations' => $recommendations,
            'details' => $details,
        ];
    }
}
