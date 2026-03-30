<?php

namespace App\Services;

use App\Models\ClassRoom;
use App\Models\Enrollment;
use App\Models\Lesson;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;


class LessonServices
{
    protected FirebaseService $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }



    public function AddLesson($request)
    {
        // 🔎 الحصول على المستخدم الحالي (المدرس)
        $teacher = Auth::user();
        if (!$teacher) {
            return [
                'data' => null,
                'message' => 'Teacher not authenticated',
                'code' => 401
            ];
        }

        // 🔎 الحصول على الصف الخاص بالمدرس
        $classRoom = $teacher->classroom; // باستخدام العلاقة classroom() في User
        if (!$classRoom) {
            return [
                'data' => null,
                'message' => 'No classroom found for this teacher',
                'code' => 404
            ];
        }

        // ➕ إنشاء الدرس
        $lesson = Lesson::create($request->only(['title', 'subject', 'description', 'date']));

        // 🔗 ربط الدرس بالصف الخاص بالمدرس
        $lesson->classRooms()->attach($classRoom->id);

        // 📎 إضافة المرفقات إن وجدت
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $type = $this->detectType($file);
                $path = $file->store('attachments', 'public');

                $lesson->attachments()->create([
                    'type' => $type,
                    'path' => asset('storage/' . $path),
                ]);
            }
        }

        // 🔔 إرسال إشعار للطلاب في هذا الصف
        $tokens = $classRoom->enrollments()
            ->with('user.fcmTokens')
            ->get()
            ->flatMap(fn($enrollment) => $enrollment->user->fcmTokens->pluck('fcm_token'))
            ->toArray();

        $fcmResponse = [];
        if (!empty($tokens)) {
            $fcmResponse = $this->firebaseService->sendNotification(
                $tokens,
                "درس جديد!",
                "تمت إضافة درس جديد في صفك: " . $lesson->title
            );
        }

        return [
            'data' => $lesson,
            'message' => 'Lesson created and notifications sent successfully',
            'code' => 200
        ];
    }




    public function ShowLesson($id)
    {
        $lesson = Lesson::with('attachments')->find($id);
        return $lesson
            ? ['data' => $lesson, 'message' => 'Lesson found', 'code' => 200]
            : ['data' => null, 'message' => 'Lesson not found', 'code' => 404];
    }

    public function GetLessons()
    {
        $lesson = Lesson::with('attachments')->get();
        return [
            'data' => $lesson,
            'message' => 'All lessons retrieved successfully',
            'code' => 200
        ];
    }

    public function UpdateLesson($request, $id)
    {
        $lesson = Lesson::find($id);
        if (!$lesson) {
            return [
                'code' => 404,
                'message' => 'Lesson not found',
                'data' => null
            ];
        }

        // ✅ تحديث بيانات الدرس الأساسية
        $lesson->update($request->only(['title', 'subject', 'description', 'date']));

        // 🔄 تحديث الصفوف المرتبطة بالدرس
        if ($request->filled('class_room_ids')) {
            $lesson->classRooms()->sync($request->class_room_ids);
        }

        // 📎 إعادة رفع المرفقات إن وُجدت
        if ($request->hasFile('attachments')) {
            // حذف المرفقات القديمة
            $lesson->attachments()->delete();

            foreach ($request->file('attachments') as $file) {
                $type = $this->detectType($file);
                $path = $file->store('attachments', 'public');

                $lesson->attachments()->create([
                    'type' => $type,
                    'path' => asset('storage/' . $path),
                ]);
            }
        }

        return [
            'code' => 200,
            'data' => $lesson->load(['attachments', 'classRooms']),
            'message' => 'Lesson updated successfully'
        ];
    }


    public function DeleteLesson($id)
    {
        $lesson = Lesson::find($id);
        if ($lesson) {
            $lesson->delete();
            return ['data' => null, 'message' => 'Lesson deleted successfully', 'code' => 200];
        }
        return ['data' => null, 'message' => 'Lesson not found', 'code' => 404];
    }
    private function detectType($file): string
    {
        $mime = $file->getMimeType();

        return match (true) {
            str_starts_with($mime, 'image/') => 'image',
            str_starts_with($mime, 'video/') => 'video',
            $mime === 'application/pdf' => 'pdf',
            default => 'unknown'
        };
    }

    public function GetTodayLessons()
    {
        $today = Carbon::today()->toDateString();

        $lessons = Lesson::with('attachments')
            ->whereDate('date', $today)
            ->get();

        return [
            'data' => $lessons,
            'message' => 'Lessons for today retrieved successfully',
            'code' => 200
        ];
    }
    public function GetUpcomingWeekLessons()
    {
        $today = Carbon::today();
        $nextWeek = Carbon::today()->addDays(7);

        $lessons = Lesson::with('attachments')
            ->whereDate('date', '>', $today)
            ->whereDate('date', '<=', $nextWeek)
            ->get();

        return [
            'data' => $lessons,
            'message' => 'Lessons for the upcoming week retrieved successfully',
            'code' => 200
        ];
    }
    public function GetLessonsBySubject($request)
    {
        $query = Lesson::with('attachments');

        if ($request->filled('subject')) {
            $query->where('subject', $request->subject);
        }

        $lessons = $query->get();

        return [
            'data' => $lessons,
            'message' => 'Lessons filtered by subject retrieved successfully',
            'code' => 200
        ];
    }
    public function GetLessonsByEnrollment($enrollment_id)
    {
        $enrollment = Enrollment::with('classRoom.lessons.attachments')->find($enrollment_id);

        if (!$enrollment || !$enrollment->classRoom) {
            return [
                'data' => null,
                'message' => 'Enrollment or classroom not found',
                'code' => 404
            ];
        }

        $lessons = $enrollment->classRoom->lessons;

        return [
            'data' => $lessons,
            'message' => 'Lessons for the student retrieved successfully',
            'code' => 200
        ];
    }
    public function GetTodayLessonsByEnrollment($enrollment_id, $subject = null)
    {
        $enrollment = Enrollment::with('classRoom.lessons.attachments')->find($enrollment_id);

        if (!$enrollment || !$enrollment->classRoom) {
            return [
                'data' => null,
                'message' => 'Enrollment or classroom not found',
                'code' => 404
            ];
        }

        $today = Carbon::today()->toDateString();

        $lessons = $enrollment->classRoom->lessons()
            ->whereDate('date', $today)
            ->when($subject, function ($query, $subject) {
                $query->where('subject', $subject);
            })
            ->with('attachments')
            ->get();

        return [
            'data' => $lessons,
            'message' => 'Today\'s lessons for the student retrieved successfully',
            'code' => 200
        ];
    }
    public function GetUpcomingWeekLessonsByEnrollment($enrollment_id, $subject = null)
    {
        $enrollment = Enrollment::with('classRoom.lessons.attachments')->find($enrollment_id);

        if (!$enrollment || !$enrollment->classRoom) {
            return [
                'data' => null,
                'message' => 'Enrollment or classroom not found',
                'code' => 404
            ];
        }

        $today = Carbon::today();
        $nextWeek = Carbon::today()->addDays(7);

        $lessons = $enrollment->classRoom->lessons()
            ->whereDate('date', '>', $today)
            ->whereDate('date', '<=', $nextWeek)
            ->when($subject, function ($query, $subject) {
                $query->where('subject', $subject);
            })
            ->with('attachments')
            ->get();

        return [
            'data' => $lessons,
            'message' => 'Upcoming week lessons for the student retrieved successfully',
            'code' => 200
        ];
    }
    public function getLessonsByClassroom($classroom_id)
    {
        $classroom = ClassRoom::with(['lessons', 'user'])->find($classroom_id);

        if (!$classroom) {
            return response()->json([
                'data' => null,
                'message' => 'Classroom not found',
                'code' => 404
            ]);
        }

        return response()->json([
            'data' => [
                'classroom' => [
                    'id' => $classroom->id,
                    'class_name' => $classroom->class_name,
                    'level' => $classroom->level,
                    'teacher' => [
                        'id' => $classroom->user->id,
                        'name' => $classroom->user->name,
                        'email' => $classroom->user->email,
                    ],
                ],
                'lessons' => $classroom->lessons
            ],
            'message' => 'Lessons fetched successfully',
            'code' => 200
        ]);
    }

}
