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

    private const CHIP_STATUSES = [
        'all' => 'Tutti gli ordini',
        Order::INVOICE_STATUS_TO_EMIT => 'Da emettere',
        Order::INVOICE_STATUS_NOT_REQUIRED => 'Fattura non necessaria',
        Order::INVOICE_STATUS_SENT => 'Inviate',
        Order::INVOICE_STATUS_REFUNDED => 'Rimborsato',
    ];

    private function ensureAdmin(): void
    {
        abort_unless(in_array(Auth::user()?->role, ['god', 'admin'], true), 403);
    }

    public function index(): View
    {
        $this->ensureAdmin();
        $partners = Partner::active()->orderBy('partner_name')->pluck('partner_name', 'id')->all();

        return view('backoffice.'.$this->path.'.index', [
            'partners' => $partners,
            'chipStatuses' => self::CHIP_STATUSES,
            'path' => $this->path,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        try {
            $this->ensureAdmin();
            $filters = $request->get('filters') ?? [];

            $query = Order::query()
                ->with(['partner', 'orderProducts.product', 'invoices'])
                ->orderByDesc('paid_at');

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

            if (! empty($filters['products'])) {
                $like = '%'.$filters['products'].'%';
                $query->whereHas('orderProducts.product', fn ($q) => $q->where('label', 'like', $like));
            }

            if (! empty($filters['customer'])) {
                $like = '%'.$filters['customer'].'%';
                $query->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('surname', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            }

            $status = (string) ($filters['status'] ?? 'all');
            $this->applyStatusFilter($query, $status);

            return datatables()->of($query)
                ->addColumn('order_number', function (Order $order) {
                    $url = route('orders.show', $order->id);

                    return '<a href="'.$url.'">#'.e($order->order_number).'</a>';
                })
                ->addColumn('paid_at', fn (Order $order) => $order->paid_at?->format('d/m/Y') ?? '—')
                ->addColumn('payment_status', function (Order $order) {
                    $status = $order->order_status;
                    if (! $status) {
                        return '—';
                    }
                    $cls = match ($status) {
                        OrderStatus::PAID, OrderStatus::COMPLETED => 'label-Success',
                        OrderStatus::PENDING => 'label-Warning',
                        OrderStatus::REFUNDED => 'label-Neutral',
                        default => 'label-Error',
                    };

                    return sprintf('<span class="label-miticko %s">%s</span>', $cls, e(strtoupper($status->label())));
                })
                ->addColumn('customer', function (Order $order) {
                    $full = trim((string) $order->full_name);

                    return $full !== '' ? e($full) : e($order->email ?: '—');
                })
                ->addColumn('partner', fn (Order $order) => e($order->partner?->partner_name ?? '—'))
                ->addColumn('product', function (Order $order) {
                    $labels = $order->orderProducts
                        ->map(fn ($op) => $op->product?->label)
                        ->filter()
                        ->unique()
                        ->values();

                    return $labels->isEmpty() ? '—' : e($labels->implode(', '));
                })
                ->addColumn('total_formatted', fn (Order $order) => Utils::price($order->amount))
                ->addColumn('invoice_status', fn (Order $order) => $this->renderInvoiceStatus($order))
                ->rawColumns(['order_number', 'payment_status', 'invoice_status'])
                ->toJson();
        } catch (\Exception $e) {
            return $this->exception($e, $request);
        }
    }

    private function applyStatusFilter($query, string $status): void
    {
        // Coerente con Order::invoiceStatus(): "emessa" = draft/queued/sent/accepted
        // (error/rejected restano riproducibili → stato "da emettere").
        $sent = fn ($q) => $q->whereIn('status', [
            Invoice::STATUS_DRAFT,
            Invoice::STATUS_QUEUED,
            Invoice::STATUS_SENT,
            Invoice::STATUS_ACCEPTED,
        ]);

        match ($status) {
            Order::INVOICE_STATUS_REFUNDED => $query->where('order_status', OrderStatus::REFUNDED->value),
            Order::INVOICE_STATUS_NOT_REQUIRED => $query
                ->where('order_status', '!=', OrderStatus::REFUNDED->value)
                ->whereDoesntHave('orderProducts.product', fn ($q) => $q->where('invoice_required', true)),
            Order::INVOICE_STATUS_SENT => $query
                ->where('order_status', '!=', OrderStatus::REFUNDED->value)
                ->whereHas('invoices', $sent),
            Order::INVOICE_STATUS_TO_EMIT => $query
                ->where('order_status', '!=', OrderStatus::REFUNDED->value)
                ->whereHas('orderProducts.product', fn ($q) => $q->where('invoice_required', true))
                ->whereDoesntHave('invoices', $sent),
            default => null,
        };
    }

    private function renderInvoiceStatus(Order $order): string
    {
        $status = $order->invoiceStatus();

        return match ($status) {
            Order::INVOICE_STATUS_TO_EMIT => '<button type="button" class="bt-miticko btn-set-invoice label-miticko label-InvoiceAction" data-order-id="'.$order->id.'" data-mode="small primary">'
                .'<i class="fa-regular fa-file-invoice"></i> imposta fattura</button>',
            Order::INVOICE_STATUS_SENT => '<span class="label-miticko label-Success">INVIATA</span>',
            Order::INVOICE_STATUS_REFUNDED => '<span class="label-miticko label-Error">RIMBORSATO</span>',
            Order::INVOICE_STATUS_NOT_REQUIRED => '<span class="label-miticko label-Neutral">NON NECESSARIA</span>',
            default => '—',
        };
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
}
