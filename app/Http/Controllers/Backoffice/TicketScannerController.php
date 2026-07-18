<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderParticipant;
use App\Services\OrderLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TicketScannerController extends Controller
{
    private const STATUSES = ['booked', 'checked_in', 'no_show', 'cancelled'];

    public function scan(string $code): JsonResponse
    {
        try {
            $participant = OrderParticipant::where('code', $code)->first();

            // Fallback per QR legacy generati col bug di concatenazione dei segmenti
            // (chillerlan/QRCode senza clearSegments): il payload conteneva tutti i
            // codici dei biglietti dell'ordine, ma l'ultimo è sempre quello effettivo.
            if (! $participant && strlen($code) > 9 && strlen($code) % 9 === 0) {
                $participant = OrderParticipant::where('code', substr($code, -9))->first();
            }

            if (! $participant) {
                return $this->error(['response' => 'Biglietto non trovato']);
            }

            $order = $participant->order;

            if (! $order) {
                return $this->error(['response' => 'Ordine associato non trovato']);
            }

            $this->authorizeOrderAccess($order);

            $this->loadOrder($order);

            return $this->success([
                'response'   => view('backoffice.orders._ticket_scanner_preview', [
                    'order'              => $order,
                    'scannedParticipant' => $participant->id,
                ])->render(),
                'scanned_id' => $participant->id,
                'order_id'   => $order->id,
            ]);
        } catch (\Throwable $e) {
            return $this->exception($e);
        }
    }

    public function updateStatus(Request $request, OrderParticipant $participant): JsonResponse
    {
        try {
            $this->authorizeOrderAccess($participant->order);

            $data = $request->validate([
                'status' => ['required', Rule::in(self::STATUSES)],
            ]);

            $oldStatus = $participant->status;
            $participant->update($data);

            if ($oldStatus !== $data['status']) {
                app(OrderLogger::class)->logCheckinChanged($participant->order, [[
                    'participant_id' => $participant->id,
                    'code'           => $participant->code,
                    'from'           => $oldStatus,
                    'to'             => $data['status'],
                    'source'         => 'manual',
                ]]);
            }

            return $this->success([
                'status' => $participant->status,
                'label'  => $participant->status_label,
            ]);
        } catch (\Throwable $e) {
            return $this->exception($e);
        }
    }

    public function batchStatus(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'participants'          => 'required|array|min:1',
                'participants.*.id'     => 'required|integer',
                'participants.*.status' => ['required', Rule::in(self::STATUSES)],
            ]);

            $ids = collect($data['participants'])->pluck('id');
            $participants = OrderParticipant::with('order.partner')->whereIn('id', $ids)->get();

            foreach ($participants->groupBy('order_id') as $group) {
                $this->authorizeOrderAccess($group->first()->order);
            }

            $changesByOrder = [];
            foreach ($data['participants'] as $item) {
                $p = $participants->firstWhere('id', $item['id']);
                if (! $p) {
                    continue;
                }
                $oldStatus = $p->status;
                if ($oldStatus === $item['status']) {
                    continue;
                }
                $p->update(['status' => $item['status']]);
                $changesByOrder[$p->order_id][] = [
                    'participant_id' => $p->id,
                    'code'           => $p->code,
                    'from'           => $oldStatus,
                    'to'             => $item['status'],
                    'source'         => 'batch',
                ];
            }

            $logger = app(OrderLogger::class);
            foreach ($changesByOrder as $orderId => $changes) {
                $order = $participants->firstWhere('order_id', $orderId)?->order;
                if ($order) {
                    $logger->logCheckinChanged($order, $changes);
                }
            }

            return $this->success([
                'response' => 'Biglietti aggiornati',
                'count'    => count($data['participants']),
            ]);
        } catch (\Throwable $e) {
            return $this->exception($e);
        }
    }

    private function loadOrder(Order $order): void
    {
        $order->load([
            'customer.country',
            'partner',
            'orderProducts.product.category',
            'orderProducts.items.variant',
            'participants.orderProductItem.variant',
            'participants.orderProductItem.orderProduct.product',
        ]);
    }

    private function authorizeOrderAccess(Order $order): void
    {
        $user = Auth::user();

        if ($user->role === 'company') {
            abort(403);
        }
        if ($user->role === 'partner' && $order->partner_id !== $user->partner_id) {
            abort(403);
        }
    }
}
