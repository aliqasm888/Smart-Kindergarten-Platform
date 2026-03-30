<?php

namespace App\Services;

use App\Models\ClassRoom;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use function PHPUnit\Framework\isEmpty;

class EnrollmentServices
{
    public function enrollment($request): array
    {
        $user = User::query()->where('phone', $request['phone'])->first();
        if (!$user) {
            return ['data' => null, 'message' => 'User not found', 'code' => 404];
        }

        $classroom = ClassRoom::query()
            ->where('class_name', $request['class_name'])
            ->where('level', $request['level'])
            ->first();

        if (!$classroom) {
            return ['data' => null, 'message' => 'Classroom not found', 'code' => 404];
        }

        $studentCount = Enrollment::query()->where('classroom_id', $classroom->id)->count();
        if ($studentCount >= $classroom->max_students) {
            return ['data' => null, 'message' => 'Classroom is full', 'code' => 403];
        }

        $imagePath = null;
        if ($request->hasFile('profile_image') && $request->file('profile_image')->isValid()) {
            $image = $request->file('profile_image');
            $imageName = time() . '_' . $image->getClientOriginalName();

            // تخزين الصورة في storage/app/public/profile_images
            $imagePath = $image->storeAs('profile_images', $imageName, 'public');
        }

        // إنشاء التسجيل
        $enrollment = Enrollment::query()->create([
            'student_name' => $request['student_name'],
            'birth_date' => $request['birth_date'],
            'enrol_date' => Carbon::now(),
            'profile_image' => $imagePath, // يخزن فقط المسار النسبي
            'gender' => $request['gender'],
            'user_id' => $user->id,
            'classroom_id' => $classroom->id,
        ]);

        // تجهيز الرابط الكامل HTTP للعرض
        $profileImageUrl = $enrollment->profile_image
            ? asset('storage/' . $enrollment->profile_image)
            : null;

        $data = [
            'id' => $enrollment->id,
            'student_name' => $enrollment->student_name,
            'birth_date' => $enrollment->birth_date,
            'enrol_date' => $enrollment->enrol_date,
            'gender' => $enrollment->gender,
            'profile_image' => $profileImageUrl,
            'classroom' => [
                'id' => $classroom->id,
                'class_name' => $classroom->class_name,
                'level' => $classroom->level,
            ],
        ];

        return ['data' => $data, 'message' => 'Enrollment successful', 'code' => 200];
    }


    public function ShowEnrollment($id)
    {
        $enrollment = Enrollment::with('classRoom', 'user')->find($id);

        if (!$enrollment) {
            return [
                'data' => null,
                'message' => 'Enrollment not found',
                'code' => 404
            ];
        }

        // تجهيز الصورة كرابط HTTP
        $profileImageUrl = $enrollment->profile_image
            ? asset('storage/' . $enrollment->profile_image)
            : null;

        $data = [
            'id' => $enrollment->id,
            'student_name' => $enrollment->student_name,
            'birth_date' => $enrollment->birth_date,
            'enrol_date' => $enrollment->enrol_date,
            'profile_image' => $profileImageUrl,
            'gender' => $enrollment->gender,
            'phone' => $enrollment->user->phone ?? null, // رقم الهاتف من علاقة user
            'classroom' => [
                'id' => $enrollment->classRoom->id,
                'class_name' => $enrollment->classRoom->class_name,
                'level' => $enrollment->classRoom->level,
            ]
        ];

        return [
            'data' => $data,
            'message' => 'Enrollment show successfully',
            'code' => 200
        ];
    }

    public function GetAllEnrollment()
    {
        $enrollments = Enrollment::with('classRoom')->get();

        $data = $enrollments->map(function ($enrollment) {
            // تجهيز رابط HTTP مباشر للصورة
            $profileImageUrl = $enrollment->profile_image
                ? asset('storage/' . $enrollment->profile_image)
                : null;

            return [
                'id' => $enrollment->id,
                'student_name' => $enrollment->student_name,
                'birth_date' => $enrollment->birth_date,
                'enrol_date' => $enrollment->enrol_date,
                'gender' => $enrollment->gender,
                'profile_image' => $profileImageUrl,
                'classroom' => [
                    'id' => $enrollment->classRoom->id ?? null,
                    'class_name' => $enrollment->classRoom->class_name ?? null,
                    'level' => $enrollment->classRoom->level ?? null,
                ]
            ];
        });

        return [
            'data' => $data,
            'message' => 'Get all Enrollment successfully',
            'code' => 200
        ];
    }



