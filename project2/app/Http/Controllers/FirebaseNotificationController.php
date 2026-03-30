<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging;

class FirebaseNotificationController extends Controller
{
    protected $messaging;

    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    public function sendNotification(Request $request)
    {
        $token = $request->token; // توكن الجهاز
        $title = $request->title;
        $body = $request->body;

        $notification = Notification::create($title, $body);
         
        $message = CloudMessage::withTarget('token', $token)
            ->withNotification($notification);

        $this->messaging->send($message);

        return response()->json(['success' => true]);
    }
}
