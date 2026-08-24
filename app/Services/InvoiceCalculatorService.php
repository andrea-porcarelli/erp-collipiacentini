<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderProductItem;

class InvoiceCalculatorService
{
    public const LINE_TICKET_BASE = 'ticket_base';

    public const LINE_PRESALE = 'presale';

    public const LINE_MITICKO = 'miticko_commission';

    public const LINE_BANK = 'bank_commission';

    /**
     * Restituisce le due strutture pronte per il dialog: una per ciascun destinatario.
     * Ogni struttura ha: `recipient`, `default_flags`, `lines` (array di righe con importo calcolato).
     *
     * @param  array<string,bool>|null  $overrideFlags  Se passati, sovrascrivono i flag snapshot (per l'anteprima con selezioni admin).
     * @return array{partner: array, customer: array}
     */
    public function computeForOrder(Order $order, ?array $overrideFlags = null): array
    {
        $order->loadMissing(['orderProducts.product', 'orderProducts.items.variant', 'partner.billing']);

        $partnerLines = [];
        $customerLines = [];

        foreach ($order->orderProducts as $orderProduct) {
            foreach ($orderProduct->items as $item) {
                $flags = $this->resolveFlags($item, $overrideFlags);
                $amounts = $this->computeAmountsForItem($item);

                if ($flags[self::LINE_MITICKO] && $amounts[self::LINE_MITICKO] > 0) {
                    $partnerLines[] = [
                        'code' => self::LINE_MITICKO,
                        'label' => 'Commissione Miticko',
                        'quantity' => (int) $item->quantity,
                        'unit_amount' => $amounts[self::LINE_MITICKO.'_unit'],
                        'total' => $amounts[self::LINE_MITICKO],
                    ];
                }

                if ($flags[self::LINE_BANK] && $amounts[self::LINE_BANK] > 0) {
                    $partnerLines[] = [
                        'code' => self::LINE_BANK,
                        'label' => 'Commissioni bancarie',
                        'quantity' => (int) $item->quantity,
                        'unit_amount' => $amounts[self::LINE_BANK.'_unit'],
                        'total' => $amounts[self::LINE_BANK],
                    ];
                }

                if ($flags[self::LINE_PRESALE] && $amounts[self::LINE_PRESALE] > 0) {
                    $customerLines[] = [
                        'code' => self::LINE_PRESALE,
                        'label' => 'Prevendita',
                        'quantity' => (int) $item->quantity,
                        'unit_amount' => $amounts[self::LINE_PRESALE.'_unit'],
                        'total' => $amounts[self::LINE_PRESALE],
                    ];
                }
            }
        }

        return [
            Invoice::RECIPIENT_PARTNER => [
                'recipient' => $this->partnerRecipient($order),
                'default_flags' => $this->aggregateFlags($order, [self::LINE_MITICKO, self::LINE_BANK]),
                'lines' => $partnerLines,
                'total' => round(array_sum(array_column($partnerLines, 'total')), 2),
            ],
            Invoice::RECIPIENT_CUSTOMER => [
                'recipient' => $this->customerRecipient($order),
                'default_flags' => $this->aggregateFlags($order, [self::LINE_PRESALE]),
                'lines' => $customerLines,
                'total' => round(array_sum(array_column($customerLines, 'total')), 2),
            ],
        ];
    }

    /**
     * @param  array<string,bool>|null  $overrideFlags
     * @return array<string,bool>
     */
    private function resolveFlags(OrderProductItem $item, ?array $overrideFlags): array
    {
        // Fallback ai flag correnti del prodotto per ordini pre-snapshot (bill_* NULL su OPI).
        $product = $item->orderProduct?->product;
        $defaults = $product?->defaultBillingFlags() ?? [
            'bill_ticket_base' => true,
            'bill_presale' => false,
            'bill_miticko_commission' => true,
            'bill_bank_commission' => true,
        ];

        $pick = fn (string $rawKey, string $defaultKey) => is_null($item->getRawOriginal($rawKey))
            ? (bool) $defaults[$defaultKey]
            : (bool) $item->{$rawKey};

        $snapshot = [
            self::LINE_TICKET_BASE => $pick('bill_ticket_base', 'bill_ticket_base'),
            self::LINE_PRESALE => $pick('bill_presale', 'bill_presale'),
            self::LINE_MITICKO => $pick('bill_miticko_commission', 'bill_miticko_commission'),
            self::LINE_BANK => $pick('bill_bank_commission', 'bill_bank_commission'),
        ];

        if ($overrideFlags === null) {
            return $snapshot;
        }

        foreach ($snapshot as $key => $value) {
            if (array_key_exists($key, $overrideFlags)) {
                $snapshot[$key] = (bool) $overrideFlags[$key];
            }
        }

        return $snapshot;
    }

