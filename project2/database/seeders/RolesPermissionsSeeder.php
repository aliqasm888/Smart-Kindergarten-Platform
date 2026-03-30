<?php

namespace Database\Seeders;

use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // create roles
        $adminRole = Role::create(['name' => 'admin']);
        $teacherRole = Role::create(['name'=>'teacher']);
        $studentRole = Role::create(['name'=> 'student']);
        //define permissions
        $permissions = [
            'getStudentsByLevel','StudentCount','DeleteStudent','UpdateStudent','ShowStudent','GetStudent','GetStudent','StudentRegister',
            'DeleteTeacher','UpdateTeacher','ShowTeacher','GetTeacher','TeacherRegister','CanselEnrollment','UpdateEnrollment','ShowEnrollment',
            'GetAllEnrollment',  'enrollments.create' ,'DeleteClassRoom' ,'GetClassRoom' , 'ShowClassRoom' ,'UpdateClassRoom' ,'AddClassRoom'
        ];

        foreach ($permissions as $permission){
            Permission::findOrCreate(($permission) , 'web');
        }
        // Assign permissions to roles
        $adminRole->syncPermissions($permissions); // delete old permissions and keep those inside $permissions
        $teacherRole->givePermissionTo(['enrollments.create']); // add permissions on top of old ones
        $studentRole->givePermissionTo(['enrollments.create']);

        // create user and assign roles
        $adminUser = User::factory()->create([
            'name' => 'AdminUser',
            'email'=> 'admin@gmail.com',
            'phone' => '0955555555',
            'password' => bcrypt('password'),
        ]);
        $adminUser->assignRole($adminRole);

        $permissions =$adminRole->permissions()->pluck('name')->toArray();
        $adminUser->givePermissionTo($permissions);


        $teacherUser = User::factory()->create([
            'name' => 'teacherUser',
            'email'=> 'teacher@gmail.com',
            'phone' => '0944444444',
            'password' => bcrypt('password'),
        ]);
        $teacherUser->assignRole($teacherRole);
        $permissions = $teacherRole->permissions()->pluck('name')->toArray();
        $teacherUser->givePermissionTo($permissions);

        $class = ClassRoom::query()->create([
            'user_id' => '2' ,
           'class_name' => 'firstClass',
           'max_students' => '15',
            'level' => 'KG1'
        ]);


    }
}
