<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Report;
use App\Services\ReportService;
use Mpdf\Mpdf;

class ReportController extends Controller
{

    public function generate($enrollment_id, ReportService $service)
    {
        $reportData = $service->generateReport($enrollment_id);

        if (!$reportData) {
            return response()->json(['message' => 'الطفل لم يكمل 3 أنشطة بعد'], 400);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'dejavusans',
            'directionality' => 'rtl',
        ]);

        $html = view('reports.pdf', [
            'child_name' => $reportData['child_name'],
            'summary' => $reportData['summary'],
            'recommendations' => $reportData['recommendations'],
            'details' => $reportData['details'],
        ])->render();

        $mpdf->WriteHTML($html);

        // يمكن التغيير بين stream أو download
        return response()->streamDownload(function() use ($mpdf) {
            echo $mpdf->Output('', 'S'); // 'S' لإرجاع المحتوى كـ string
        }, 'تقرير_الطفل_'.$reportData['child_name'].'.pdf', [
            'Content-Type' => 'application/pdf'
        ]);
    }
}
