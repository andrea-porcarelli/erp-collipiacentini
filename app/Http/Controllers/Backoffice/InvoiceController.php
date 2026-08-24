<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\OrderStatus;
use App\Facades\Utils;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Partner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public string $path = 'invoices';

    private function ensureAdmin(): void
    {
        abort_unless(in_array(Auth::user()?->role, ['god', 'admin'], true), 403);
    }

    public function index(): View
    {
        $this->ensureAdmin();
        $partners = Partner::active()->orderBy('partner_name')->pluck('partner_name', 'id')->all();
        $statuses = [
            Invoice::STATUS_DRAFT => 'Bozza',
            Invoice::STATUS_QUEUED => 'In coda',
            Invoice::STATUS_SENT => 'Inviata',
            Invoice::STATUS_ACCEPTED => 'Accettata',
            Invoice::STATUS_REJECTED => 'Rifiutata',
            Invoice::STATUS_ERROR => 'Errore',
        ];

        return view('backoffice.'.$this->path.'.index', compact('partners', 'statuses'))
            ->with('path', $this->path);
    }

    public function pending(): View
    {
        $this->ensureAdmin();
        $partners = Partner::active()->orderBy('partner_name')->pluck('partner_name', 'id')->all();

        return view('backoffice.'.$this->path.'.pending', compact('partners'))
            ->with('path', $this->path);
    }

    public function data(Request $request): JsonResponse
    {
        try {
            $this->ensureAdmin();
            $filters = $request->get('filters') ?? [];

            $query = Invoice::query()->with(['order', 'partner'])->orderBy('emitted_at', 'desc');

            if (! empty($filters['dates'])) {
                [$from, $to] = $this->parseDateRange($filters['dates']);
                if ($from) {
                    $query->where('emitted_at', '>=', $from.' 00:00:00');
                }
                if ($to) {
                    $query->where('emitted_at', '<=', $to.' 23:59:59');
                }
            }

            $partnersFilter = $this->collectSelected($filters['partners'] ?? null);
            if (! empty($partnersFilter)) {
                $query->whereIn('partner_id', $partnersFilter);
            }

            $recipientFilter = $this->collectSelected($filters['recipient_type'] ?? null);
            if (! empty($recipientFilter)) {
                $query->whereIn('recipient_type', $recipientFilter);
            }

            $statusFilter = $this->collectSelected($filters['status'] ?? null);
            if (! empty($statusFilter)) {
                $query->whereIn('status', $statusFilter);
            }

            return datatables()->of($query)
                ->addColumn('number', fn (Invoice $inv) => $inv->number ?: '—')
                ->addColumn('emitted_at', fn (Invoice $inv) => $inv->emitted_at?->format('d/m/Y H:i') ?? '—')
                ->addColumn('order', function (Invoice $inv) {
                    if (! $inv->order) {
                        return '—';
                    }
                    $url = route('orders.show', $inv->order->id);

                    return '<a href="'.$url.'">#'.e($inv->order->order_number).'</a>';
                })
                ->addColumn('recipient_type_label', function (Invoice $inv) {
                    return $inv->recipient_type === Invoice::RECIPIENT_PARTNER ? 'Partner' : 'Cliente';
                })
                ->addColumn('recipient_name', fn (Invoice $inv) => $inv->recipient_name ?: '—')
                ->addColumn('total_formatted', fn (Invoice $inv) => Utils::price($inv->total))
                ->addColumn('status_label', function (Invoice $inv) {
                    return $this->statusBadge($inv->status);
                })
                ->addColumn('action', function (Invoice $inv) {
                    $url = $inv->order ? route('orders.show', $inv->order->id) : '#';

                    return '<a href="'.$url.'" class="bt-miticko outlined Primary small" data-mode="buttonSize-Small buttonEmphasis-Medium buttonAppearance-Primary"><i class="fa-regular fa-eye icon"></i></a>';
                })
                ->rawColumns(['order', 'status_label', 'action'])
                ->toJson();
        } catch (\Exception $e) {
            return $this->exception($e, $request);
        }
    }

    public function pendingData(Request $request): JsonResponse
    {
        try {
            $this->ensureAdmin();
            $filters = $request->get('filters') ?? [];

            // Ordini pagati senza alcuna fattura collegata.
            $query = Order::query()
                ->with(['partner', 'orderProducts.product'])
                ->where('order_status', OrderStatus::PAID->value)
                ->whereDoesntHave('invoices')
                ->orderBy('paid_at', 'desc');

            if (! empty($filters['dates'])) {
                [$from, $to] = $this->parseDateRange($filters['dates']);
                if ($from) {
                    $query->where('paid_at', '>=', $from.' 00:00:00');
                }
                if ($to) {
                    $query->where('paid_at', '<=', $to.' 23:59:59');
                }
            }

            $partnersFilter = $this->collectSelected($filters['partners'] ?? null);
            if (! empty($partnersFilter)) {
                $query->whereIn('partner_id', $partnersFilter);
            }

            if (! empty($filters['customer'])) {
                $like = '%'.$filters['customer'].'%';
                $query->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('surname', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            }

            return datatables()->of($query)
                ->addColumn('order_number', function (Order $order) {
                    $url = route('orders.show', $order->id);

                    return '<a href="'.$url.'">#'.e($order->order_number).'</a>';
                })
                ->addColumn('paid_at', fn (Order $order) => $order->paid_at?->format('d/m/Y H:i') ?? '—')
                ->addColumn('partner', fn (Order $order) => $order->partner?->partner_name ?? '—')
                ->addColumn('customer', fn (Order $order) => trim($order->full_name) ?: ($order->email ?: '—'))
                ->addColumn('product', function (Order $order) {
                    $labels = $order->orderProducts
                        ->map(fn ($op) => $op->product?->label)
                        ->filter()
                        ->unique()
                        ->values();

                    return $labels->isEmpty() ? '—' : e($labels->implode(', '));
                })
                ->addColumn('total_formatted', fn (Order $order) => Utils::price($order->amount))
                ->addColumn('action', function (Order $order) {
                    $url = route('orders.show', $order->id);

                    return '<a href="'.$url.'" class="bt-miticko outlined Primary small" data-mode="buttonSize-Small buttonEmphasis-Medium buttonAppearance-Primary"><i class="fa-regular fa-file-invoice icon"></i></a>';
                })
                ->rawColumns(['order_number', 'action'])
                ->toJson();
        } catch (\Exception $e) {
            return $this->exception($e, $request);
        }
    }

    private function parseDateRange(?string $range): array
    {
        if (! $range || ! str_contains($range, '|')) {
            return [null, null];
        }
        [$from, $to] = explode('|', $range, 2);

        return [
            $from ? Utils::data_from_ita($from) : null,
            $to ? Utils::data_from_ita($to) : null,
        ];
    }

    /**
     * I filtri multi-select del backoffice arrivano come JSON stringificato del formato
     * `[{name, value}]` (vedi app.js:711) — pluck della chiave `name`.
     *
     * @return array<int,string>
     */
    private function collectSelected($raw): array
    {
        if (is_null($raw) || $raw === '') {
            return [];
        }
        if (is_array($raw)) {
            return array_values(array_filter($raw, fn ($v) => $v !== '' && $v !== null));
        }
        $decoded = json_decode((string) $raw, true);
        if (! is_array($decoded)) {
            return [];
        }

        return collect($decoded)->pluck('name')->filter()->values()->all();
    }

    private function statusBadge(string $status): string
    {
        $map = [
            Invoice::STATUS_DRAFT => ['label' => 'Bozza', 'class' => 'label-Neutral'],
            Invoice::STATUS_QUEUED => ['label' => 'In coda', 'class' => 'label-Warning'],
            Invoice::STATUS_SENT => ['label' => 'Inviata', 'class' => 'label-Primary'],
            Invoice::STATUS_ACCEPTED => ['label' => 'Accettata', 'class' => 'label-Success'],
            Invoice::STATUS_REJECTED => ['label' => 'Rifiutata', 'class' => 'label-Error'],
            Invoice::STATUS_ERROR => ['label' => 'Errore', 'class' => 'label-Error'],
        ];
        $meta = $map[$status] ?? ['label' => $status, 'class' => 'label-Neutral'];

        return sprintf('<span class="label-miticko %s">%s</span>', $meta['class'], e($meta['label']));
    }
}
