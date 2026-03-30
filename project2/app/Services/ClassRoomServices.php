<?php

namespace App\Services;

use App\Models\ClassRoom;
use App\Models\Enrollment;
use App\Models\User;
use function PHPUnit\Framework\isEmpty;

class ClassRoomServices
{
    public function AddClassRoom($request)
    {
        $user = User::query()->where('name', $request["user_name"])->first();
        if (!$user) {
            $message = 'User not found';
            $code = 404;
            return ['data' => null, 'message' => $message, 'code' => $code];
        }

        // 🔍 تحقق إذا عنده صف مسبق
        $exists = ClassRoom::query()->where('user_id', $user->id)->exists();
        if ($exists) {
            $message = 'This user already has a classroom';
            $code = 400;
            return ['data' => null, 'message' => $message, 'code' => $code];
        }

        // ✅ إنشاء الصف
        $ClassRoom = ClassRoom::query()->create([
            'user_id' => $user->id,
            'class_name' => $request["class_name"],
            'max_students' => $request['max_students'],
            'level' => $request['level']
        ]);

        $message = 'Classroom created successfully';
        return ['data' => $ClassRoom, 'message' => $message, 'code' => 200];
    }

    public function ShowClassRoom($id){

        $Class = ClassRoom::with('user:id,name')->find($id); // جلب العلاقة مع الاسم فقط

        if (!$Class) {
            $message = 'classRoom not found';
            $code = 404;
            return ['data' => null, 'message' => $message, 'code' => $code];
        }

        // تعديل البيانات بحيث يرجع اسم اليوزر بدل user_id
        $data = [
            'id' => $Class->id,
            'class_name' => $Class->class_name,
            'max_students' => $Class->max_students,
            'level' => $Class->level,
            'user_name' => $Class->user->name ?? null, // اسم اليوزر بدل user_id
            'created_at' => $Class->created_at,
            'updated_at' => $Class->updated_at,
        ];

        return ['data' => $data, 'message' => 'Class show successfully', 'code' => 200];
    }
    public function GetClassRoom()
    {
        $ClassRooms = ClassRoom::with('teacher:id,name') // جلب اسم المعلم فقط
        ->withCount('enrollments') // عدد الطلاب
        ->get()
            ->map(function ($classroom) {
                return [
                    'id' => $classroom->id,
                    'class_name' => $classroom->class_name,
                    'max_students' => $classroom->max_students,
                    'level' => $classroom->level,
                    'teacher_name' => $classroom->teacher->name,
                    'students_count' => $classroom->enrollments_count,
                ];
            });

        $message = 'Get all Classes successfully';
        return ['data' => $ClassRooms, 'message' => $message, 'code' => 200];
    }
    public function UpdateClassRoom($request, $id)
    {
        $classRoom = ClassRoom::find($id);

        if (!$classRoom) {
            return [
                'code' => 404,
                'message' => 'ClassRoom not found',
                'data' => null
            ];
        }

        $updateData = [];

        // Update user_id if user_name is provided
        if ($request->has('user_name')) {
            $user = User::where('name', $request->user_name)->first();
            $updateData['user_id'] = $user->id;
        }

        // Add other fields if they exist in request
        $fields = ['class_name', 'max_students', 'level'];
        foreach ($fields as $field) {
            if ($request->has($field)) {
                $updateData[$field] = $request->$field;
            }
        }

        // Only update if there are changes
        if (!empty($updateData)) {
            $classRoom->update($updateData);
        }

        return [
            'code' => 200,
            'data' => $classRoom->fresh(),
            'message' => 'ClassRoom updated successfully'
        ];
    }
    public function DeleteClassRoom($id)
    {
        $Classroom = ClassRoom::query()->find($id);

        if ($Classroom) {
            $Classroom->delete();
            $message = 'Class deleted successfully';
            $code = 200;
        } else {
            $message = 'Class not found';
            $code = 404;
        }

        return [
            'data' => null,
            'message' => $message,
            'code' => $code
        ];
    }
    public function ClassCount(){
        $ClassCount =ClassRoom::query()->count();
        $message ='Class get all Successfully';
        return ['data' => $ClassCount , 'message' => $message , 'code' => 200] ;
    }
}
