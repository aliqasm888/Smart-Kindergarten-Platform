<?php
// app/Services/HomeworkService.php
namespace App\Services;

use App\Models\Homework;
use App\Models\ClassRoom;
use App\Models\Enrollment;
use Illuminate\Support\Facades\Auth;

class HomeworkService
{
    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }
    public function AddHomework($request, $teacherId)
    {
        // الحصول على الصف الخاص بالمعلم (مثلًا الصف الأول أو حسب شرط معين)
        $classroom = ClassRoom::where('user_id', $teacherId)->first();

        if (!$classroom) {
            return ['data' => null, 'message' => 'Classroom not found for this teacher', 'code' => 404];
        }

        $homework = Homework::create([
            'classroom_id' => $classroom->id,
            'title'        => $request->title,
            'description'  => $request->description ?? null,
            'due_date'     => $request->due_date,
        ]);
        $tokens = $classroom->enrollments()
            ->with('user.fcmTokens')
            ->get()
            ->flatMap(fn($enrollment) => $enrollment->user->fcmTokens->pluck('fcm_token'))
            ->toArray();

        $fcmResponse = [];
        if (!empty($tokens)) {
            $fcmResponse = $this->firebaseService->sendNotification(
                $tokens,
                "وظيفة جديدة!",
                "تمت إضافة وظيفة جديدة في صفك: "
            );
        }

        return ['data' => $homework, 'message' => 'Homework created successfully', 'code' => 200];
    }

    public function ShowHomework($id)
    {
        $homework = Homework::with('classroom:id,class_name')->find($id);

        if (!$homework) {
            return ['data' => null, 'message' => 'Homework not found', 'code' => 404];
        }

        $data = [
            'id'          => $homework->id,
            'title'       => $homework->title,
            'description' => $homework->description,
            'due_date'    => $homework->due_date,
            'class_name'  => $homework->classroom->class_name ?? null,
            'created_at'  => $homework->created_at,
            'updated_at'  => $homework->updated_at,
        ];

        return ['data' => $data, 'message' => 'Homework retrieved successfully', 'code' => 200];
    }

    public function GetHomeworksByClass($classroomId)
    {
        $homeworks = Homework::where('classroom_id', $classroomId)->get();

        return ['data' => $homeworks, 'message' => 'Homeworks retrieved successfully', 'code' => 200];
    }

    public function UpdateHomework($request, $id, $teacherId)
    {
        $homework = Homework::find($id);

        if (!$homework) {
            return ['data' => null, 'message' => 'Homework not found', 'code' => 404];
        }

        if ($homework->classroom->user_id !== $teacherId) {
            return ['data' => null, 'message' => 'Unauthorized', 'code' => 403];
        }

        $homework->update($request->only(['title', 'description', 'due_date']));

        return ['data' => $homework->fresh(), 'message' => 'Homework updated successfully', 'code' => 200];
    }

    public function DeleteHomework($id, $teacherId)
    {
        $homework = Homework::find($id);

        if (!$homework) {
            return ['data' => null, 'message' => 'Homework not found', 'code' => 404];
        }

        if ($homework->classroom->user_id !== $teacherId) {
            return ['data' => null, 'message' => 'Unauthorized', 'code' => 403];
        }

        $homework->delete();

        return ['data' => null, 'message' => 'Homework deleted successfully', 'code' => 200];
    }

    public function GetStudentHomeworks($enrollmentId)
    {
        $enrollment = Enrollment::find($enrollmentId);

        if (!$enrollment) {
            return ['data' => null, 'message' => 'Enrollment not found', 'code' => 404];
        }

        $homeworks = Homework::where('classroom_id', $enrollment->classroom_id)
            ->orderBy('due_date', 'asc')
            ->get();

        return ['data' => $homeworks, 'message' => 'Student homeworks retrieved successfully', 'code' => 200];
    }
    public function GetTeacherHomeworks()
    {
        $teacher = Auth::user(); // المدرس الحالي (عن طريق المصادقة Sanctum/JWT)

        if (!$teacher) {
            return [
                'data' => null,
                'message' => 'Unauthorized',
                'code' => 401
            ];
        }

        // جلب الصفوف المرتبطة بالمدرس
        $classrooms = $teacher->teacher()->pluck('id');

        // جلب الوظائف الخاصة بهذه الصفوف
        $homeworks = Homework::with('classroom')
            ->whereIn('classroom_id', $classrooms)
            ->orderBy('due_date', 'asc')
            ->get();

        return [
            'data' => $homeworks,
            'message' => 'Teacher homeworks retrieved successfully',
            'code' => 200
        ];
    }
}
