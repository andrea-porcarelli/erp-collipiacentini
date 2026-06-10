<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class Telegram
{
    public function send($notifiable, Notification $notification)
    {
        try {
            $chatId = $notifiable->routes['telegram'];
            $message = $notification->toTelegram($notifiable);

            $payload = [
                'chat_id' => $chatId,
                'text' => $message['text'],
                'parse_mode' => $message['parse_mode'] ?? 'HTML',
            ];

            if (!empty($message['url'])) {
                $buttonText = $message['button_text'] ?? 'Apri';
                $payload['reply_markup'] = json_encode([
                    'inline_keyboard' => [
                        [
                            ['text' => $buttonText, 'url' => $message['url']],
                        ],
                    ],
                ]);
            }

            $response = Http::post('https://api.telegram.org/bot' . config('services.telegram.bot_token') . '/sendMessage', $payload);
            if (!$response->successful()) {
                logger()->error('Telegram API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            logger()->error('Telegram Exception', [
                'message' => $e->getMessage(),
            ]);
        }
    }
}
