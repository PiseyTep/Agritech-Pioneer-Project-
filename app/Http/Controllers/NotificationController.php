<?php
 use App\Services\FirebaseService;

class NotificationController extends Controller
{
    public function sendToUser(FirebaseService $firebase)
    {
        $token = 'your-device-token-here';
        $title = 'AgriTech Update!';
        $body = 'Your field report is ready.';

        $firebase->sendNotification($token, $title, $body);

        return response()->json(['status' => 'Notification sent!']);
    }
}