    public function GetEnrollment()
    {
        try {
            $user = Auth::user(); // جلب المستخدم من التوكن

            if (!$user) {
                return [
                    'code' => 401,
                    'message' => 'Unauthorized - Invalid token',
                    'data' => null
                ];
            }

            // جلب جميع التسجيلات المرتبطة بالمستخدم
            $enrollments = Enrollment::where('user_id', $user->id)->get();

            $message = 'Get all Enrollment successfully';
            return [
                'data' => $enrollments,
                'message' => $message,
                'code' => 200
            ];

        } catch (\Exception $e) {
            return [
                'code' => 500,
                'message' => 'Server error',
                'data' => $e->getMessage()
            ];
        }
    }
    public function updateEnrollment($request, $enrollmentId): array
    {
        $enrollment = Enrollment::find($enrollmentId);
        if (!$enrollment) {
            return ['data' => null, 'message' => 'Enrollment not found', 'code' => 404];
        }

        // التحقق من الحقول المرسلة (Validation)
        $validated = $request->validate([
            'student_name' => 'sometimes|string|max:255',
            'birth_date' => 'sometimes|date',
            'gender' => 'sometimes|in:male,female',
            'profile_image' => 'sometimes|file|image|max:2048',
            'class_name' => 'sometimes|string', // لتغيير الصف
            'level' => 'sometimes|string',      // لتغيير مستوى الصف
        ]);

        // تحديث البيانات الأساسية
        if (isset($validated['student_name'])) $enrollment->student_name = $validated['student_name'];
        if (isset($validated['birth_date'])) $enrollment->birth_date = $validated['birth_date'];
        if (isset($validated['gender'])) $enrollment->gender = $validated['gender'];

        // تحديث الصف إذا تم تغييره
        if (isset($validated['class_name']) || isset($validated['level'])) {
            $classroom = ClassRoom::query()
                ->when(isset($validated['class_name']), fn($q) => $q->where('class_name', $validated['class_name']))
                ->when(isset($validated['level']), fn($q) => $q->where('level', $validated['level']))
                ->first();

            if (!$classroom) {
                return ['data' => null, 'message' => 'Classroom not found', 'code' => 404];
            }

            // التحقق من سعة الصف
            $studentCount = Enrollment::query()->where('classroom_id', $classroom->id)->count();
            if ($studentCount >= $classroom->max_students) {
                return ['data' => null, 'message' => 'Classroom is full', 'code' => 403];
            }

            $enrollment->classroom_id = $classroom->id;
        }

        // رفع الصورة واستبدال القديمة
        if ($request->hasFile('profile_image') && $request->file('profile_image')->isValid()) {
            if ($enrollment->profile_image && Storage::exists($enrollment->profile_image)) {
                Storage::delete($enrollment->profile_image);
            }
            $image = $request->file('profile_image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $imagePath = $image->storeAs('profile_images', $imageName, 'public');
            $enrollment->profile_image = $imagePath;
        }

        $enrollment->save();

        // تجهيز الرابط الكامل للصورة
        $profileImageUrl = $enrollment->profile_image
            ? asset('storage/' . $enrollment->profile_image)
            : null;

        $data = [
            'id' => $enrollment->id,
            'student_name' => $enrollment->student_name,
            'birth_date' => $enrollment->birth_date,
            'enrol_date' => $enrollment->enrol_date,
            'gender' => $enrollment->gender,
            'profile_image' => $profileImageUrl,
            'classroom' => $enrollment->classRoom ? [
                'id' => $enrollment->classRoom->id,
                'class_name' => $enrollment->classRoom->class_name,
                'level' => $enrollment->classRoom->level,
            ] : null,
        ];

        return ['data' => $data, 'message' => 'Enrollment updated successfully', 'code' => 200];
    }
    public function CanselEnrollment($id)
    {
        $enrollment = Enrollment::query()->find($id);

        if ($enrollment) {
            $enrollment->delete();
            $message = 'Enrollment deleted successfully';
            $code = 200;
        } else {
            $message = 'Enrollment not found';
            $code = 404;
        }

        return [
            'data' => null,
            'message' => $message,
            'code' => $code
        ];
    }
}
