<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserSigninRequest;
use App\Traits\ApiResponseTrait;
use App\Services\UserService;

class UserController extends Controller

{
    private UserService $userService;
    use ApiResponseTrait;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;

    }


    public function login(UserSigninRequest $request)
    {
        $data = [];
        try {
            $data = $this->userService->login($request);

            if ($data['code'] === 200) {
                return $this->successResponse($data['data'], $data['message'], $data['code']);
            } else {
                return $this->errorResponse($data['data'], $data['message'], $data['code']);
            }

        } catch (\Throwable $throwable) {
            return $this->errorResponse(null, $throwable->getMessage());
        }
    }



    public function logout()
    {
        $data = [];
        try {
            $data = $this->userService->logout();
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
}

