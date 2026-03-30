<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeacherRequest;
use App\Http\Requests\UpdateTeacherRequest;
use App\Http\Requests\UserSignupRequest;
use App\Models\Teacher;
use App\Services\TeacherServices;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class TeacherController extends Controller
{
    private TeacherServices $TeacherServices;
    use ApiResponseTrait;

    public function __construct(TeacherServices $TeacherServices)
    {
        $this->TeacherServices= $TeacherServices;

    }

    public function TeacherRegister(StoreTeacherRequest $request)
    {
        $data = [];
        try {
            $data = $this->TeacherServices->TeacherRegister($request);
            return $this->successResponse(
                $data['data'],
                $data['message'],
                $data['code']
            );
        } catch (\Throwable $throwable) {
            return $this->errorResponse(
                $data['data'] ?? null,
                $throwable->getMessage()
            );
        }
    }
    public function UpdateTeacher(UpdateTeacherRequest  $request)
    {
        $teacher = Teacher::where('user_id', $request->user()->id)->first();

        if (!$teacher) {
            return response()->json(['error' => 'Teacher not found'], 404);
        }

        $user = $teacher->user;

        // Validation
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone' => 'sometimes|string|max:20',
            'password' => 'sometimes|string|min:6',
            'gender' => 'sometimes|in:male,female',
            'birth_date' => 'sometimes|date',
            'experience_years' => 'sometimes|integer|min:0',
            'work_days' => 'sometimes|array',
            'work_hours' => 'sometimes|string|max:20',
            'profile_image' => 'sometimes|file|image|max:2048',
            'certificate_file' => 'sometimes|file|max:5120',
        ]);

        // تحديث بيانات المستخدم
        if (isset($validated['name'])) $user->name = $validated['name'];
        if (isset($validated['email'])) $user->email = $validated['email'];
        if (isset($validated['phone'])) $user->phone = $validated['phone'];
        if (isset($validated['password'])) $user->password = Hash::make($validated['password']);
        $user->save();

        // رفع الملفات واستبدال القديمة
        if ($request->hasFile('profile_image')) {
            if ($teacher->profile_image && Storage::exists($teacher->profile_image)) {
                Storage::delete($teacher->profile_image);
            }
            $teacher->profile_image = $request->file('profile_image')->store('teachers/profile_images', 'public');
        }

        if ($request->hasFile('certificate_file')) {
            if ($teacher->certificate_file && Storage::exists($teacher->certificate_file)) {
                Storage::delete($teacher->certificate_file);
            }
            $teacher->certificate_file = $request->file('certificate_file')->store('teachers/certificates', 'public');
        }

        // تحديث بيانات Teacher
        if (isset($validated['gender'])) $teacher->gender = $validated['gender'];
        if (isset($validated['birth_date'])) $teacher->birth_date = $validated['birth_date'];
        if (isset($validated['experience_years'])) $teacher->experience_years = $validated['experience_years'];
        if (isset($validated['work_days'])) $teacher->work_days = json_encode($validated['work_days']);
        if (isset($validated['work_hours'])) $teacher->work_hours = $validated['work_hours'];

        $teacher->save();

        // إعادة تحميل بيانات المستخدم مع الرولات والصلاحيات
        $user->load('roles', 'permissions');

        return response()->json([
            'data' => $user,
            'message' => 'Teacher updated successfully'
        ], 200);
    }

    public function ShowTeacher($id){
        $data = [];
        try {
            $data = $this->TeacherServices->ShowTeacher($id);

            if ($data['code'] === 200) {
                return $this->successResponse($data['data'], $data['message'], $data['code']);
            } else {
                return $this->errorResponse($data['data'], $data['message'], $data['code']);
            }

        } catch (\Throwable $throwable) {
            return $this->errorResponse(null, $throwable->getMessage());
        }
    }
    public function GetTeacher()
    {
        $data = [] ;
        try {
            $data =$this->TeacherServices->GetTeacher();
            return $this->successResponse(
                $data['data']
            );
        }
        catch (\Throwable $throwable){
            return $this->errorResponse($data['data'] = null , $throwable->getMessage());
        }


    }
    public function getUnassignedTeachers()
    {
        $data = [] ;
        try {
            $data =$this->TeacherServices->getUnassignedTeachers();
            return $this->successResponse(
                $data['data']
            );
        }
        catch (\Throwable $throwable){
            return $this->errorResponse($data['data'] = null , $throwable->getMessage());
        }


    }
    public function TeacherCount()
    {
        $data = [];
        try {
            $data = $this->TeacherServices->TeacherCount();

            if ($data['code'] === 200) {
                return $this->successResponse($data['data'], $data['message'], $data['code']);
            } else {
                return $this->errorResponse($data['data'], $data['message'], $data['code']);
            }

        } catch (\Throwable $throwable) {
            return $this->errorResponse(null, $throwable->getMessage());
        }
    }
    public function DeleteTeacher($id){
        $data = [];
        try {
            $data = $this->TeacherServices->DeleteTeacher($id);

            if ($data['code'] === 200) {
                return $this->successResponse($data['data'], $data['message'], $data['code']);
            } else {
                return $this->errorResponse($data['data'], $data['message'], $data['code']);
            }

        } catch (\Throwable $throwable) {
            return $this->errorResponse(null, $throwable->getMessage());
        }
    }





}
