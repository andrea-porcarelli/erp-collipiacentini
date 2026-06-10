<?php

namespace App\Notifications;

use App\Channels\Telegram;
use Illuminate\Notifications\Notification;
use Throwable;

class ErrorTelegramNotify extends Notification
{
    protected Throwable $exception;
    protected array $context;

    public function __construct(Throwable $exception, array $context = [])
    {
        $this->exception = $exception;
        $this->context = $context;
    }

    public function via($notifiable)
    {
        return [Telegram::class];
    }

    public function toTelegram($notifiable)
    {
        $env = strtoupper(config('app.env', 'production'));
        $class = get_class($this->exception);
        $message = $this->exception->getMessage() !== ''
            ? $this->exception->getMessage()
            : '(no message)';
        $file = $this->exception->getFile() . ':' . $this->exception->getLine();

        $url = $this->context['url'] ?? null;
        $method = $this->context['method'] ?? null;
        $userId = $this->context['user_id'] ?? null;

        $text = "🚨 <b>Errore {$env}</b>\n"
            . '<b>' . $this->escape($class) . "</b>\n"
            . '<code>' . $this->escape($this->truncate($message, 500)) . "</code>\n"
            . '📄 ' . $this->escape($file);

        if ($url) {
            $text .= "\n🔗 " . $this->escape(($method ? $method . ' ' : '') . $url);
        }
        if ($userId) {
            $text .= "\n👤 user #" . (int) $userId;
        }

        return [
            'text' => $this->truncate($text, 3900),
        ];
    }

    private function truncate(string $text, int $max): string
    {
        return mb_strlen($text) > $max ? mb_substr($text, 0, $max - 1) . '…' : $text;
    }

    private function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
