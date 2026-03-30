<?php

namespace App\Services;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Exception\FirebaseException;

class FirebaseService
{
    protected Messaging $messaging;

    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    /**
     * إرسال إشعار لجميع التوكنات
     *
     * @param string|array $tokens
     * @param string $title
     * @param string $body
     * @param array $data
     * @return array<string, array>
     */
    public function sendNotification($tokens, string $title, string $body, array $data = []): array
    {
        if (!is_array($tokens)) {
            $tokens = [$tokens];
        }
        $tokens = array_unique($tokens);
        $results = [];

        foreach ($tokens as $token) {
            try {
                $message = CloudMessage::withTarget('token', $token)
                    ->withNotification(Notification::create($title, $body))
                    ->withData($data);

                $this->messaging->send($message);

                $results[$token] = ['success' => true];
            } catch (MessagingException $e) {
                $results[$token] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            } catch (FirebaseException $e) {
                $results[$token] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}
