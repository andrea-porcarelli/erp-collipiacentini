<?php

namespace App\Services\Statistics;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class RevenueFilters
{
    public const SOURCE_ORDER_DATE = 'order_date';
    public const SOURCE_VISIT_DATE = 'visit_date';

    public const COMPARE_OFF = 'off';
    public const COMPARE_PREVIOUS_PERIOD = 'previous_period';
    public const COMPARE_PREVIOUS_YEAR = 'previous_year';

    public const PRESETS = [
        'today', 'yesterday', 'last_7_days', 'last_30_days',
        'last_90_days', 'this_month', 'last_month', 'this_year', 'custom',
    ];

    public Carbon $from;

    public Carbon $to;

    public string $preset = 'last_30_days';

    public string $source = self::SOURCE_ORDER_DATE;

    public string $compare = self::COMPARE_OFF;

    /** @var array<int, string> */
    public array $orderStatus = [];

    /** @var array<int, int> */
    public array $categoryIds = [];

    /** @var array<int, int> */
    public array $productIds = [];

    /** @var array<int, int> */
    public array $partnerIds = [];

    public static function fromRequest(Request $request): self
    {
        $f = new self;

        $preset = (string) $request->input('preset', 'last_30_days');
        $f->preset = in_array($preset, self::PRESETS, true) ? $preset : 'last_30_days';

        [$f->from, $f->to] = self::rangeForPreset($f->preset, $request);

        $source = (string) $request->input('source', self::SOURCE_ORDER_DATE);
        $f->source = in_array($source, [self::SOURCE_ORDER_DATE, self::SOURCE_VISIT_DATE], true)
            ? $source
            : self::SOURCE_ORDER_DATE;

        $compare = (string) $request->input('compare', self::COMPARE_OFF);
        $f->compare = in_array($compare, [self::COMPARE_OFF, self::COMPARE_PREVIOUS_PERIOD, self::COMPARE_PREVIOUS_YEAR], true)
            ? $compare
            : self::COMPARE_OFF;

        $validStatuses = array_map(fn (OrderStatus $s) => $s->value, OrderStatus::cases());
        $f->orderStatus = array_values(array_intersect(
            (array) $request->input('order_status', []),
            $validStatuses
        ));

        $f->categoryIds = array_values(array_filter(array_map('intval', (array) $request->input('category_id', []))));
        $f->productIds = array_values(array_filter(array_map('intval', (array) $request->input('product_id', []))));
        $f->partnerIds = array_values(array_filter(array_map('intval', (array) $request->input('partner_id_filter', []))));

        return $f;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private static function rangeForPreset(string $preset, Request $request): array
    {
        $now = Carbon::now();

        return match ($preset) {
            'today'         => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday'     => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'last_7_days'   => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            'last_90_days'  => [$now->copy()->subDays(89)->startOfDay(), $now->copy()->endOfDay()],
            'this_month'    => [$now->copy()->startOfMonth(), $now->copy()->endOfDay()],
            'last_month'    => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'this_year'     => [$now->copy()->startOfYear(), $now->copy()->endOfDay()],
            'custom'        => self::parseCustomRange($request, $now),
            default         => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
        };
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private static function parseCustomRange(Request $request, Carbon $now): array
    {
        try {
            $from = $request->filled('from') ? Carbon::parse((string) $request->input('from'))->startOfDay() : null;
            $to = $request->filled('to') ? Carbon::parse((string) $request->input('to'))->endOfDay() : null;
        } catch (\Throwable) {
            $from = $to = null;
        }

        if (! $from || ! $to || $from->greaterThan($to)) {
            return [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}|null
     */
    public function comparisonRange(): ?array
    {
        if ($this->compare === self::COMPARE_OFF) {
            return null;
        }

        if ($this->compare === self::COMPARE_PREVIOUS_YEAR) {
            return [$this->from->copy()->subYear(), $this->to->copy()->subYear()];
        }

        $durationDays = $this->from->diffInDays($this->to) + 1;

        return [
            $this->from->copy()->subDays($durationDays)->startOfDay(),
            $this->from->copy()->subDay()->endOfDay(),
        ];
    }

    public function apply(Builder $query): Builder
    {
        $this->applyDateRange($query, $this->from, $this->to);

        if ($this->orderStatus !== []) {
            $query->whereIn('order_status', $this->orderStatus);
        }

        if ($this->partnerIds !== []) {
            $query->whereIn('partner_id', $this->partnerIds);
        }

        if ($this->categoryIds !== [] || $this->productIds !== []) {
            $query->whereHas('orderProducts', function (Builder $q) {
                if ($this->productIds !== []) {
                    $q->whereIn('order_products.product_id', $this->productIds);
                }
                if ($this->categoryIds !== []) {
                    $q->whereHas('product', fn (Builder $p) => $p->whereIn('products.category_id', $this->categoryIds));
                }
            });
        }

        return $query;
    }

    public function applyWithRange(Builder $query, Carbon $from, Carbon $to): Builder
    {
        $this->applyDateRange($query, $from, $to);

        if ($this->orderStatus !== []) {
            $query->whereIn('order_status', $this->orderStatus);
        }

        if ($this->partnerIds !== []) {
            $query->whereIn('partner_id', $this->partnerIds);
        }

        if ($this->categoryIds !== [] || $this->productIds !== []) {
            $query->whereHas('orderProducts', function (Builder $q) {
                if ($this->productIds !== []) {
                    $q->whereIn('order_products.product_id', $this->productIds);
                }
                if ($this->categoryIds !== []) {
                    $q->whereHas('product', fn (Builder $p) => $p->whereIn('products.category_id', $this->categoryIds));
                }
            });
        }

        return $query;
    }

    private function applyDateRange(Builder $query, Carbon $from, Carbon $to): void
    {
        if ($this->source === self::SOURCE_VISIT_DATE) {
            $query->whereHas('orderProducts', fn (Builder $q) => $q->whereBetween('booking_date', [$from->toDateString(), $to->toDateString()]));
        } else {
            $query->whereBetween('orders.created_at', [$from, $to]);
        }
    }

    /**
     * Serializza i filtri attivi per preservarli nei link / download URLs.
     *
     * @return array<string, mixed>
     */
    public function toQuery(): array
    {
        $q = [
            'preset'  => $this->preset,
            'source'  => $this->source,
            'compare' => $this->compare,
        ];

        if ($this->preset === 'custom') {
            $q['from'] = $this->from->toDateString();
            $q['to']   = $this->to->toDateString();
        }
        if ($this->orderStatus !== []) {
            $q['order_status'] = $this->orderStatus;
        }
        if ($this->categoryIds !== []) {
            $q['category_id'] = $this->categoryIds;
        }
        if ($this->productIds !== []) {
            $q['product_id'] = $this->productIds;
        }
        if ($this->partnerIds !== []) {
            $q['partner_id_filter'] = $this->partnerIds;
        }

        return $q;
    }

    public function dateColumnForGroup(): string
    {
        return $this->source === self::SOURCE_VISIT_DATE
            ? 'order_products.booking_date'
            : 'orders.created_at';
    }

    public function presetLabel(): string
    {
        return match ($this->preset) {
            'today'         => 'Oggi',
            'yesterday'     => 'Ieri',
            'last_7_days'   => 'Ultimi 7 giorni',
            'last_90_days'  => 'Ultimi 90 giorni',
            'this_month'    => 'Mese corrente',
            'last_month'    => 'Mese scorso',
            'this_year'     => 'Anno in corso',
            'custom'        => $this->from->format('d/m/Y').' – '.$this->to->format('d/m/Y'),
            default         => 'Ultimi 30 giorni',
        };
    }
}
