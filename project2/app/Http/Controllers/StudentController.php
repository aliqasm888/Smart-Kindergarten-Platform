<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateTeacherRequest;
use App\Http\Requests\UserSignupRequest;
use App\Services\StudentServices;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    private StudentServices $StudentServices;
    use ApiResponseTrait;

    public function __construct(StudentServices $StudentServices)
    {
        $this->StudentServices= $StudentServices;

    }

    public function StudentRegister(UserSignupRequest $request)
    {
        $data = [];
        try {
            $data = $this->StudentServices->StudentRegister($request);
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
    public function UpdateStudent(UpdateTeacherRequest $request ,$id){
        $data = [];
        try {
            $data = $this->StudentServices->UpdateStudent($request,$id);

            if ($data['code'] === 200) {
                return $this->successResponse($data['data'], $data['message'], $data['code']);
            } else {
                return $this->errorResponse($data['data'], $data['message'], $data['code']);
            }

        } catch (\Throwable $throwable) {
            return $this->errorResponse(null, $throwable->getMessage());
        }
    }
    public function ShowStudent($id){
        $data = [];
        try {
            $data = $this->StudentServices->ShowStudent($id);

            if ($data['code'] === 200) {
                return $this->successResponse($data['data'], $data['message'], $data['code']);
            } else {
                return $this->errorResponse($data['data'], $data['message'], $data['code']);
            }

        } catch (\Throwable $throwable) {
            return $this->errorResponse(null, $throwable->getMessage());
        }
    }
    public function GetStudent()
    {
        $data = [] ;
        try {
            $data =$this->StudentServices->GetStudent();
            return $this->successResponse(
                $data['data']
            );
        }
        catch (\Throwable $throwable){
            return $this->errorResponse($data['data'] = null , $throwable->getMessage());
        }


    }
    public function DeleteStudent($id){
        $data = [];
        try {
            $data = $this->StudentServices->Deletestudent($id);

            if ($data['code'] === 200) {
                return $this->successResponse($data['data'], $data['message'], $data['code']);
            } else {
                return $this->errorResponse($data['data'], $data['message'], $data['code']);
            }

        } catch (\Throwable $throwable) {
            return $this->errorResponse(null, $throwable->getMessage());
        }
    }
    public function StudentCount(){
        $data = [];
        try {
            $data = $this->StudentServices->StudentCount();

            if ($data['code'] === 200) {
                return $this->successResponse($data['data'], $data['message'], $data['code']);
            } else {
                return $this->errorResponse($data['data'], $data['message'], $data['code']);
            }

        } catch (\Throwable $throwable) {
            return $this->errorResponse(null, $throwable->getMessage());
        }
    }
    public function getStudentsByLevel(Request $request)
    {
        $data = [];
        try {
            $data = $this->StudentServices->getStudentsByLevel($request);

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
