<?php

namespace App\Services;

use App\Models\ClassRoom;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class UserService
{




    public function login($request): array
    {
        $user = User::query()->where('email', $request['email'])->first();

        if (!$user) {
            return [
                'data' => [],
                'message' =>  'User not found',
                'code' => 404
            ];
        }

        if (!Auth::attempt(['email' => $request['email'], 'password' => $request['password']])) {
            return [
                'data' => [],
                'message' => 'Username and password do not match',
                'code' => 401
            ];
        }

        // احتفظ بالكائن الأصلي
        $userModel = $user;

        // أضف الأدوار والصلاحيات بدون ما تغير الأصل
        $userData = $this->appendRolesAndPermissions($userModel);

        // أنشئ التوكن على الكائن الأصلي
        $userData['token'] = $userModel->createToken("token")->plainTextToken;

        return [
            'data' => $userData,
            'message' => 'User logged in successfully',
            'code' => 200
        ];
    }


    public function logout():array
    {
        $user =Auth::user();
        if (!is_null(Auth::user())){
            Auth::user()->currentAccessToken()->delete();
            $message = 'User logged out successfully .';
            $code = 200 ;
        }
        else
        {
            $message = 'invalid Token.';
            $code = 404 ;
        }
        return ['data' => $user , 'message' => $message , 'code' => $code] ;
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
