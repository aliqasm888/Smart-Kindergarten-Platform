<?php

namespace App\Services;

use App\Models\User;
use Spatie\Permission\Models\Role;
class StudentServices
{
    public function StudentRegister($request):array
    {
        $user = User::query()->create([
            'name' => $request['name'],
            'phone' => $request['phone'],
            'email' => $request['email'],
            'password' =>bcrypt($request['password'])
        ])  ;

        $studentRole = Role::query()->where('name', 'student')->first();

        if (!$studentRole) {
            return ['error' => 'student role not found'];
        }

        $user->assignRole($studentRole);

        $permissions = $studentRole->permissions()->pluck('name')->toArray();
        $user->givePermissionTo($permissions);

        $user->load('roles', 'permissions');

        $user = User::query()->find($user['id']);
        $user = $this->appendRolesAndPermissions($user);
        $user['token'] = $user->createToken("token")->plainTextToken;

        $message = 'User created successfully';
        return ['data' => $user, 'message' => $message,'code' => 200];
    }
    public function GetStudent()
    {
        $teachers = User::role('student')->get();
        $message ='student get all Successfully';
        return ['data' => $teachers , 'message' => $message , 'code' => 200] ;

    }
    public function StudentCount(){
        $student = User::role('student')->count();
        $message ='student get all Successfully';
        return ['data' => $student , 'message' => $message , 'code' => 200] ;
    }
    public function Deletestudent($id)
    {
        $student = User::query()->find($id);

        if ($student) {
            $student->delete();
            $message = 'student deleted successfully';
            $code = 200;
        } else {
            $message = 'student not found';
            $code = 404;
        }

        return [
            'data' => null,
            'message' => $message,
            'code' => $code
        ];
    }
    public function ShowStudent($id) {
        try {
            $student = User::with('roles')->find($id);

            if (!$student) {
                return [
                    'code' => 404,
                    'message' => 'User not found',
                    'data' => null
                ];
            }

            if (!$student->hasRole('student')) {
                return [
                    'code' => 403,
                    'message' => 'The specified user is not a teacher',
                    'data' => null
                ];
            }

            return [
                'code' => 200,
                'data' => $student,
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
    public function UpdateStudent($request, $id) {
        $student = User::query()->find($id);

        if (!$student) {
            return [
                'code' => 404,
                'message' => 'Teacher not found',
                'data' => null
            ];
        }

        if (!$student->hasRole('student')) {
            return [
                'success' => 403,
                'message' => 'Only student with teacher role can be updated',
                'data' => null
            ];
        }

        $updateData = $request->only(['name', 'email', 'phone']);

        if ($request->has('password')) {
            $updateData['password'] = bcrypt($request->password);
        }

        $student->update($updateData);

        return [
            'code' => 200,
            'data' => $student->fresh(),
            'message' => 'Student updated successfully'
        ];
    }

    public function getStudentsByLevel($request)
    {
        $validated = $request->validate([
            'level' => 'required|in:KG1,KG2,KG3'
        ]);

        $level = $request->level;

        $students = User::role('student')
            ->whereHas('enrollments.classRoom', function ($query) use ($level) {
                $query->where('level', $level);
            })
            ->with(['enrollments' => function ($query) use ($level) {
                $query->whereHas('classRoom', function ($q) use ($level) {
                    $q->where('level', $level);
                });
            }, 'enrollments.classRoom'])
            ->select(['id', 'name', 'email'])
            ->get();

        $formattedStudents = $students->map(function ($student) {
            return [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'enrollments' => $student->enrollments->map(function ($enrollment) {
                    return [
                        'class_name' => $enrollment->classRoom->class_name ?? null,
                        'level' => $enrollment->classRoom->level ?? null,
                        'enrol_date' => $enrollment->enrol_date ?? null,
                    ];
                })
            ];
        });

        return [
            'code' => 200,
            'data' => $formattedStudents,
            'message' => 'Students retrieved successfully for level ' . $level
        ];
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