    /**
     * @return array<string,float>
     */
    private function computeAmountsForItem(OrderProductItem $item): array
    {
        $unitPrice = (float) $item->unit_price;
        $qty = max((int) $item->quantity, 1);
        $subtotal = round($unitPrice * $qty, 2);

        // Prevendita: importo per partecipante, dipende dalla soglia sul unit_price.
        $presaleThreshold = $item->partner_commission_presale_threshold;
        $presaleUnit = is_null($presaleThreshold)
            ? 0.0
            : ($unitPrice < (float) $presaleThreshold
                ? (float) $item->partner_commission_presale_low
                : (float) $item->partner_commission_presale_high);
        $presaleTotal = round($presaleUnit * $qty, 2);

        // Miticko = fisso per biglietto + variabile % sul subtotal.
        $mitickoFixed = (float) $item->partner_commission_miticko_fixed;
        $mitickoVarPct = (float) $item->partner_commission_miticko_variable;
        $mitickoTotal = round(($mitickoFixed * $qty) + ($subtotal * $mitickoVarPct / 100), 2);
        $mitickoUnit = $qty > 0 ? round($mitickoTotal / $qty, 2) : 0.0;

        // Bancarie: % gateway sul subtotal.
        $bankPct = (float) $item->partner_commission_payment;
        $bankTotal = round($subtotal * $bankPct / 100, 2);
        $bankUnit = $qty > 0 ? round($bankTotal / $qty, 2) : 0.0;

        return [
            self::LINE_TICKET_BASE.'_unit' => $unitPrice,
            self::LINE_TICKET_BASE => $subtotal,
            self::LINE_PRESALE.'_unit' => $presaleUnit,
            self::LINE_PRESALE => $presaleTotal,
            self::LINE_MITICKO.'_unit' => $mitickoUnit,
            self::LINE_MITICKO => $mitickoTotal,
            self::LINE_BANK.'_unit' => $bankUnit,
            self::LINE_BANK => $bankTotal,
        ];
    }

    /**
     * @param  array<int,string>  $keys
     * @return array<string,bool>
     */
    private function aggregateFlags(Order $order, array $keys): array
    {
        $result = array_fill_keys($keys, false);

        foreach ($order->orderProducts as $orderProduct) {
            foreach ($orderProduct->items as $item) {
                $itemFlags = $this->resolveFlags($item, null);
                foreach ($keys as $key) {
                    if (! empty($itemFlags[$key])) {
                        $result[$key] = true;
                    }
                }
            }
        }

        return $result;
    }

    private function partnerRecipient(Order $order): array
    {
        $partner = $order->partner;
        $billing = $partner?->billing;

        return [
            'name' => $billing?->legal_name ?? $partner?->partner_name,
            'vat_number' => $billing?->vat_number,
            'tax_code' => $billing?->tax_code,
            'address' => $billing?->street_address,
            'postal_code' => $billing?->postal_code,
            'city' => $billing?->city,
            'province' => $billing?->province,
            'country' => $billing?->country ?? 'IT',
            'email' => $partner?->email_notify,
            'pec' => $billing?->pec_email,
            'sdi_code' => $billing?->sdi_code,
        ];
    }

    private function customerRecipient(Order $order): array
    {
        $addressLine = trim((string) ($order->address ?? ''));

        return [
            'name' => trim((string) $order->full_name) ?: null,
            'vat_number' => null,
            'tax_code' => $order->fiscal_code,
            'address' => $addressLine ?: null,
            'postal_code' => $order->zip_code,
            'city' => $order->city,
            'province' => null,
            'country' => 'IT',
            'email' => $order->email,
            'pec' => null,
            'sdi_code' => null,
        ];
    }
}
