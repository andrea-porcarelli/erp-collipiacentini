<?php

namespace App\Services;

use App\Models\CustomerConsent;
use App\Models\Order;
use App\Models\PartnerConsent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class OrderExportService
{
    /**
     * Genera il file xlsx per gli ordini forniti e restituisce il path assoluto.
     * $partnerConsents deve contenere i consensi attivi del partner degli ordini,
     * ordinati per position; ogni consenso diventa 2 colonne (accettato + scadenza).
     */
    public function generate(Collection $orders, Collection $partnerConsents): string
    {
        $orders->loadMissing([
            'customer.country',
            'partner',
            'orderProducts.product.category',
            'orderProducts.items.variant',
            'orderProducts.items.participants',
        ]);

        $consentAcceptanceByCustomer = $this->loadCustomerConsents($orders, $partnerConsents);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ordini');

        $groups = $this->buildColumnGroups($partnerConsents);
        $headers = $this->flattenColumns($groups);

        $this->writeHeaders($sheet, $groups, $headers);

        $rowIndex = 3;
        foreach ($orders as $order) {
            $participantCounter = 0;
            $consentsForOrder = $consentAcceptanceByCustomer[$order->customer_id.'|'.$order->partner_id] ?? [];

            foreach ($order->orderProducts as $orderProduct) {
                foreach ($orderProduct->items as $item) {
                    $participants = $item->participants;
                    if ($participants->isEmpty()) {
                        // Se non ci sono partecipanti materializzati, ne emettiamo comunque
                        // uno per quantity per non perdere righe.
                        for ($q = 0; $q < (int) $item->quantity; $q++) {
                            $participantCounter++;
                            $this->writeRow($sheet, $rowIndex++, $order, $orderProduct, $item, null, $participantCounter, $partnerConsents, $consentsForOrder);
                        }
                        continue;
                    }
                    foreach ($participants as $participant) {
                        $participantCounter++;
                        $this->writeRow($sheet, $rowIndex++, $order, $orderProduct, $item, $participant, $participantCounter, $partnerConsents, $consentsForOrder);
                    }
                }
            }
        }

        $this->autosizeColumns($sheet, count($headers));

        $path = storage_path('app/exports/miticko-ordini-'.now()->format('Ymd-His').'-'.Str::random(6).'.xlsx');
        @mkdir(dirname($path), 0775, true);

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return $path;
    }

    private function buildColumnGroups(Collection $partnerConsents): array
    {
        $consentColumns = [];
        foreach ($partnerConsents as $consent) {
            $label = $this->consentLabel($consent);
            $consentColumns[] = 'Consenso '.$label;
            $consentColumns[] = 'Scadenza '.$label;
        }
        // Se il partner non ha consensi attivi, mettiamo comunque una colonna vuota
        // per non lasciare la sezione priva di intestazioni.
        if (empty($consentColumns)) {
            $consentColumns = ['Consensi'];
        }

        return [
            ['label' => 'ORDINE', 'columns' => [
                'ID Ordine', 'ID Partecipante', 'Data creazione', 'Orario ordine',
                'Stato ordine', 'Stato pagamento', 'Metodo pagamento', 'Canale vendita',
            ]],
            ['label' => 'ESPERIENZA', 'columns' => [
                'Nome prodotto', 'Categoria', 'Data visita', 'Ora visita', 'Durata', 'Partner',
            ]],
            ['label' => 'ECONOMICO', 'columns' => [
                'Variante', 'Totale',
            ]],
            ['label' => 'CLIENTE', 'columns' => [
                'ID Cliente', 'Nome completo', 'Email', 'Telefono', 'Data di nascita',
                'Codice fiscale', 'Azienda', 'Partita IVA', 'Indirizzo', 'CAP',
                'Città', 'Provincia', 'Regione', 'Paese',
            ]],
            ['label' => 'PRIVACY', 'columns' => $consentColumns],
            ['label' => 'CHECK-IN', 'columns' => [
                'Stato check-in', 'Data check-in', 'Ora check-in',
            ]],
            ['label' => 'MARKETING', 'columns' => [
                'Codice sconto', 'UTM Source', 'UTM Medium', 'UTM Campaign',
                'Referral', 'Dispositivo', 'Browser', 'Lingua',
            ]],
            ['label' => 'NOTE', 'columns' => [
                'Note cliente', 'Note interne',
            ]],
            ['label' => 'COSTO SERVIZIO', 'columns' => [
                'Comm. pagamento €', 'Comm. pagamento %',
                'Comm. servizio €', 'Comm. servizio %',
                'Prevendita €',
            ]],
        ];
    }

    private function flattenColumns(array $groups): array
    {
        $columns = [];
        foreach ($groups as $group) {
            foreach ($group['columns'] as $col) {
                $columns[] = $col;
            }
        }

        return $columns;
    }

    private function writeHeaders($sheet, array $groups, array $headers): void
    {
        // Riga 1: intestazioni di gruppo con merge.
        $col = 1;
        foreach ($groups as $group) {
            $span = count($group['columns']);
            $from = Coordinate::stringFromColumnIndex($col).'1';
            $to = Coordinate::stringFromColumnIndex($col + $span - 1).'1';
            $sheet->setCellValue($from, $group['label']);
            if ($span > 1) {
                $sheet->mergeCells($from.':'.$to);
            }
            $sheet->getStyle($from.':'.$to)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F3864']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
            ]);
            $col += $span;
        }

        // Riga 2: intestazioni colonna.
        foreach ($headers as $i => $label) {
            $cell = Coordinate::stringFromColumnIndex($i + 1).'2';
            $sheet->setCellValue($cell, $label);
            $sheet->getStyle($cell)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9E1F2']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BFBFBF']]],
            ]);
        }
        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->getRowDimension(2)->setRowHeight(30);
        $sheet->freezePane('A3');
    }

    private function writeRow($sheet, int $rowIndex, Order $order, $orderProduct, $item, $participant, int $participantIndex, Collection $partnerConsents, array $consentsForOrder): void
    {
        $customer = $order->customer;
        $partner = $order->partner;
        $product = $orderProduct->product;
        $variant = $item?->variant;

        $orderStatusLabel = $order->order_status?->label();

        $paymentMethod = $order->card_brand
            ? trim(strtoupper($order->card_brand).' •••• '.($order->card_last4 ?? ''))
            : null;

        $checkinStatus = $participant?->status_label;
        $isCheckedIn = ($participant?->status ?? null) === 'checked_in';

        $unitPrice = (float) ($item?->unit_price ?? 0);
        $itemQty = (int) ($item?->quantity ?? 0);
        $itemSubtotal = round($unitPrice * max($itemQty, 1), 2);

        $paymentPct = (float) ($item?->partner_commission_payment ?? $partner?->commission_payment ?? 0);
        $servicePct = (float) ($item?->partner_commission_miticko_variable ?? $partner?->commission_miticko_variable ?? 0);
        $paymentAmount = round($itemSubtotal * $paymentPct / 100, 2);
        $serviceAmount = round($itemSubtotal * $servicePct / 100, 2);

        // Prevendita €: si applica per partecipante, dipende dal prezzo unitario e dalla soglia.
        $presaleLow = (float) ($item?->partner_commission_presale_low ?? 0);
        $presaleHigh = (float) ($item?->partner_commission_presale_high ?? 0);
        $presaleThreshold = $item?->partner_commission_presale_threshold;
        $presaleUnit = is_null($presaleThreshold)
            ? 0.0
            : ($unitPrice < (float) $presaleThreshold ? $presaleLow : $presaleHigh);

        $values = [];

        // ORDINE
        $values[] = '#'.$order->order_number;
        $values[] = $participant?->code ?? $participantIndex;
        $values[] = $order->created_at?->format('d/m/Y');
        $values[] = $order->created_at?->format('H:i');
        $values[] = $orderStatusLabel;
        $values[] = null; // Stato pagamento
        $values[] = $paymentMethod;
        $values[] = null; // Canale vendita

        // ESPERIENZA
        $values[] = $product?->label;
        $values[] = $product?->category?->label;
        $bookingDate = $orderProduct->booking_date;
        if ($bookingDate) {
            $bookingDate = $bookingDate instanceof \DateTimeInterface ? $bookingDate : \Carbon\Carbon::parse($bookingDate);
            $values[] = $bookingDate->format('d/m/Y');
        } else {
            $values[] = null;
        }
        $values[] = $orderProduct->booking_time ? substr($orderProduct->booking_time, 0, 5) : null;
        $values[] = $product?->duration;
        $values[] = $partner?->partner_name;

        // ECONOMICO
        $values[] = $variant?->label;
        $values[] = $itemSubtotal ?: null;

        // CLIENTE
        $values[] = $customer?->id;
        $values[] = trim(($customer?->name ?? '').' '.($customer?->surname ?? '')) ?: null;
        $values[] = $customer?->email;
        $values[] = $customer?->phone
            ? trim(($customer->prefix_phone ?? '').' '.$customer->phone)
            : null;
        $values[] = $customer?->birth_date
            ? (\Carbon\Carbon::parse($customer->birth_date))->format('d/m/Y')
            : null;
        $values[] = $customer?->fiscal_code;
        $values[] = null; // Azienda
        $values[] = null; // Partita IVA
        $values[] = $customer?->address;
        $values[] = $customer?->zip_code;
        $values[] = $customer?->city;
        $values[] = null; // Provincia
        $values[] = null; // Regione
        $values[] = $customer?->country?->name;

        // PRIVACY (consensi dinamici)
        if ($partnerConsents->isEmpty()) {
            $values[] = null;
        } else {
            foreach ($partnerConsents as $consent) {
                $cc = $consentsForOrder[$consent->id] ?? null;
                if ($cc) {
                    $values[] = $cc->accepted ? 'CONCESSO' : 'NEGATO';
                    $values[] = $cc->expires_at?->format('d/m/Y');
                } else {
                    $values[] = null;
                    $values[] = null;
                }
            }
        }

        // CHECK-IN
        $values[] = $checkinStatus;
        $values[] = $isCheckedIn ? $participant?->updated_at?->format('d/m/Y') : null;
        $values[] = $isCheckedIn ? $participant?->updated_at?->format('H:i') : null;

        // MARKETING
        $values[] = null; // Codice sconto
        $values[] = null; // UTM Source
        $values[] = null; // UTM Medium
        $values[] = null; // UTM Campaign
        $values[] = null; // Referral
        $values[] = null; // Dispositivo
        $values[] = null; // Browser
        $values[] = 'it'; // Lingua

        // NOTE
        $values[] = $order->customer_note;
        $values[] = $order->internal_note;

        // COSTO SERVIZIO
        $values[] = $paymentAmount ?: null;
        $values[] = $paymentPct ? $paymentPct.'%' : null;
        $values[] = $serviceAmount ?: null;
        $values[] = $servicePct ? $servicePct.'%' : null;
        $values[] = $presaleUnit ?: null;

        foreach ($values as $i => $value) {
            $sheet->setCellValueExplicit(
                Coordinate::stringFromColumnIndex($i + 1).$rowIndex,
                $value ?? '',
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );
        }
    }

    private function autosizeColumns($sheet, int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
    }

    /**
     * Ritorna un array indicizzato per "customer_id|partner_id" contenente
     * i CustomerConsent indicizzati per partner_consent_id, così durante la
     * scrittura riga possiamo fare lookup O(1).
     */
    private function loadCustomerConsents(Collection $orders, Collection $partnerConsents): array
    {
        if ($partnerConsents->isEmpty() || $orders->isEmpty()) {
            return [];
        }

        $consentIds = $partnerConsents->pluck('id')->all();
        $customerIds = $orders->pluck('customer_id')->filter()->unique()->all();
        $partnerIds = $orders->pluck('partner_id')->filter()->unique()->all();

        $records = CustomerConsent::whereIn('partner_consent_id', $consentIds)
            ->whereIn('customer_id', $customerIds)
            ->whereIn('partner_id', $partnerIds)
            ->get();

        $indexed = [];
        foreach ($records as $r) {
            $key = $r->customer_id.'|'.$r->partner_id;
            $indexed[$key][$r->partner_consent_id] = $r;
        }

        return $indexed;
    }

    private function consentLabel(PartnerConsent $consent): string
    {
        if (! empty($consent->code)) {
            return Str::of($consent->code)->replace(['_', '-'], ' ')->ucfirst()->toString();
        }

        $raw = trim(strip_tags($consent->contentField('content', 'it') ?? ''));
        if ($raw !== '') {
            // Rimuove il prefisso "Consenso" se già presente nel testo, per non
            // duplicare la parola nell'intestazione "Consenso X" / "Scadenza X".
            $raw = preg_replace('/^\s*consenso\s+/i', '', $raw);
            return Str::limit($raw, 40, '…');
        }

        return '#'.$consent->id;
    }
}
