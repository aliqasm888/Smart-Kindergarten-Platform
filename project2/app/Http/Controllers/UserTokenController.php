<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FcmToken;

class UserTokenController extends Controller
{
    public function saveToken(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'token'   => 'required|string',
        ]);

        // إذا التوكن موجود حدثه، إذا مش موجود أنشئه
        FcmToken::updateOrCreate(
            ['fcm_token' => $request->token],
            ['user_id' => $user->id]
        );

        return response()->json(['message' => 'Token saved successfully'],200);
    }
}
