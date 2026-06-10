<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OrderLogger
{
    /**
     * Causer "forzato" per la prossima scrittura (utile durante il flusso
     * carrello dove l'utente non è autenticato come User ma vogliamo
     * comunque attribuire l'azione al Customer associato).
     */
    protected ?object $forcedCauser = null;

    /**
     * UUID di raggruppamento per più log generati nella stessa operazione.
     */
    protected ?string $batchUuid = null;

    public function as(?object $causer): self
    {
        $this->forcedCauser = $causer;

        return $this;
    }

    public function batch(?string $uuid = null): self
    {
        $this->batchUuid = $uuid ?? (string) Str::uuid();

        return $this;
    }

    public function endBatch(): void
    {
        $this->batchUuid = null;
        $this->forcedCauser = null;
    }

    /**
     * Scrittura grezza — usata internamente dai metodi helper, ma esposta
     * per casi non coperti.
     */
    public function write(array $attributes): OrderLog
    {
        $causer = $this->resolveCauser();
        $request = function_exists('request') ? request() : null;

        return OrderLog::create(array_merge([
            'causer_type' => $causer ? $causer::class : null,
            'causer_id'   => $causer?->getKey(),
            'causer_name' => $this->causerName($causer),
            'properties'  => null,
            'context'     => $this->buildContext($request),
            'batch_uuid'  => $this->batchUuid,
        ], $attributes));
    }

    // ─── Eventi fase carrello ───────────────────────────────────────────

    public function logCartStarted(Cart $cart, array $itemsSummary, float $total): OrderLog
    {
        $date = $cart->date ? \Carbon\Carbon::parse($cart->date)->translatedFormat('j F Y') : '—';
        $time = $cart->time ? substr($cart->time, 0, 5) : '—';
        $tickets = collect($itemsSummary)->sum('quantity');

        return $this->write([
            'cart_id'     => $cart->id,
            'session_id'  => $cart->session_id,
            'event_type'  => 'cart_started',
            'description' => sprintf(
                'Carrello avviato per il %s alle %s · %d biglietti · %s €',
                $date,
                $time,
                $tickets,
                number_format($total, 2, ',', '.')
            ),
            'properties'  => [
                'date'      => $cart->date,
                'time'      => $cart->time,
                'slot_type' => $cart->slot_type,
                'slot_id'   => $cart->slot_id,
                'items'     => $itemsSummary,
                'total'     => $total,
            ],
        ]);
    }

    public function logCartCustomerAssigned(Cart $cart, Customer $customer): OrderLog
    {
        return $this->write([
            'cart_id'     => $cart->id,
            'session_id'  => $cart->session_id,
            'event_type'  => 'cart_customer_assigned',
            'description' => sprintf(
                'Cliente %s associato al carrello',
                trim(($customer->name ?? '') . ' ' . ($customer->surname ?? '')) ?: ($customer->email ?? '#' . $customer->id)
            ),
            'properties'  => [
                'customer_id'    => $customer->id,
                'customer_email' => $customer->email,
            ],
        ]);
    }

    public function logCartConsentsAccepted(Cart $cart, array $consentsPayload): OrderLog
    {
        $accepted = collect($consentsPayload)->where('accepted', true)->count();
        $total = count($consentsPayload);

        return $this->write([
            'cart_id'     => $cart->id,
            'session_id'  => $cart->session_id,
            'event_type'  => 'cart_consents_accepted',
            'description' => sprintf('Consensi compilati: %d/%d accettati', $accepted, $total),
            'properties'  => ['consents' => $consentsPayload],
        ]);
    }

    public function logCartRemoved(Cart $cart, string $reason = 'manuale'): OrderLog
    {
        return $this->write([
            'cart_id'     => $cart->id,
            'session_id'  => $cart->session_id,
            'event_type'  => 'cart_removed',
            'description' => sprintf('Carrello svuotato (%s)', $reason),
            'properties'  => ['reason' => $reason],
        ]);
    }

    /**
     * Sposta i log del cart sull'ordine appena creato. Va chiamato dentro
     * createOrderFromCart prima che il cart venga eventualmente cancellato.
     */
    public function promoteCartLogs(Cart $cart, Order $order): int
    {
        return OrderLog::where('cart_id', $cart->id)
            ->whereNull('order_id')
            ->update(['order_id' => $order->id]);
    }

    // ─── Eventi ciclo di vita ordine ────────────────────────────────────

    public function logOrderCreated(Order $order): OrderLog
    {
        $op = $order->orderProducts()->first();
        $date = $op?->booking_date ? \Carbon\Carbon::parse($op->booking_date)->translatedFormat('j F Y') : '—';
        $time = $op?->booking_time ? substr($op->booking_time, 0, 5) : '—';

        return $this->write([
            'order_id'    => $order->id,
            'cart_id'     => null,
            'event_type'  => 'order_created',
            'description' => sprintf('Ordine #%s creato per il %s alle %s', $order->order_number, $date, $time),
            'properties'  => [
                'order_number' => $order->order_number,
                'amount'       => (float) $order->amount,
            ],
        ]);
    }

    public function logOrderPaid(Order $order): OrderLog
    {
        $cardLabel = $order->card_brand
            ? ucfirst($order->card_brand) . ' · ' . ($order->card_last4 ?? '••••')
            : 'metodo non specificato';

        return $this->write([
            'order_id'    => $order->id,
            'event_type'  => 'order_paid',
            'description' => sprintf('Pagamento di %s € completato (%s)', number_format((float) $order->amount, 2, ',', '.'), $cardLabel),
            'properties'  => [
                'amount'       => (float) $order->amount,
                'card_brand'   => $order->card_brand,
                'card_last4'   => $order->card_last4,
            ],
        ]);
    }

    public function logOrderFailed(Order $order, ?string $error): OrderLog
    {
        return $this->write([
            'order_id'    => $order->id,
            'event_type'  => 'order_failed',
            'description' => 'Pagamento fallito' . ($error ? ': ' . $error : ''),
            'properties'  => ['error' => $error],
        ]);
    }

    // ─── Eventi backoffice ──────────────────────────────────────────────

    public function logBookingChanged(Order $order, ?string $oldDate, ?string $oldTime, ?string $newDate, ?string $newTime): OrderLog
    {
        $oldDateFmt = $oldDate ? \Carbon\Carbon::parse($oldDate)->translatedFormat('j F Y') : '—';
        $newDateFmt = $newDate ? \Carbon\Carbon::parse($newDate)->translatedFormat('j F Y') : '—';
        $oldTimeFmt = $oldTime ? substr($oldTime, 0, 5) : '—';
        $newTimeFmt = $newTime ? substr($newTime, 0, 5) : '—';

        return $this->write([
            'order_id'    => $order->id,
            'event_type'  => 'booking_changed',
            'description' => sprintf(
                'Data/orario visita: %s ore %s → %s ore %s',
                $oldDateFmt, $oldTimeFmt, $newDateFmt, $newTimeFmt
            ),
            'properties'  => [
                'from' => ['date' => $oldDate, 'time' => $oldTime],
                'to'   => ['date' => $newDate, 'time' => $newTime],
            ],
        ]);
    }

    public function logCustomerStatusChanged(Order $order, ?string $oldStatus, ?string $newStatus): OrderLog
    {
        $labels = [
            'booked'    => 'Prenotato',
            'confirmed' => 'Confermato',
            'completed' => 'Completato',
            'no_show'   => 'No show',
            'cancelled' => 'Annullato',
        ];

        return $this->write([
            'order_id'    => $order->id,
            'event_type'  => 'customer_status_changed',
            'description' => sprintf(
                'Stato cliente: %s → %s',
                $labels[$oldStatus] ?? ($oldStatus ?: '—'),
                $labels[$newStatus] ?? ($newStatus ?: '—')
            ),
            'properties'  => ['from' => $oldStatus, 'to' => $newStatus],
        ]);
    }

    public function logNotesUpdated(Order $order, array $changes): OrderLog
    {
        $fields = [];
        if (array_key_exists('customer_note', $changes)) {
            $fields[] = 'note cliente';
        }
        if (array_key_exists('internal_note', $changes)) {
            $fields[] = 'note interne';
        }

        return $this->write([
            'order_id'    => $order->id,
            'event_type'  => 'notes_updated',
            'description' => 'Aggiornate ' . (empty($fields) ? 'note' : implode(' e ', $fields)),
            'properties'  => $changes,
        ]);
    }

    public function logCustomerUpdated(Order $order, array $changes): OrderLog
    {
        $labelsByField = [
            'name'        => 'nome',
            'surname'     => 'cognome',
            'email'       => 'email',
            'phone'       => 'telefono',
            'prefix_phone' => 'prefisso',
            'address'     => 'indirizzo',
            'country_id'  => 'paese',
            'fiscal_code' => 'codice fiscale',
        ];
        $changedFields = array_values(array_intersect_key($labelsByField, $changes));

        return $this->write([
            'order_id'    => $order->id,
            'event_type'  => 'customer_updated',
            'description' => empty($changedFields)
                ? 'Aggiornati dati cliente'
                : 'Aggiornati dati cliente: ' . implode(', ', $changedFields),
            'properties'  => $changes,
        ]);
    }

    public function logEmailSent(Order $order, string $recipient, string $kind = 'confirmation'): OrderLog
    {
        $kindLabels = [
            'confirmation' => 'conferma ordine',
        ];
        $label = $kindLabels[$kind] ?? $kind;

        return $this->write([
            'order_id'    => $order->id,
            'event_type'  => 'email_sent',
            'description' => sprintf('Email di %s inviata a %s', $label, $recipient),
            'properties'  => ['recipient' => $recipient, 'kind' => $kind],
        ]);
    }

    public function logReceiptDownloaded(Order $order): OrderLog
    {
        return $this->write([
            'order_id'    => $order->id,
            'event_type'  => 'receipt_downloaded',
            'description' => sprintf('Ricevuta dell\'ordine #%s scaricata', $order->order_number),
        ]);
    }

    /**
     * Cambio check-in. $changes è un array di
     * ['participant_id' => int, 'from' => string, 'to' => string, 'code' => string|null].
     */
    public function logCheckinChanged(Order $order, array $changes): OrderLog
    {
        $statusLabels = [
            'booked'     => 'Prenotato',
            'checked_in' => 'Arrivato',
            'no_show'    => 'No show',
            'refunded'   => 'Rimborsato',
            'cancelled'  => 'Annullato',
        ];

        $count = count($changes);
        $description = $count === 1
            ? sprintf(
                'Biglietto %s: %s → %s',
                $changes[0]['code'] ?? ('#' . ($changes[0]['participant_id'] ?? '?')),
                $statusLabels[$changes[0]['from'] ?? ''] ?? ($changes[0]['from'] ?? '—'),
                $statusLabels[$changes[0]['to'] ?? ''] ?? ($changes[0]['to'] ?? '—'),
            )
            : sprintf('%d biglietti aggiornati', $count);

        return $this->write([
            'order_id'    => $order->id,
            'event_type'  => 'checkin_changed',
            'description' => $description,
            'properties'  => ['changes' => $changes],
        ]);
    }

    public function logRefunded(Order $order, float $amount): OrderLog
    {
        return $this->write([
            'order_id'    => $order->id,
            'event_type'  => 'refunded',
            'description' => sprintf('Rimborso di %s € emesso via Stripe', number_format($amount, 2, ',', '.')),
            'properties'  => ['amount' => $amount],
        ]);
    }

    // ─── Helpers ────────────────────────────────────────────────────────

    protected function resolveCauser(): ?object
    {
        if ($this->forcedCauser) {
            return $this->forcedCauser;
        }

        return Auth::user();
    }

    protected function causerName(?object $causer): ?string
    {
        if (! $causer) {
            return null;
        }

        if ($causer instanceof User) {
            $full = trim(($causer->name ?? '') . ' ' . ($causer->surname ?? ''));

            return $full !== '' ? $full : ($causer->email ?? null);
        }

        if ($causer instanceof Customer) {
            $full = trim(($causer->name ?? '') . ' ' . ($causer->surname ?? ''));

            return $full !== '' ? $full : ($causer->email ?? null);
        }

        return method_exists($causer, '__toString') ? (string) $causer : null;
    }

    protected function buildContext($request): ?array
    {
        if (! $request) {
            return null;
        }

        return [
            'url'        => $request->fullUrl(),
            'method'     => $request->method(),
            'ip'         => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 250),
        ];
    }
}
