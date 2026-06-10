@php
    $brand = $order->partner?->brand ?? config('design.default_brand', 'miticko');
    $t = config("design.brands.{$brand}.tokens")
        ?? config('design.brands.miticko.tokens', []);

    // La ricevuta è un documento del servizio Miticko: font sempre DM Sans
    // a prescindere dal brand del partner (i colori restano per-brand).
    $receiptFontFamily = 'DM Sans';
    $fontFaces = [];
    foreach ([300, 400, 500, 700] as $w) {
        $slug = str_replace(' ', '', $receiptFontFamily);
        $path = storage_path("fonts/{$slug}-{$w}.ttf");
        if (is_file($path)) {
            $fontFaces[] = [
                'family' => $receiptFontFamily,
                'weight' => $w,
                'src'    => 'file://' . $path,
            ];
        }
    }
@endphp
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Biglietto MTK-{{ $order->order_number }}</title>
    <style>
        @page { margin: 0; }

        @foreach($fontFaces as $face)
        @font-face {
            font-family: "{{ $face['family'] }}";
            font-style: normal;
            font-weight: {{ $face['weight'] }};
            src: url({{ $face['src'] }}) format("truetype");
        }
        @endforeach

        * { box-sizing: border-box; }

        body {
            font-family: "{{ $receiptFontFamily }}", DejaVu Sans, sans-serif;
            color: {{ $t['text-main'] }};
            font-size: 11px;
            line-height: 1.55;
            margin: 0;
            padding: 0;
        }

        .page-content {
            padding: 37px 30px 90px 30px;
        }

        /* --- Top tab "Email - Biglietto" --- */
        .tab-strip {
            margin: 0 0 12px;
            font-size: 10px;
            color: {{ $t['text-secondary'] }};
        }
        .tab-strip .accent {
            display: inline-block;
            width: 6px;
            height: 6px;
            background: #2F6BFF;
            border-radius: 50%;
            margin-right: 6px;
            vertical-align: middle;
        }
        .top-band {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            height: 15px;
            background: {{ $t['brand-primary-brand'] }};
            line-height: 0;
            font-size: 0;
        }

        /* --- Header --- */
        .ticket-header {
            width: 100%;
            margin-bottom: 22px;
        }
        .ticket-header td { vertical-align: top; }
        .ticket-header .partner-name {
            font-size: 14px;
            font-weight: 700;
            color: {{ $t['text-main'] }};
            line-height: 1.2;
        }
        .ticket-header .partner-site {
            font-size: 11px;
            color: {{ $t['text-secondary'] }};
            margin-top: 3px;
        }
        .ticket-header .right { text-align: right; }
        .ticket-header .brand img { height: 20px; }
        .ticket-header .brand .fallback {
            font-size: 15px;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: {{ $t['text-main'] }};
        }
        .ticket-header .order-id {
            margin-top: 6px;
            font-size: 10.5px;
            color: {{ $t['text-secondary'] }};
        }

        /* --- Ticket card --- */
        .ticket-card {
            width: 100%;
            background: {{ $t['brand-primary-brandlight'] }};
            border-radius: 16px;
            border-collapse: separate;
            border-spacing: 0;
            margin: 0 0 26px;
        }
        .ticket-card-cell {
            padding: 22px 24px 24px 24px;
        }
        .ticket-card .badge-wrap { margin: 0 0 14px; }
        .ticket-card .badge {
            display: inline-block;
            background: {{ $t['brand-primary-brand'] }};
            color: {{ $t['background-global-paper1'] }};
            font-size: 9.5px;
            font-weight: 700;
            letter-spacing: 0.7px;
            text-transform: uppercase;
            padding: 6px 14px;
            border-radius: 9999px;
        }
        .ticket-card-body { width: 100%; border-collapse: collapse; }
        .ticket-card-body td { vertical-align: top; }
        .ticket-card-body .left { padding-right: 16px; }
        .ticket-card .product-title {
            font-size: 18px;
            font-weight: 700;
            color: {{ $t['text-main'] }};
            margin: 0 0 4px;
            line-height: 1.25;
        }
        .ticket-card .product-variant {
            font-size: 12.5px;
            color: {{ $t['text-main'] }};
            margin-bottom: 16px;
            font-weight: 400;
        }
        .ticket-card .slot {
            font-size: 12px;
            color: {{ $t['text-main'] }};
            margin-bottom: 16px;
        }
        .ticket-card .guest {
            font-size: 12.5px;
            font-weight: 700;
            color: {{ $t['text-main'] }};
        }
        .ticket-card .qr {
            width: 210px;
            text-align: right;
        }
        .qr-box {
            background: {{ $t['background-global-paper1'] }};
            border-radius: 14px;
            border-collapse: separate;
            border-spacing: 0;
        }
        .qr-box td {
            padding: 4px;
        }
        .qr-box img {
            width: 130px;
            height: 130px;
            display: block;
        }
        .ticket-card .qr .code {
            font-family: DejaVu Sans Mono, monospace;
            font-size: 9.5px;
            color: {{ $t['text-secondary'] }};
            letter-spacing: 0.3px;
            text-align: center;
            padding-top: 3px;
        }

        @if($brand === 'veleia')
        /* --- Override Veleia: bordi squadrati per ticket card e QR box --- */
        .ticket-card { border-radius: 0; }
        .ticket-card .qr { width: 230px; }
        .qr-box { border-radius: 0; }
        .qr-box td { padding: 26px 28px 22px 28px; }
        .qr-box img { width: 150px; height: 150px; }
        .ticket-card .qr .code {
            font-size: 10.5px;
            padding-top: 8px;
        }
        @endif

        /* --- Section --- */
        .section { margin: 0 0 24px; }
        .section h3 {
            font-size: 13px;
            font-weight: 700;
            color: {{ $t['text-main'] }};
            margin: 0 0 10px;
        }
        .section p {
            margin: 0 0 8px;
            font-size: 10px;
            font-weight: 300;
            line-height: normal;
            color: {{ $t['text-main'] }};
        }
        .section p:last-child { margin-bottom: 0; }

        /* --- Meta grid (Durata / Tipologia / ...) --- */
        .meta-grid {
            width: 100%;
            border-top: 1px solid {{ $t['border-default'] }};
            border-bottom: 1px solid {{ $t['border-default'] }};
            margin: 0 0 24px;
        }
        .meta-grid td {
            width: 25%;
            padding: 14px 8px 14px 0;
            vertical-align: top;
        }
        .meta-grid .label {
            font-size: 10.5px;
            color: {{ $t['text-main'] }};
            margin-bottom: 6px;
            font-weight: 700;
        }
        .meta-grid .value {
            font-size: 12px;
            color: {{ $t['text-main'] }};
            font-weight: 300;
        }

        /* --- Terms --- */
        .terms p {
            font-size: 9px;
            color: {{ $t['text-secondary'] }};
            line-height: 1.5;
            margin: 0 0 6px;
        }
        .terms strong { color: {{ $t['text-main'] }}; font-weight: 700; }

        /* --- Footer --- */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            background: {{ $t['background-global-paper3'] }};
        }
        .footer table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }
        .footer td {
            vertical-align: middle;
            padding: 16px 12px 16px 30px;
        }
        .footer .logo {
            width: 130px;
        }
        .footer .logo img { height: 22px; display: block; }
        .footer .logo .fallback {
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: {{ $t['text-main'] }};
        }
        .footer .info {
            padding-right: 30px;
        }
        .footer p {
            font-size: 9.5px;
            color: {{ $t['text-secondary'] }};
            line-height: 1.55;
            margin: 0;
        }

        /* --- Page break --- */
        .page-break { page-break-after: always; }
        .page-break:last-child { page-break-after: auto; }
    </style>
