<?php

namespace App\Notifications;

use App\Channels\Telegram;
use App\Models\Order;
use Illuminate\Notifications\Notification;

class NewOrderTelegramNotify extends Notification
{
    public function __construct(protected Order $order) {}

    public function via($notifiable)
    {
        return [Telegram::class];
    }

    public function toTelegram($notifiable)
    {
        $order = $this->order;
        $order->loadMissing(['partner', 'customer', 'orderProducts.product']);

        $op = $order->orderProducts->first();
        $product = $op?->product;
        $partner = $order->partner;

        $date = $op?->booking_date
            ? \Carbon\Carbon::parse($op->booking_date)->translatedFormat('j F Y')
            : '—';
        $time = $op?->booking_time ? substr($op->booking_time, 0, 5) : '—';

        $pax = $order->orderProducts
            ->flatMap->items
            ->sum('quantity');
        if ($pax === 0) {
            $pax = (int) $order->orderProducts->sum('quantity');
        }

        $amount = (float) $order->amount;
        $payment = $amount > 0
            ? '💳 Pagato <b>' . $this->money($amount) . '</b>'
            : '🎁 <b>Gratuito</b>';


        $text = "🎟️ <b>Nuovo ordine #{$order->order_number}</b>\n"
            . '📦 ' . $this->escape($product?->label ?? '—') . "\n"
            . '🏷️ ' . $this->escape($partner?->partner_name ?? '—') . "\n"
            . "📅 {$date}\n"
            . "🕐 {$time}\n"
            . "👥 {$pax} " . ($pax === 1 ? 'persona' : 'persone') . "\n"
            . $payment;

        return ['text' => $text];
    }

    private function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, ',', '.') . ' €';
    }
}
