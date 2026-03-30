<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

class Response
{
    public function Success($data,$message,$code=200):JsonResponse
    {
        return response()->json([
            'status'=>1,
            'data'=>$data,
            'message'=>$message,

        ],$code);
    }
    public function Error($data, $message, $code = 500): JsonResponse
    {
        return response()->json([
            'status' => 0,
            'data' => $data ?? [],
            'message' => $message,
        ], $code);
    }


    public function Validation($data,$message,$code=422):JsonResponse
    {
        return response()->json([
            'status'=>0,
            'data'=>$data,
            'message'=>$message,

        ],$code);

    }

}