</head>
<body>
@php
    use Illuminate\Support\Carbon;
    use chillerlan\QRCode\QRCode;
    use chillerlan\QRCode\QROptions;
    use chillerlan\QRCode\Common\EccLevel;
    use chillerlan\QRCode\Output\QRGdImagePNG;

    $qrRenderer = new QRCode(new QROptions([
        'outputInterface' => QRGdImagePNG::class,
        'eccLevel'        => EccLevel::M,
        'scale'           => 8,
        'outputBase64'    => true,
        'addQuietzone'    => true,
        'quietzoneSize'   => 1,
    ]));

    $partnerLogoMedia = $order->partner?->logo ?? $order->partner?->cover;
    $logoPath = null;
    $logoMime = null;
    if ($partnerLogoMedia && \Illuminate\Support\Facades\Storage::disk('public')->exists($partnerLogoMedia->file_path)) {
        $logoPath = \Illuminate\Support\Facades\Storage::disk('public')->path($partnerLogoMedia->file_path);
        $logoMime = $partnerLogoMedia->file_type ?: 'image/png';
    }
    $hasLogo = $logoPath !== null;
    $logoSrc = $hasLogo ? 'data:' . $logoMime . ';base64,' . base64_encode(file_get_contents($logoPath)) : null;

    // Il footer riporta sempre il marchio Miticko, indipendentemente dal brand del partner.
    $footerLogoPath = public_path('assets/images/logo-miticko.png');
    $hasFooterLogo  = file_exists($footerLogoPath);
    $footerLogoSrc  = $hasFooterLogo ? 'data:image/png;base64,' . base64_encode(file_get_contents($footerLogoPath)) : null;

    $orderCode    = $order->order_number;
    $partnerName  = $order->partner?->partner_name;
    $partnerSite  = $order->partner?->domain_name;
    $guestName    = $order->customer?->full_name;
    $purchasedAt  = $order->paid_at ?? $order->created_at;

    $payment = match (true) {
        (float) $order->amount === 0.0 => 'Ingresso gratuito',
        ! empty($order->card_brand) => trim(ucfirst($order->card_brand) . ($order->card_last4 ? ' · ' . $order->card_last4 : '')),
        ! empty($order->stripe_payment_method) => 'Carta di credito',
        default => '—',
    };

    // Costruisco la lista dei biglietti da stampare: un'entry per ogni OrderParticipant.
    $tickets = collect();
    foreach ($order->orderProducts as $op) {
        foreach ($op->items as $item) {
            foreach ($item->orderProductItem?->id ? collect() : collect() as $_) {}
            $itemParticipants = $order->participants
                ->where('order_product_item_id', $item->id)
                ->values();

            $variantLabel = $item->variant?->label;
            $unitPrice    = (float) $item->unit_price;

            foreach ($itemParticipants as $participant) {
                $tickets->push([
                    'participant'   => $participant,
                    'order_product' => $op,
                    'item'          => $item,
                    'product'       => $op->product,
                    'variant_label' => $variantLabel,
                    'unit_price'    => $unitPrice,
                ]);
            }
        }
    }

    if ($tickets->isEmpty()) {
        // Fallback per ordini legacy senza participants/items: una stampa per quantity.
        foreach ($order->orderProducts as $op) {
            $qty = max(1, (int) $op->quantity);
            for ($i = 0; $i < $qty; $i++) {
                $tickets->push([
                    'participant'   => null,
                    'order_product' => $op,
                    'item'          => null,
                    'product'       => $op->product,
                    'variant_label' => null,
                    'unit_price'    => $op->quantity > 0 ? $op->total / $op->quantity : $op->total,
                ]);
            }
        }
    }

    $totalTickets = $tickets->count();
