<?php

namespace App\Http\Controllers;


use App\Models\Attendances;
use App\Models\ClassRoom;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function storeAttendance(Request $request)
    {
        $data = $request->validate([
            'absents'   => 'required|array',        // مصفوفة الغائبين
            'absents.*' => 'exists:enrollments,id', // كل عنصر enrollment_id موجود
        ]);

        $teacher = $request->user();
        $classroom = $teacher->classroom;

        if (!$classroom) {
            return response()->json(['message' => 'لا يوجد صف مرتبط بالمعلم'], 404);
        }

        $today = now()->toDateString();

        foreach ($data['absents'] as $enrollmentId) {
            Attendances::updateOrCreate(
                ['enrollment_id' => $enrollmentId, 'date' => $today],
                ['status' => 'absent']
            );
        }

        // تسجيل الحضور للبقية تلقائياً (اختياري)
        $presentEnrollments = Enrollment::where('classroom_id', $classroom->id)
            ->whereNotIn('id', $data['absents'])
            ->pluck('id');

        foreach ($presentEnrollments as $enrollmentId) {
            Attendances::updateOrCreate(
                ['enrollment_id' => $enrollmentId, 'date' => $today],
                ['status' => 'present']
            );
        }

        return response()->json(['message' => 'تم تسجيل الحضور والغياب بنجاح']);
    }
    public function teacherAttendances(Request $request)
    {
        $teacher = $request->user();

        // تحقق من الصف
        $classroom = ClassRoom::where('user_id', $teacher->id)->first();
        if (!$classroom) {
            return response()->json([
                'message' => 'لا يوجد صف مرتبط بالمعلم',
                'teacher_id' => $teacher->id
            ], 404);
        }

        // تحقق من الطلاب
        $enrollmentsCount = $classroom->enrollments()->count();
        if ($enrollmentsCount === 0) {
            return response()->json([
                'message' => 'لا يوجد طلاب مسجلين في هذا الصف',
                'classroom_id' => $classroom->id
            ], 404);
        }

        // جلب الغيابات فقط
        $absences = Attendances::whereHas('enrollment', function ($q) use ($classroom) {
            $q->where('classroom_id', $classroom->id);
        })
            ->where('status', 'absent') // ✅ الغيابات فقط
            ->with(['enrollment:id,student_name'])
            ->get(['id','enrollment_id','status','date']);

        if ($absences->isEmpty()) {
            return response()->json([
                'message' => 'لا توجد سجلات غياب للطلاب حتى الآن',
                'classroom_id' => $classroom->id,
                'enrollments_count' => $enrollmentsCount
            ]);
        }

        $result = $absences->map(function ($attendance) {
            return [
                'student_id'   => $attendance->enrollment->id,
                'student_name' => $attendance->enrollment->student_name,
                'date'         => $attendance->date,
            ];
        });

        return response()->json($result);
    }




    public function studentAttendanceReport($enrollmentId, Request $request)
    {


        $attendance = Attendances::where('enrollment_id', $enrollmentId)
            ->with(['enrollment.student', 'enrollment.classroom'])
            ->first(); // لأننا نريد يوم واحد فقط

        if (!$attendance) {
            return response()->json(['message' => 'لا يوجد سجل حضور لهذا الطالب في هذا التاريخ'], 404);
        }

        return response()->json($attendance);
    }



    // ✅ استرجاع حضور صف كامل في يوم معين
    public function classroomAttendance(Request $request)
    {
        $teacher = Auth::user();
        $classroom = $teacher->classroom;
        $date = $request->query('date');

        if (!$classroom) {
            return response()->json(['message' => 'لا يوجد صف مرتبط بالمعلم'], 404);
        }

        if (!$date) {
            return response()->json(['message' => 'يجب إدخال تاريخ'], 400);
        }

        $absents = Attendances::whereHas('enrollment', function ($q) use ($classroom) {
            $q->where('classroom_id', $classroom->id);
        })
            ->whereDate('date', $date)
            ->where('status', 'absent') // ✅ نرجع فقط الغيابات
            ->with('enrollment.student')
            ->get();

        return response()->json($absents);
    }
    public function absencesByDate(Request $request)
    {
        $date = $request->input('date');

        if (!$date) {
            return response()->json(['message' => 'يجب إدخال تاريخ'], 400);
        }

        // نفترض أن attendance فيه عمود status = "absent" أو "present"
        $absences = Attendances::whereDate('date', $date)
            ->where('status', 'absent')
            ->with(['enrollment.student', 'enrollment.classroom'])
            ->get();

        if ($absences->isEmpty()) {
            return response()->json(['message' => 'لا يوجد غيابات في هذا التاريخ'], 404);
        }

        return response()->json($absences);
    }
    public function classroomAttendanceAlldate()
    {
        $teacher = Auth::user();
        $classroom = $teacher->classroom;

        if (!$classroom) {
            return response()->json(['message' => 'لا يوجد صف مرتبط بالمعلم'], 404);
        }



        $absents = Attendances::whereHas('enrollment', function ($q) use ($classroom) {
            $q->where('classroom_id', $classroom->id);
        })

            ->where('status', 'absent') // ✅ نرجع فقط الغيابات
            ->with('enrollment.student')
            ->get();

        return response()->json($absents);
    }


}
