@extends('backoffice.layout', ['title' => $model->full_name, 'active' => $path])

@section('main-content')
    @php($ordersCount = $orders->count())
    @php($ordersTotal = $orders->sum('amount'))
    @php($firstOrder = $orders->last())
    @php($lastOrder = $orders->first())

    {{-- HEADER --}}
    <div class="order-show-header d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3 mb-spacing-2xl">
        <div class="d-flex gap-3 align-items-start">
            <a href="{{ route('customers.index') }}" class="text-decoration-none">
                <x-button status="Primary" emphasis="MediumLow" leading="fa-arrow-left" />
            </a>
            <div>
                <x-breadcrumb :first="['Clienti', 'customers.index']" :second="[$model->full_name]" />
                <x-header-page :title="$model->full_name" />
                <div class="order-show-meta">
                    Cliente registrato il {{ $model->created_at->translatedFormat('j F Y') }} alle {{ $model->created_at->format('H:i') }}
                </div>
            </div>
        </div>
    </div>

    {{-- STAT STRIP --}}
    <div class="row g-3 mb-spacing-2xl order-stat-strip">
        <div class="col-12 col-md-6 col-xl">
            <x-card>
                <div class="stat-label">ORDINI</div>
                <div class="stat-value">{{ $ordersCount }} {{ $ordersCount === 1 ? 'ordine' : 'ordini' }}</div>
                @if($firstOrder)
                    <div class="stat-sub">Primo: {{ $firstOrder->created_at->translatedFormat('j M Y') }}</div>
                @endif
            </x-card>
        </div>
        <div class="col-12 col-md-6 col-xl">
            <x-card>
                <div class="stat-label">SPESA TOTALE</div>
                <div class="stat-value">{{ number_format($ordersTotal, 2, ',', '.') }} €</div>
                @if($ordersCount > 0)
                    <div class="stat-sub">Media: {{ number_format($ordersTotal / $ordersCount, 2, ',', '.') }} € / ordine</div>
                @endif
            </x-card>
        </div>
        <div class="col-12 col-md-6 col-xl">
            <x-card>
                <div class="stat-label">ULTIMO ACQUISTO</div>
                @if($lastOrder)
                    <div class="stat-value">{{ $lastOrder->created_at->translatedFormat('j M Y') }}</div>
                    <div class="stat-sub">#{{ $lastOrder->order_number }}</div>
                @else
                    <div class="stat-value">—</div>
                @endif
            </x-card>
        </div>
        <div class="col-12 col-md-6 col-xl">
            <x-card>
                <div class="stat-label">CONTATTI</div>
                <div class="stat-value" style="font-size: 14px;">{{ $model->email ?: '—' }}</div>
                @if($model->phone)
                    <div class="stat-sub">{{ trim(($model->prefix_phone ?? '') . ' ' . $model->phone) }}</div>
                @endif
            </x-card>
        </div>
    </div>

    {{-- BODY GRID --}}
    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <x-card title="Ordini del cliente">
                @if($orders->isEmpty())
                    <div class="text-secondary">Nessun ordine effettuato.</div>
                @else
                    <div class="order-detail-tickets-wrap">
                        <table class="order-detail-tickets">
                            <thead>
                                <tr>
                                    <th>Ordine</th>
                                    <th>Data</th>
                                    <th>Prodotto</th>
                                    <th>Partner</th>
                                    <th>Stato</th>
                                    <th class="text-end">Totale</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($orders as $order)
                                    @php($firstProduct = $order->orderProducts->first()?->product)
                                    <tr>
                                        <td><strong>#{{ $order->order_number }}</strong></td>
                                        <td>{{ $order->created_at->translatedFormat('j M Y') }}</td>
                                        <td>{{ $firstProduct?->contentField('short_title') ?? $firstProduct?->label ?? '—' }}</td>
                                        <td>{{ $order->partner?->partner_name ?? '—' }}</td>
                                        <td>
                                            @include('backoffice.components.label', [
                                                'status' => $order->order_status->status(),
                                                'label'  => $order->order_status->label(),
                                            ])
                                        </td>
                                        <td class="text-end">{{ number_format((float) $order->amount, 2, ',', '.') }} €</td>
                                        <td class="text-end">
                                            <a href="{{ route('orders.show', $order->id) }}" class="detail-link">Apri</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>
        </div>

        <div class="col-12 col-lg-4 d-flex flex-column gap-3">
            <x-card title="Anagrafica">
                <div class="customer-block">
                    <div class="detail-label">Nome</div>
                    <div class="detail-value"><i class="fa-regular fa-user"></i> {{ $model->full_name }}</div>
                </div>
                <div class="customer-block mt-spacing-l">
                    <div class="detail-label">Email</div>
                    <div class="detail-value"><i class="fa-regular fa-envelope"></i> {{ $model->email ?: '—' }}</div>
                </div>
                <div class="customer-block mt-spacing-l">
                    <div class="detail-label">Telefono</div>
                    <div class="detail-value"><i class="fa-regular fa-phone"></i> {{ $model->phone ? trim(($model->prefix_phone ?? '') . ' ' . $model->phone) : '—' }}</div>
                </div>
                <div class="customer-block mt-spacing-l">
                    <div class="detail-label">Indirizzo</div>
                    <div class="detail-value"><i class="fa-regular fa-location-dot"></i> {{ $model->address ? $model->full_address : '—' }}</div>
                </div>
                <div class="customer-block mt-spacing-l">
                    <div class="detail-label">Paese</div>
                    <div class="detail-value"><i class="fa-regular fa-globe"></i> {{ $model->country?->name ?? '—' }}</div>
                </div>
                <div class="customer-block mt-spacing-l">
                    <div class="detail-label">Codice fiscale</div>
                    <div class="detail-value"><i class="fa-regular fa-id-card"></i> {{ $model->fiscal_code ?: '—' }}</div>
                </div>
                @if($model->birth_date)
                    <div class="customer-block mt-spacing-l">
                        <div class="detail-label">Data di nascita</div>
                        <div class="detail-value"><i class="fa-regular fa-cake-candles"></i> {{ \Carbon\Carbon::parse($model->birth_date)->translatedFormat('j F Y') }}</div>
                    </div>
                @endif
            </x-card>

            <x-card title="Consensi utente">
                @if($customerConsents->isEmpty())
                    <div class="text-secondary">Nessun consenso registrato per questo cliente.</div>
                @else
                    @foreach($customerConsents as $i => $consent)
                        <div class="consent-block @if($i > 0) mt-spacing-l @endif">
                            <div class="d-flex align-items-start justify-content-between gap-2">
                                <div class="detail-value flex-grow-1">{{ $consent['label'] }}</div>

                                @if($consent['is_expired'])
                                    <span class="consent-status consent-status--expired">
                                        <i class="fa-regular fa-triangle-exclamation"></i> SCADUTO
                                    </span>
                                @elseif($consent['accepted'])
                                    <span class="consent-status consent-status--granted">
                                        <i class="fa-solid fa-check"></i> CONCESSO
                                    </span>
                                @else
                                    <span class="consent-status consent-status--denied">
                                        <i class="fa-solid fa-xmark"></i> NON CONCESSO
                                    </span>
                                @endif
                            </div>

                            @if($consent['accepted'])
                                <div class="detail-label mt-spacing-xs">
                                    <i class="fa-regular fa-calendar"></i>
                                    Sottoscrizione: {{ $consent['subscribed_at']?->translatedFormat('j M Y') ?? '—' }}
                                </div>
                                <div class="detail-label">
                                    @if($consent['is_expired'])
                                        <i class="fa-solid fa-hourglass-end"></i>
                                        Scaduto il {{ $consent['expires_at']->translatedFormat('j M Y') }}
                                    @else
                                        <i class="fa-regular fa-hourglass"></i>
                                        Scadenza: {{ $consent['expires_at']?->translatedFormat('j M Y') ?? 'Nessuna' }}
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                @endif
            </x-card>

            <div class="d-flex gap-2 order-show-footer-actions">
                <a href="{{ route('customers.index') }}" class="text-decoration-none flex-grow-1">
                    <x-button label="Torna ai clienti" status="Neutral" emphasis="Medium" class="w-100" />
                </a>
            </div>
        </div>
    </div>
@endsection