@endphp

{{-- Banda arancione e footer: position:fixed → DomPDF li ripete su ogni pagina --}}
<div class="top-band">&nbsp;</div>

<div class="footer">
    <table>
        <tr>
            <td class="logo">
                @if($hasFooterLogo)
                    <img src="{{ $footerLogoSrc }}" alt="Miticko">
                @else
                    <span class="fallback">miticko</span>
                @endif
            </td>
            <td class="info">
                <p>
                    Servizio offerto da Miticko (miticko.com) – Miticko.com è un brand di Colli Italiani S.N.C.<br>
                    P.IVA 12343060963 – San Giuliano Milanese (MI) Via Fratelli Rizzi 8 – CAP 20098 – Italia
                </p>
            </td>
        </tr>
    </table>
</div>

@foreach($tickets as $index => $ticket)
    @php
        $product       = $ticket['product'];
        $op            = $ticket['order_product'];
        $participant   = $ticket['participant'];
        $variantLabel  = $ticket['variant_label'] ?? $product?->label;
        $unitPrice     = $ticket['unit_price'];
        $bookingDate   = $op->booking_date ? Carbon::parse($op->booking_date) : null;
        $bookingTime   = $op->booking_time ? substr($op->booking_time, 0, 5) : null;
        $code          = $participant?->code ?? sprintf('mtk%05d%02d', $order->id, $index + 1);

        $durationLabel = null;
        if ($product?->duration_minutes) {
            $durationLabel = $product->duration_minutes . ' min';
        } elseif ($product?->duration_hours) {
            $durationLabel = $product->duration_hours . ' h';
        } elseif ($product?->duration_days) {
            $durationLabel = $product->duration_days . ' g';
        }

        $typeLabel = $product?->category?->label ?? ($product?->product_type ? __('products.types.' . $product->product_type) : null);

        try {
            $qrDataUri = $qrRenderer->render($code);
        } catch (\Throwable $e) {
            $qrDataUri = null;
        }
    @endphp

    <div class="{{ $index < $totalTickets - 1 ? 'page-break' : '' }}">
        <div class="page-content">

        {{-- HEADER --}}
        <table class="ticket-header">
            <tr>
                <td style="width: 60%;">
                    <div class="partner-name">{{ $partnerName ?? '—' }}</div>
                    @if($partnerSite)
                        <div class="partner-site">{{ $partnerSite }}</div>
                    @endif
                </td>
                <td class="right" style="width: 40%;">
                    <div class="brand">
                        @if($hasLogo)
                            <img src="{{ $logoSrc }}" alt="{{ ucfirst($brand) }}">
                        @else
                            <span class="fallback">{{ $brand }}</span>
                        @endif
                    </div>
                    <div class="order-id">Ordine #{{ $orderCode }}</div>
                </td>
            </tr>
        </table>

        {{-- TICKET CARD --}}
        <table class="ticket-card">
            <tr>
                <td class="ticket-card-cell">
                    <div class="badge-wrap">
                        <span class="badge">Biglietto {{ $index + 1 }} di {{ $totalTickets }}</span>
                    </div>
                    <table class="ticket-card-body">
                        <tr>
                            <td class="left">
                                <div class="product-title">{{ $product?->contentField('short_title') ?? '—' }}</div>
                                <div class="product-variant">
                                    {{ $variantLabel ?? '—' }}
                                    @if($unitPrice !== null)
                                        - {{ number_format((float) $unitPrice, 2, ',', '.') }}€
                                    @endif
                                </div>
                                @if($bookingDate)
                                    <div class="slot">
                                        {{ $bookingDate->translatedFormat('j F Y') }}
                                        @if($bookingTime) / Ore {{ $bookingTime }} @endif
                                    </div>
                                @endif
                                @if($guestName)
                                    <div class="guest">{{ $guestName }}</div>
                                @endif
                            </td>
                            <td class="qr">
                                <table class="qr-box" align="right">
                                    <tr>
                                        <td>
                                            @if($qrDataUri)
                                                <img src="{{ $qrDataUri }}" alt="QR {{ $code }}">
                                            @else
                                                <div style="width:130px;height:130px;border:1px dashed #B89C8B;border-radius:6px;"></div>
                                            @endif
                                            <div class="code">{{ $code }}</div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        {{-- INFORMAZIONI PER LA VISITA --}}
        @php
            $visitInfo    = $product?->contentField('visit_info');
            $supportEmail = $product?->support_email;
        @endphp
        @if($visitInfo || $supportEmail)
            <div class="section">
                <h3>Informazioni per la visita</h3>
                @if($visitInfo)
                    @foreach(preg_split('/\R+/', trim($visitInfo)) as $paragraph)
                        @if(trim($paragraph) !== '')
                            <p>{{ $paragraph }}</p>
                        @endif
                    @endforeach
                @endif
                @if($supportEmail)
                    <p>
                        Per esigenze di accessibilità, assistenza o variazioni della prenotazione scrivere a {{ $supportEmail }} almeno 48 ore prima della visita.
                    </p>
                @endif
            </div>
        @endif

        {{-- META GRID --}}
        <table class="meta-grid">
            <tr>
                <td>
                    <div class="label">Durata</div>
                    <div class="value">{{ $durationLabel ?? '—' }}</div>
                </td>
                <td>
                    <div class="label">Tipologia</div>
                    <div class="value">{{ $typeLabel ?? '—' }}</div>
                </td>
                <td>
                    <div class="label">Acquistato il</div>
                    <div class="value">{{ $purchasedAt ? $purchasedAt->translatedFormat('j F Y') : '—' }}</div>
                </td>
                <td>
                    <div class="label">Pagamento</div>
                    <div class="value">{{ $payment }}</div>
                </td>
            </tr>
        </table>

        {{-- TERMS --}}
        <div class="section terms">
            <p>
                <strong>Trattamento dei dati personali.</strong> I dati personali raccolti sono trattati ai sensi del Regolamento (UE) 2016/679 (GDPR) e del D.lgs. 196/2003 esclusivamente per la gestione della prenotazione e degli obblighi fiscali correlati. L'informativa completa è disponibile su miticko.com/privacy. Il titolare del trattamento è Miticko S.r.l.
            </p>
            <p>
                <strong>Foro competente.</strong> Per ogni controversia relativa all'interpretazione, esecuzione e risoluzione del contratto è competente in via esclusiva il Foro di Firenze, fatta salva l'applicazione delle normative inderogabili a tutela dei consumatori.
            </p>
        </div>

        </div>{{-- /.page-content --}}
    </div>
@endforeach
</body>
</html>
