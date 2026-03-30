<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
    /**
     * استجابة ناجحة
     */
    public function successResponse($data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'data' => $data,
            'message' => $message,
        ], $code);
    }

    /**
     * استجابة فشل أو خطأ
     */
    public function errorResponse($data = null, string $message = 'Error', int $code = 400): JsonResponse
    {
        return response()->json([
            'status' => 0,
            'data' => $data,
            'message' => $message,
        ], $code);
    }
}
