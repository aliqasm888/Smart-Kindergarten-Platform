<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateClassRequest;
use App\Http\Requests\UpdateClassRoomRequest;
use App\Services\ClassRoomServices;
use App\Traits\ApiResponseTrait;

class ClassRoomController extends Controller
{
    private ClassRoomServices $ClassRoomServices ;
    use ApiResponseTrait ;

    public function __construct(ClassRoomServices $ClassRoomServices)
    {
        $this->ClassRoomServices = $ClassRoomServices ;
    }
    public function AddClassRoom (CreateClassRequest $request)
    {
        $data = [];
        try {
            $data = $this->ClassRoomServices->AddClassRoom($request);

            if ($data['code'] === 200) {
                return $this->successResponse($data['data'], $data['message'], $data['code']);
            } else {
                return $this->errorResponse($data['data'], $data['message'], $data['code']);
            }

        } catch (\Throwable $throwable) {
            return $this->errorResponse(null, $throwable->getMessage());
        }

    }
    public function ShowClassRoom($id){

        $data = [];
        try {
            $data = $this->ClassRoomServices->ShowClassRoom($id);

            if ($data['code'] === 200) {
                return $this->successResponse($data['data'], $data['message'], $data['code']);
            } else {
                return $this->errorResponse($data['data'], $data['message'], $data['code']);
            }

        } catch (\Throwable $throwable) {
            return $this->errorResponse(null, $throwable->getMessage());
        }

    }
    public function GetClassRoom(){
        $data = [];
        try {
            $data = $this->ClassRoomServices->GetClassRoom();

            if ($data['code'] === 200) {
                return $this->successResponse($data['data'], $data['message'], $data['code']);
            } else {
                return $this->errorResponse($data['data'], $data['message'], $data['code']);
            }

        } catch (\Throwable $throwable) {
            return $this->errorResponse(null, $throwable->getMessage());
        }


    }

    public function UpdateClassRoom(UpdateClassRoomRequest $request , $id){
        $data = [];
        try {
            $data = $this->ClassRoomServices->UpdateClassRoom($request,$id);

            if ($data['code'] === 200) {
                return $this->successResponse($data['data'], $data['message'], $data['code']);
            } else {
                return $this->errorResponse($data['data'], $data['message'], $data['code']);
            }

        } catch (\Throwable $throwable) {
            return $this->errorResponse(null, $throwable->getMessage());
        }

    }
    public function DeleteClassRoom($id){
        $data = [];
        try {
            $data = $this->ClassRoomServices->DeleteClassRoom($id);

            if ($data['code'] === 200) {
                return $this->successResponse($data['data'], $data['message'], $data['code']);
            } else {
                return $this->errorResponse($data['data'], $data['message'], $data['code']);
            }

        } catch (\Throwable $throwable) {
            return $this->errorResponse(null, $throwable->getMessage());
        }
    }
    public function ClassCount()
    {
        $data = [];
        try {
            $data = $this->ClassRoomServices->ClassCount();

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
