<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderParticipant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TicketScannerController extends Controller
{
    private const STATUSES = ['booked', 'checked_in', 'no_show', 'refunded', 'cancelled'];

    public function scan(string $code): JsonResponse
    {
        try {
            $participant = OrderParticipant::where('code', $code)->first();

            if (! $participant) {
                return $this->error(['response' => 'Biglietto non trovato']);
            }

            $order = $participant->order;

            if (! $order) {
                return $this->error(['response' => 'Ordine associato non trovato']);
            }

            $this->authorizeOrderAccess($order);

            $wasCheckedIn = false;
            $alreadyCheckedIn = false;
            if ($participant->status === 'booked') {
                $participant->update(['status' => 'checked_in']);
                $wasCheckedIn = true;
            } elseif ($participant->status === 'checked_in') {
                $alreadyCheckedIn = true;
            }

            $this->loadOrder($order);

            return $this->success([
                'response'           => view('backoffice.orders._ticket_scanner_preview', [
                    'order'              => $order,
                    'scannedParticipant' => $participant->id,
                ])->render(),
                'scanned_id'         => $participant->id,
                'was_checked_in'     => $wasCheckedIn,
                'already_checked_in' => $alreadyCheckedIn,
                'order_id'           => $order->id,
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

            $participant->update($data);

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

            foreach ($data['participants'] as $item) {
                $p = $participants->firstWhere('id', $item['id']);
                if ($p) {
                    $p->update(['status' => $item['status']]);
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
