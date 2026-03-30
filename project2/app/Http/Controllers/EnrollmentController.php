<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnrollmentRequest;
use App\Http\Requests\UpdateEnrollmentRequest;
use App\Services\EnrollmentServices;
use App\Traits\ApiResponseTrait;

class EnrollmentController extends Controller
{
    private EnrollmentServices $EnrollmentServices ;
    use ApiResponseTrait ;

    public function __construct(EnrollmentServices $EnrollmentServices)
    {
        $this->EnrollmentServices = $EnrollmentServices ;
    }
    public function enrollment(EnrollmentRequest $request)
    {
        $data = [];
        try {
            $data = $this->EnrollmentServices->enrollment($request);
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
    public function GetAllEnrollment()
    {
        $data = [];
        try {
            $data = $this->EnrollmentServices->GetAllEnrollment();

            if ($data['code'] === 200) {
                return $this->successResponse($data['data'], $data['message'], $data['code']);
            } else {
                return $this->errorResponse($data['data'], $data['message'], $data['code']);
            }

        } catch (\Throwable $throwable) {
            return $this->errorResponse(null, $throwable->getMessage());
        }
    }
    public function GetEnrollment()
    {
        $data = [];
        try {
            $data = $this->EnrollmentServices->GetEnrollment();

            if ($data['code'] === 200) {
                return $this->successResponse($data['data'], $data['message'], $data['code']);
            } else {
                return $this->errorResponse($data['data'], $data['message'], $data['code']);
            }

        } catch (\Throwable $throwable) {
            return $this->errorResponse(null, $throwable->getMessage());
        }
    }
    public function ShowEnrollment($id){
        $data = [];
        try {
            $data = $this->EnrollmentServices->ShowEnrollment($id);

            if ($data['code'] === 200) {
                return $this->successResponse($data['data'], $data['message'], $data['code']);
            } else {
                return $this->errorResponse($data['data'], $data['message'], $data['code']);
            }

        } catch (\Throwable $throwable) {
            return $this->errorResponse(null, $throwable->getMessage());
        }
    }
    public function UpdateEnrollment(UpdateEnrollmentRequest $request , $id){
        $data = [];
        try {
            $data = $this->EnrollmentServices->UpdateEnrollment($request,$id);

            if ($data['code'] === 200) {
                return $this->successResponse($data['data'], $data['message'], $data['code']);
            } else {
                return $this->errorResponse($data['data'], $data['message'], $data['code']);
            }

        } catch (\Throwable $throwable) {
            return $this->errorResponse(null, $throwable->getMessage());
        }
    }
    public function CanselEnrollment($id)
    {
        $data = [];
        try {
            $data = $this->EnrollmentServices->CanselEnrollment($id);

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
