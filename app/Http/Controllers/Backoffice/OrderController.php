<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\OrderStatus;
use App\Facades\Utils;
use App\Http\Controllers\Backoffice\Requests\Orders\UpdateBookingRequest;
use App\Http\Controllers\Backoffice\Requests\Orders\UpdateCustomerRequest;
use App\Http\Controllers\Backoffice\Requests\Orders\UpdateCustomerStatusRequest;
use App\Http\Controllers\Backoffice\Requests\Orders\UpdateNotesRequest;
use App\Http\Controllers\Controller;
use App\Interfaces\OrderInterface;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Services\StripePaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class OrderController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    public OrderInterface $interface;
    public string $path;

    public function __construct(OrderInterface $interface)
    {
        $this->interface = $interface;
        $this->path = 'orders';
    }

    public function index(): View
    {
        $statuses = OrderStatus::statuses();
        return view('backoffice.' . $this->path . '.index', compact('statuses'))
            ->with('path', $this->path);
    }

    public function data(Request $request) : JsonResponse {
        try {
            $user = Auth::user();
            $filters = $request->get('filters') ?? [];

            $elements = $this->interface->filters($filters)
                ->with(['orderProducts.items.variant'])
                ->orderBy('created_at', 'desc');

            if ($user->role === 'company') {
                $elements->whereHas('partner', function ($q) use ($user) {
                    $q->where('company_id', $user->company_id);
                });
            } elseif (in_array($user->role, ['partner', 'admin'])) {
                $elements->where('partner_id', $user->partner_id);
            }

            return $this->editColumns(datatables()->of($elements), $this->route_name(__CLASS__), ['preview', 'detail', ])
                ->addColumn('created_at', function ($item) {
                    return Utils::data($item->product_data);
                })
                ->addColumn('order_number', function ($item) {
                    return '#' . $item->order_number;
                })
                ->addColumn('customer', function ($item) {
                    return $item->customer->full_name;
                })
                ->addColumn('timing', function ($item) {
                    return $item->product_time;
                })
                ->addColumn('details', function ($item) {
                    return $item->product_label;
                })
                ->addColumn('type', function ($item) {
                    return $item->orderProducts
                        ->flatMap->items
                        ->groupBy('product_variant_id')
                        ->map(function ($group) {
                            $label = $group->first()->variant?->label ?? 'Variante';
                            $qty   = $group->sum('quantity');
                            return "{$qty} × {$label}";
                        })
                        ->implode(', ');
                })
                ->addColumn('status', function ($item) {
                    $order_status = $item->order_status;
                    return view('backoffice.components.label', ['status' => $order_status->status(), 'label' => $order_status->label()])->render();
                })
                ->rawColumns(['status', 'options'])
                ->toJson();
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }

    public function preview(Order $order): JsonResponse
    {
        try {
            $this->authorizeOrderAccess($order);

            $order->load(['customer', 'orderProducts.product', 'orderProducts.items.variant']);

            return $this->success([
                'response' => view('backoffice.orders._preview', compact('order'))->render(),
            ]);
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }

    public function show(Order $order): View
    {
        $this->authorizeOrderAccess($order);

        $order->load([
            'customer.country',
            'partner',
            'orderProducts.product.category',
            'orderProducts.items.variant',
        ]);

        // Lazy backfill di card_brand/card_last4 per ordini precedenti.
        if (!$order->card_brand && $order->stripe_payment_method) {
            try {
                $pm = app(StripePaymentService::class)->retrievePaymentMethod($order->stripe_payment_method);
                $order->update([
                    'card_brand' => $pm->card->brand ?? null,
                    'card_last4' => $pm->card->last4 ?? null,
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $commissionPaymentRate = (float) ($order->partner?->commission_payment ?? 0);
        $commissionServiceRate = (float) ($order->partner?->commission_miticko_variable ?? 0);
        $amount = (float) $order->amount;
        $commissionPaymentAmount = round($amount * $commissionPaymentRate / 100, 2);
        $commissionServiceAmount = round($amount * $commissionServiceRate / 100, 2);

        return view('backoffice.' . $this->path . '.show', [
            'model' => $order,
            'commissionPaymentRate'   => $commissionPaymentRate,
            'commissionServiceRate'   => $commissionServiceRate,
            'commissionPaymentAmount' => $commissionPaymentAmount,
            'commissionServiceAmount' => $commissionServiceAmount,
        ])->with('path', $this->path);
    }

    public function updateCustomerStatus(UpdateCustomerStatusRequest $request, Order $order): JsonResponse
    {
        try {
            $this->authorizeOrderAccess($order);
            $order->update($request->validated());
            return $this->success();
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }

    public function updateNotes(UpdateNotesRequest $request, Order $order): JsonResponse
    {
        try {
            $this->authorizeOrderAccess($order);
            $order->update($request->validated());
            return $this->success();
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }

    public function updateCustomer(UpdateCustomerRequest $request, Order $order): JsonResponse
    {
        try {
            $this->authorizeOrderAccess($order);
            $order->customer->update($request->validated());
            return $this->success();
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }

    public function updateBooking(UpdateBookingRequest $request, Order $order): JsonResponse
    {
        try {
            $this->authorizeOrderAccess($order);
            $orderProduct = $order->orderProducts()->first();
            if (!$orderProduct) {
                return $this->error(['response' => 'Nessun prodotto associato all\'ordine']);
            }
            $orderProduct->update($request->validated());
            return $this->success();
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }

    public function sendEmail(Order $order): JsonResponse
    {
        try {
            $this->authorizeOrderAccess($order);
            $order->load(['customer', 'partner', 'orderProducts.product.category', 'orderProducts.items.variant']);

            if (!$order->customer?->email) {
                return $this->error(['response' => 'Il cliente non ha un\'email valida']);
            }

            Mail::to($order->customer->email)->send(new OrderConfirmationMail($order));

            return $this->success(['response' => 'Email inviata correttamente']);
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }

    public function downloadReceipt(Order $order): Response
    {
        $this->authorizeOrderAccess($order);
        $order->load(['customer.country', 'partner', 'orderProducts.product.category', 'orderProducts.items.variant']);

        $pdf = Pdf::loadView('backoffice.orders._receipt', ['order' => $order]);

        return $pdf->download("ricevuta-{$order->order_number}.pdf");
    }

    public function refund(Order $order): JsonResponse
    {
        try {
            $this->authorizeOrderAccess($order);

            if (!$order->stripe_payment_intent_id) {
                return $this->error(['response' => 'PaymentIntent Stripe non disponibile per questo ordine']);
            }
            if ($order->order_status === OrderStatus::REFUNDED) {
                return $this->error(['response' => 'Ordine già rimborsato']);
            }

            app(StripePaymentService::class)->refund($order->stripe_payment_intent_id);

            $order->update(['order_status' => OrderStatus::REFUNDED]);

            return $this->success(['response' => 'Rimborso eseguito correttamente']);
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }

    private function authorizeOrderAccess(Order $order): void
    {
        $user = Auth::user();

        if ($user->role === 'company' && $order->partner?->company_id !== $user->company_id) {
            abort(403);
        }
        if ($user->role === 'partner' && $order->partner_id !== $user->partner_id) {
            abort(403);
        }
    }
}
