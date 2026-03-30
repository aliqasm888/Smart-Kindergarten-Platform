<?php

namespace App\Services;

use App\Http\Requests\UserSignupRequest;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class TeacherServices
{
    public function TeacherRegister($request): array
    {
        $user = User::create([
            'name' => $request['name'],
            'phone' => $request['phone'],
            'email' => $request['email'],
            'password' => Hash::make($request['password']),
        ]);

        $teacherRole = Role::where('name', 'teacher')->first();

        if (!$teacherRole) {
            return ['error' => 'Teacher role not found'];
        }

        $user->assignRole($teacherRole);
        $permissions = $teacherRole->permissions()->pluck('name')->toArray();
        $user->givePermissionTo($permissions);


        $profileImagePath = $request->hasFile('profile_image') ?
            $request->file('profile_image')->store('teachers/profile_images', 'public') : null;

        $certificatePath = $request->hasFile('certificate_file') ?
            $request->file('certificate_file')->store('teachers/certificates', 'public') : null;


        Teacher::create([
            'user_id' => $user->id,
            'gender' => $request['gender'],
            'birth_date' => $request['birth_date'],
            'experience_years' => $request['experience_years'],
            'profile_image' => $profileImagePath,
            'certificate_file' => $certificatePath,
            'work_days' => json_encode($request['work_days']), // يجب أن يكون مصفوفة
            'work_hours' => $request['work_hours'], // مثال: "09:00-14:00"
        ]);

        $user->load('roles', 'permissions');
        $user = $this->appendRolesAndPermissions($user);
        $user['token'] = $user->createToken("token")->plainTextToken;

        $message = 'Teacher registered successfully';
        return ['data' => $user, 'message' => $message, 'code' => 200];
    }

    public function GetTeacher()
    {
        $teachers = User::role('teacher')
            ->with(['teacherInfo'])
            ->get();

        $message = 'Teachers retrieved successfully';

        return [
            'data' => $teachers,
            'message' => $message,
            'code' => 200
        ];
    }
    public function DeleteTeacher($id)
    {
        try {
            $teacher = User::with('teacher')->find($id);

            if (!$teacher) {
                return [
                    'code' => 404,
                    'message' => 'User not found',
                    'data' => null
                ];
            }

            if (!$teacher->hasRole('teacher')) {
                return [
                    'code' => 403,
                    'message' => 'Only users with teacher role can be deleted',
                    'data' => null
                ];
            }

            // Additional check: Ensure teacher has no assigned classes
            if ($teacher->teacher()->exists()) {
                return [
                    'code' => 403,
                    'message' => 'Cannot delete teacher with assigned classes',
                    'data' => null
                ];
            }

            $teacher->delete();

            return [
                'code' => 200,
                'message' => 'Teacher deleted successfully',
                'data' => null
            ];

        } catch (\Exception $e) {
            return [
                'code' => 500,
                'message' => 'Failed to delete teacher',
                'data' => $e->getMessage()
            ];
        }
    }
    public function ShowTeacher($id)
    {
        try {
            $teacher = User::with( 'teacherInfo')->find($id);

            if (!$teacher) {
                return [
                    'code' => 404,
                    'message' => 'User not found',
                    'data' => null
                ];
            }

            if (!$teacher->hasRole('teacher')) {
                return [
                    'code' => 403,
                    'message' => 'The specified user is not a teacher',
                    'data' => null
                ];
            }

            return [
                'code' => 200,
                'data' => $teacher,
                'message' => 'Teacher details retrieved successfully'
            ];

        } catch (\Exception $e) {
            return [
                'code' => 500,
                'message' => 'Server error',
                'data' => $e->getMessage()
            ];
        }
    }
    public function UpdateTeacher($request, $id)
    {
        $teacher = User::query()->find($id);

        if (!$teacher) {
            return [
                'code' => 404,
                'message' => 'Teacher not found',
                'data' => null
            ];
        }

        if (!$teacher->hasRole('teacher')) {
            return [
                'code' => 403,
                'message' => 'Only users with teacher role can be updated',
                'data' => null
            ];
        }

        $updateData = $request->only(['name', 'phone']);

        // إذا بعت إيميل جديد ومختلف، حدّثه
        if ($request->filled('email') && $request->email !== $teacher->email) {
            $updateData['email'] = $request->email;
        } else {
            // غير هيك، خليه نفس القديم
            $updateData['email'] = $teacher->email;
        }

        // تحديث الباسوورد إذا بعت وحدة
        if ($request->filled('password')) {
            $updateData['password'] = bcrypt($request->password);
        }

        $teacher->update($updateData);

        return [
            'code' => 200,
            'data' => $teacher->fresh(),
            'message' => 'Teacher updated successfully'
        ];
    }


    public function getUnassignedTeachers()
    { $teachers = Teacher::whereNotIn('user_id', function ($query) {
        $query->select('user_id')->from('class_rooms');
    })->with('user') // عشان يرجع بيانات اليوزر المرتبط
    ->get();

        return [
            'data' => $teachers,
            'message' => 'Teachers without classrooms retrieved successfully',
            'code' => 200
        ];
    }
    public function TeacherCount(){
        $student = User::role('teacher')->count();
        $message ='teacher get all Successfully';
        return ['data' => $student , 'message' => $message , 'code' => 200] ;
    }



    private function appendRolesAndPermissions($user){
        $roles = [];
        foreach ($user->roles as $role) {
            $roles[] = $role->name;
        }
        unset($user['roles']);
        $user['roles']=$roles;

        $permissions =[];
        foreach ($user->permissions as $permission) {
            $permissions[] = $permission->name;
        }
        unset($user['permissions']);
        $user['permissions']=$permissions;
        return $user;
    }
}
