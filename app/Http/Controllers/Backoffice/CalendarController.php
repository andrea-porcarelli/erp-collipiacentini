<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Interfaces\CalendarInterface;
use App\Models\Order;
use App\Models\OrderParticipant;
use App\Models\Partner;
use App\Services\OrderLogger;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CalendarController extends Controller
{
    private const PARTICIPANT_STATUSES = ['booked', 'checked_in', 'no_show', 'cancelled'];

    public function __construct(private CalendarInterface $interface)
    {
    }

    public function index(): View
    {
        $partners = $this->accessiblePartners();
        $selectedPartner = $this->resolveSelectedPartner(request()->query('partner_id'), $partners);

        return view('backoffice.calendar.index', [
            'partners'         => $partners,
            'selectedPartner'  => $selectedPartner,
            'canPickPartner'   => in_array(Auth::user()->role, ['god', 'operator', 'company'], true),
            'orderStatuses'    => OrderStatus::statuses(),
            'weekStart'        => Carbon::today()->startOfWeek(Carbon::MONDAY)->toDateString(),
            'today'            => Carbon::today()->toDateString(),
        ])->with('path', 'calendar')->with('active', 'calendar');
    }

    public function week(Request $request): JsonResponse
    {
        try {
            $partner = $this->requirePartner($request);
            $weekStart = $this->parseWeekStart($request->query('week_start'));

            $days = $this->interface->weekOverview($partner->id, $weekStart);

            return $this->success([
                'week_start' => $weekStart->toDateString(),
                'week_end'   => $weekStart->copy()->addDays(6)->toDateString(),
                'label'      => $this->weekLabel($weekStart),
                'days'       => $days,
            ]);
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }

    public function day(Request $request): JsonResponse
    {
        try {
            $partner = $this->requirePartner($request);
            $date = $this->parseDate($request->query('date'));
            $groupBy = $request->query('group_by') === 'slot' ? 'slot' : 'product';

            $data = $this->interface->daySlots($partner->id, $date, $groupBy);

            $html = view('backoffice.calendar._day', [
                'date'    => $date,
                'groupBy' => $groupBy,
                'data'    => $data,
            ])->render();

            return $this->success([
                'group_by' => $groupBy,
                'date'     => $date,
                'html'     => $html,
            ]);
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }

    public function slotOrders(Request $request): JsonResponse
    {
        try {
            $partner = $this->requirePartner($request);
            $productId = (int) $request->query('product_id');
            $date = $this->parseDate($request->query('date'));
            $time = $this->parseTime($request->query('time'));

            if (! $productId) {
                return $this->error(['response' => 'Prodotto non specificato']);
            }

            $filters = [
                'order_status' => $request->query('order_status'),
                'check_in'     => $request->query('check_in'),
                'search'       => $request->query('search'),
            ];

            $orders = $this->interface->slotOrders($partner->id, $productId, $date, $time, $filters);

            $html = view('backoffice.calendar._arrivals_list', [
                'orders' => $orders,
            ])->render();

            return $this->success([
                'count' => $orders->count(),
                'html'  => $html,
            ]);
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }

    public function orderDetail(Order $order): JsonResponse
    {
        try {
            $this->authorizeOrderAccess($order);
            $order->load([
                'customer',
                'partner',
                'orderProducts.product',
                'orderProducts.items.variant',
                'participants.orderProductItem.variant',
                'participants.orderProductItem.orderProduct.product',
            ]);

            $html = view('backoffice.calendar._order_modal', [
                'order' => $order,
            ])->render();

            return $this->success(['html' => $html]);
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }

    public function batchStatus(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'participants'          => 'required|array|min:1',
                'participants.*.id'     => 'required|integer',
                'participants.*.status' => ['required', Rule::in(self::PARTICIPANT_STATUSES)],
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
                    'source'         => 'calendar',
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
                'response' => 'Check-in aggiornati',
                'updated'  => array_sum(array_map('count', $changesByOrder)),
            ]);
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }

    /**
     * Elenca i partner accessibili all'utente corrente. Ritorna sempre una
     * Collection di ['id','partner_name']. Per partner/admin è forzato al proprio.
     */
    private function accessiblePartners()
    {
        $user = Auth::user();
        $query = Partner::query()->orderBy('partner_name');

        if (in_array($user->role, ['partner', 'admin'], true)) {
            $query->where('id', (int) $user->partner_id);
        } elseif ($user->role === 'company') {
            $query->whereHas('products.companies', function ($q) use ($user) {
                $q->where('companies.id', $user->company_id);
            });
        }

        return $query->get(['id', 'partner_name']);
    }

    private function resolveSelectedPartner($requestedId, $partners): ?Partner
    {
        $user = Auth::user();

        if (in_array($user->role, ['partner', 'admin'], true)) {
            return Partner::find((int) $user->partner_id);
        }

        if ($requestedId) {
            $id = (int) $requestedId;
            if ($partners->contains(fn ($p) => (int) $p->id === $id)) {
                return Partner::find($id);
            }
        }

        return null;
    }

    private function requirePartner(Request $request): Partner
    {
        $user = Auth::user();

        if (in_array($user->role, ['partner', 'admin'], true)) {
            $partner = Partner::find((int) $user->partner_id);
            if (! $partner) {
                abort(403);
            }

            return $partner;
        }

        $partnerId = (int) $request->query('partner_id');
        if (! $partnerId) {
            abort(422, 'Partner non specificato');
        }
        $partner = Partner::find($partnerId);
        if (! $partner) {
            abort(404);
        }
        $this->authorizePartnerAccess($partner);

        return $partner;
    }

    private function parseWeekStart(?string $raw): Carbon
    {
        if ($raw) {
            try {
                return Carbon::parse($raw)->startOfWeek(Carbon::MONDAY);
            } catch (\Throwable $e) {
                // fallback su settimana corrente
            }
        }

        return Carbon::today()->startOfWeek(Carbon::MONDAY);
    }

    private function parseDate(?string $raw): string
    {
        if ($raw && preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return $raw;
        }
        abort(422, 'Data non valida');
    }

    private function parseTime(?string $raw): string
    {
        if ($raw && preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $raw)) {
            return substr($raw, 0, 5);
        }
        abort(422, 'Orario non valido');
    }

    private function weekLabel(Carbon $weekStart): string
    {
        $end = $weekStart->copy()->addDays(6);
        $months = ['GEN', 'FEB', 'MAR', 'APR', 'MAG', 'GIU', 'LUG', 'AGO', 'SET', 'OTT', 'NOV', 'DIC'];

        $startLabel = $weekStart->day.' '.$months[$weekStart->month - 1];
        $endLabel = $end->day.' '.$months[$end->month - 1];

        return $startLabel.' - '.$endLabel;
    }

    private function authorizePartnerAccess(?Partner $partner): void
    {
        if (! $partner) {
            abort(403);
        }
        $user = Auth::user();

        if (in_array($user->role, ['god', 'operator'], true)) {
            return;
        }

        if ($user->role === 'company') {
            $allowed = $partner->products()
                ->whereHas('companies', fn ($q) => $q->where('companies.id', $user->company_id))
                ->exists();
            if (! $allowed) {
                abort(403);
            }

            return;
        }

        if (in_array($user->role, ['partner', 'admin'], true)) {
            if ((int) $partner->id !== (int) $user->partner_id) {
                abort(403);
            }

            return;
        }

        abort(403);
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
        if ($user->role === 'admin' && $order->partner_id !== $user->partner_id) {
            abort(403);
        }
    }
}
