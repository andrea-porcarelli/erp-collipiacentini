@extends('backoffice.layout', ['title' => '#' . $model->order_number, 'active' => $path])

@section('main-content')
    @php($order = $model)
    @php($firstOp = $order->orderProducts->first())
    @php($product = $firstOp?->product)
    @php($category = $product?->category)
    @php($effettuatoIl = $order->paid_at ?? $order->created_at)
    @php($ticketsTotal = $order->orderProducts->sum(fn($op) => $op->items->isNotEmpty() ? $op->items->sum('quantity') : (int) $op->quantity))
    @php($cardLabel = $order->card_brand ? ucfirst($order->card_brand) . ' · ' . ($order->card_last4 ?? '••••') : '—')

    {{-- HEADER --}}

    @section('custom-css')
    <style>
        .modal {
            --bs-modal-width: 350px !important;
        }
        .flatpickr-day {
            font-weight:var(--weight,700);
        }
        .flatpickr-day.flatpickr-disabled {
            text-decoration-line: line-through;
            font-weight:var(--weight, 300);
            color: var(--text-disabled, #999);
        }
        .flatpickr-day.selected {
            border-radius: var(--border-radius-s, 8px) !important;
            border: 1px solid var(--brand-secondary-brand, #3948D3);
            background: var(--brand-secondary-brand, #3948D3);
            line-height: 38px;
        }
        .flatpickr-day.today {
            border: none;
            border-radius: var(--border-radius-0, 0) !important;
            background: var(--brand-secondary-brandlight, #EAEEFA) !important;
            color: var(--brand-secondary-brand, #3948D3) !important;
            text-align: center;

            /* Title */
            font-family: var(--typography-web-title-font, "DM Sans");
            font-size: var(--size, 14px);
            font-style: normal;
            font-weight: var(--weight, 700);
        }
    </style>
    @endsection
    <div class="order-show-header d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3 mb-spacing-2xl">
        <div class="d-flex gap-3 align-items-start">
            <a href="{{ route('orders.index') }}" class="text-decoration-none">
                <x-button status="Primary" emphasis="MediumLow" leading="fa-arrow-left" />
            </a>
            <div>
                <x-breadcrumb :first="['Ordini', 'orders.index']" :second="['#' . $order->order_number]" />
                <x-header-page :title="'#' . $order->order_number" />
                <div class="order-show-meta">
                    Ordine creato il {{ $order->created_at->translatedFormat('j F Y') }} alle {{ $order->created_at->format('H:i') }}
                </div>
            </div>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            @include('backoffice.components.label', [
                'status' => $order->order_status->status(),
                'label' => $order->order_status->label(),
            ])
            <x-button id="btn-send-email" label="Invia email ordine" status="Primary" emphasis="MediumLow" />
            <x-button id="btn-download-receipt" label="Scarica ricevuta" status="Primary" emphasis="Medium"  />
        </div>
    </div>

    {{-- STAT STRIP --}}
    <div class="row g-3 mb-spacing-2xl order-stat-strip">
        <div class="col-12 col-md-6 col-xl">
            <x-card>
                <div class="stat-label">DATA VISITA</div>
                @if($firstOp)
                    <div class="stat-value">{{ \Carbon\Carbon::parse($firstOp->booking_date)->translatedFormat('j M Y') }}</div>
                    <div class="stat-sub">
                        Ore {{ substr($firstOp->booking_time, 0, 5) }}
                        @if($product?->duration_minutes)
                            · {{ $product->duration_minutes }} min
                        @endif
                    </div>
                @else
                    <div class="stat-value">—</div>
                @endif
            </x-card>
        </div>
        <div class="col-12 col-md-6 col-xl">
            <x-card>
                <div class="stat-label">CLIENTE</div>
                <div class="stat-value">{{ $order->customer->name }} {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($order->customer->surname ?? '', 0, 1)) }}.</div>
                @if($order->customer->phone)
                    <div class="stat-sub">{{ trim(($order->customer->prefix_phone ?? '') . ' ' . $order->customer->phone) }}</div>
                @endif
            </x-card>
        </div>
        <div class="col-12 col-md-6 col-xl">
            <x-card>
                <div class="stat-label">BIGLIETTI</div>
                <div class="stat-value">{{ $ticketsTotal }} biglietti</div>
                @if($product)
                    <div class="stat-sub">{{ $product->label }}</div>
                @endif
            </x-card>
        </div>
        <div class="col-12 col-md-6 col-xl">
            <x-card>
                <div class="stat-label">TOTALE</div>
                <div class="stat-value">{{ number_format($order->amount, 2, ',', '.') }} €</div>
            </x-card>
        </div>
    </div>

    {{-- BODY GRID --}}
    <div class="row g-3">
        <div class="col-12 col-lg-8">

            @php($participants = $order->participants()->with('orderProductItem.variant')->orderBy('id')->get())
            @php($checkinTotal = $participants->count())
            @php($checkinDone = $participants->where('status', 'checked_in')->count())
            @php($statusOptions = ['booked' => 'Prenotato', 'checked_in' => 'Arrivato', 'no_show' => 'No show', 'refunded' => 'Rimborsato', 'cancelled' => 'Annullato'])
            <x-card class="position-relative mb-spacing-xl order-checkin-card" title="Check-in visitatori">
                @if($participants->isEmpty())
                    <div class="text-secondary">Nessun partecipante per questo ordine.</div>
                @else
                    <div class="ticket-scanner-content order-checkin-content" data-order-id="{{ $order->id }}">
                        <div class="ts-checkin-counter order-checkin-counter">
                            <div class="ts-checkin-counter-main">
                                <span data-role="card-checkin-count">{{ $checkinDone }}</span> / <span data-role="card-checkin-total">{{ $checkinTotal }}</span> arrivati
                            </div>
                            <div class="ts-checkin-counter-sub">{{ $checkinTotal }} {{ $checkinTotal === 1 ? 'visitatore atteso' : 'visitatori attesi' }}</div>
                        </div>

                        <button type="button" class="ts-btn-all-arrived order-checkin-all-arrived" data-role="card-all-arrived">
                            <i class="fa-solid fa-check"></i> Arrivati tutti
                        </button>

                        <ul class="ts-tickets-list order-checkin-tickets">
                            @foreach($participants as $i => $participant)
                                @php($variantLabel = $participant->orderProductItem?->variant?->label ?? '—')
                                @php($displayCode = sprintf('MTK-%s-%02d', $order->order_number, $i + 1))
                                <li class="ts-ticket-row" data-participant-id="{{ $participant->id }}">
                                    <div class="ts-ticket-info">
                                        <div class="ts-ticket-title">Biglietto {{ $i + 1 }} · <span class="ts-ticket-variant">{{ $variantLabel }}</span></div>
                                        <div class="ts-ticket-code">{{ $displayCode }}</div>
                                    </div>
                                    <div class="ts-ticket-status">
                                        <select class="ts-status-select ts-status-{{ $participant->status }}" data-role="card-status-select" data-original="{{ $participant->status }}">
                                            @foreach($statusOptions as $value => $label)
                                                <option value="{{ $value }}" {{ $participant->status === $value ? 'selected' : '' }}>{{ mb_strtoupper($label) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </li>
                            @endforeach
                        </ul>

                        <button type="button" class="ts-btn-save-inline order-checkin-save" data-role="card-save-changes">Salva</button>
                    </div>
                @endif
            </x-card>

            <x-card :title="'Dettaglio ordine #' . $order->order_number">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <x-input
                            label="Stato pagamento"
                            name="payment_status_label"
                            :value="$order->order_status->label()"
                            :disabled="true"
                            trailing="fa-chevron-down"
                            trailing_style="solid"
                        />
                    </div>
                    <div class="col-12 col-md-6">
                        <x-input
                            label="Metodo di pagamento"
                            name="payment_method_label"
                            :value="$cardLabel"
                            :disabled="true"
                            leading="fa-credit-card"
                            leading_style="regular"
                            trailing="fa-chevron-down"
                            trailing_style="solid"
                        />
                    </div>
                </div>

                <hr class="order-divider" />

                <div class="row g-3 order-detail-grid">
                    <div class="col-12 col-md-6">
                        <div class="detail-label">Prodotto</div>
                        <div class="detail-value">{{ $product?->label ?? '—' }}</div>
                        @if($product)
                            <a href="{{ route('products.show', $product->id) }}" class="detail-link">Apri scheda prodotto</a>
                        @endif
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="detail-label">Categoria</div>
                        <div class="detail-value">{{ $category?->label ?? '—' }}</div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="detail-label">Data e ora visita</div>
                        @if($firstOp)
                            <div class="detail-value">{{ \Carbon\Carbon::parse($firstOp->booking_date)->translatedFormat('j F Y') }} · ore {{ substr($firstOp->booking_time, 0, 5) }}</div>
                        @else
                            <div class="detail-value">—</div>
                        @endif
                        <a href="#" class="detail-link" data-bs-toggle="modal" data-bs-target="#modal-edit-booking">Modifica data/orario</a>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="detail-label">Durata</div>
                        <div class="detail-value">
                            @if($product?->duration_minutes)
                                {{ $product->duration_minutes }} min
                            @else
                                —
                            @endif
                        </div>
                        <div class="detail-label mt-spacing-l">Canale di vendita</div>
                        <div class="detail-value">{{ $order->partner?->domain_name ?? $order->partner?->partner_name ?? '—' }}</div>
                    </div>
                </div>

                <hr class="order-divider" />

                <div class="order-detail-tickets-wrap">
                    <h6 class="tickets-title">Biglietti</h6>
                    <table class="order-detail-tickets">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th class="text-end">Q.tà</th>
                                <th class="text-end">Prezzo</th>
                                <th class="text-end">Subtotale</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->orderProducts as $op)
                                @if($op->items->isNotEmpty())
                                    @foreach($op->items as $item)
                                        <tr>
                                            <td><strong>{{ $item->variant?->label ?? $op->product?->label ?? '—' }}</strong></td>
                                            <td class="text-end">{{ $item->quantity }}</td>
                                            <td class="text-end">{{ number_format($item->unit_price, 2, ',', '.') }} €</td>
                                            <td class="text-end">{{ number_format($item->subtotal, 2, ',', '.') }} €</td>
                                        </tr>
                                    @endforeach
                                @else
                                    @php($unit = $op->quantity > 0 ? $op->total / $op->quantity : $op->total)
                                    <tr>
                                        <td><strong>{{ $op->product?->label ?? '—' }}</strong></td>
                                        <td class="text-end">{{ $op->quantity }}</td>
                                        <td class="text-end">{{ number_format($unit, 2, ',', '.') }} €</td>
                                        <td class="text-end">{{ number_format($op->total, 2, ',', '.') }} €</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="order-detail-totals">
                    <div class="totals-row">
                        <span>Subtotale</span>
                        <span>{{ number_format($order->amount, 2, ',', '.') }} €</span>
                    </div>
                    <div class="totals-section-label">DI CUI</div>
                    <div class="totals-row totals-sub">
                        <span>Commissioni di pagamento ({{ rtrim(rtrim(number_format($commissionPaymentRate, 2, ',', ''), '0'), ',') }}%)</span>
                        <span>{{ number_format($commissionPaymentAmount, 2, ',', '.') }} €</span>
                    </div>
                    <div class="totals-row totals-sub">
                        <span>Commissioni di servizio ({{ rtrim(rtrim(number_format($commissionServiceRate, 2, ',', ''), '0'), ',') }}%)</span>
                        <span>{{ number_format($commissionServiceAmount, 2, ',', '.') }} €</span>
                    </div>
                    <div class="totals-row totals-final">
                        <span>Totale ordine</span>
                        <span>{{ number_format($order->amount, 2, ',', '.') }} €</span>
                    </div>
                </div>
            </x-card>
        </div>

        <div class="col-12 col-lg-4 d-flex flex-column gap-3">
            <x-card class="position-relative" title="Note">
                <div class="button-card-absolute">
                    <x-button
                        label="Modifica note"
                        emphasis="Low"
                        size="Small"
                        status="Secondary"
                        trailing="fa-pen"
                        :dataset="['bs-toggle' => 'modal', 'bs-target' => '#modal-edit-notes']"
                    />
                </div>
                <div class="notes-block">
                    <div class="detail-label"><i class="fa-regular fa-user"></i> Note cliente</div>
                    <div class="detail-value notes-content">{{ $order->customer_note ?: '—' }}</div>
                </div>
                <div class="notes-block mt-spacing-l">
                    <div class="detail-label"><i class="fa-regular fa-lock"></i> Note interne</div>
                    <div class="detail-value notes-content">{{ $order->internal_note ?: '—' }}</div>
                </div>

            </x-card>

            <x-card class="position-relative" title="Dettagli cliente">
                <div class="button-card-absolute">
                    <x-button
                        label="Modifica cliente"
                        emphasis="Low"
                        size="Small"
                        status="Secondary"
                        trailing="fa-arrow-right"
                        :dataset="['bs-toggle' => 'modal', 'bs-target' => '#modal-edit-customer']"
                    />
                </div>

                <div class="customer-block">
                    <div class="detail-label">Nome</div>
                    <div class="detail-value"><i class="fa-regular fa-user"></i> {{ $order->customer->full_name }}</div>
                    <a href="{{ route('customers.show', $order->customer->id) }}" class="detail-link">Apri scheda cliente</a>
                </div>
                <div class="customer-block mt-spacing-l">
                    <div class="detail-label">Email</div>
                    <div class="detail-value"><i class="fa-regular fa-envelope"></i> {{ $order->customer->email ?: '—' }}</div>
                </div>
                <div class="customer-block mt-spacing-l">
                    <div class="detail-label">Telefono</div>
                    <div class="detail-value"><i class="fa-regular fa-phone"></i> {{ $order->customer->phone ? trim(($order->customer->prefix_phone ?? '') . ' ' . $order->customer->phone) : '—' }}</div>
                </div>
                <div class="customer-block mt-spacing-l">
                    <div class="detail-label">Indirizzo</div>
                    <div class="detail-value"><i class="fa-regular fa-location-dot"></i> {{ $order->customer->address ? $order->customer->full_address : '—' }}</div>
                </div>
                <div class="customer-block mt-spacing-l">
                    <div class="detail-label">Paese</div>
                    <div class="detail-value"><i class="fa-regular fa-globe"></i> {{ $order->customer->country?->name ?? '—' }}</div>
                </div>
                <div class="customer-block mt-spacing-l">
                    <div class="detail-label">Codice fiscale</div>
                    <div class="detail-value"><i class="fa-regular fa-id-card"></i> {{ $order->customer->fiscal_code ?: '—' }}</div>
                </div>
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
                <a href="{{ route('orders.index') }}" class="text-decoration-none flex-grow-1">
                    <x-button label="Torna agli ordini" status="Neutral" emphasis="Medium" class="w-100" />
                </a>
                <x-button id="btn-refund" label="Rimborsa ordine" status="Error" emphasis="MediumLow" />
            </div>
        </div>
    </div>

    @if(in_array(auth()->user()->role, ['god', 'admin']))
        <div class="row g-3 mt-spacing-xl">
            <div class="col-12">
                @include('backoffice.orders._activity_log', ['logs' => $orderLogs])
            </div>
        </div>
    @endif

    {{-- MODALI --}}
    @include('backoffice.orders._modal-edit-booking', ['order' => $order])
    @include('backoffice.orders._modal-edit-notes', ['order' => $order])
    @include('backoffice.orders._modal-edit-customer', ['order' => $order])
@endsection

@push('scripts')
    <script>
        window.orderRoutes = {
            updateCustomerStatus: @json(route('orders.updateCustomerStatus', $model)),
            updateNotes:          @json(route('orders.updateNotes', $model)),
            updateCustomer:       @json(route('orders.updateCustomer', $model)),
            updateBooking:        @json(route('orders.updateBooking', $model)),
            sendEmail:            @json(route('orders.sendEmail', $model)),
            receipt:              @json(route('orders.receipt', $model)),
            refund:               @json(route('orders.refund', $model)),
            ticketsBatchStatus:   @json(route('tickets.batchStatus')),
            availabilityDays:     @json(route('orders.availabilityDays', $model)),
            availabilitySlots:    @json(route('orders.availabilitySlots', $model)),
        };
        window.orderBooking = {
            currentDate: @json($firstOp?->booking_date ? \Carbon\Carbon::parse($firstOp->booking_date)->format('Y-m-d') : null),
            currentTime: @json($firstOp?->booking_time ? substr($firstOp->booking_time, 0, 5) : null),
        };
    </script>
    <script type="module" src="{{ asset('backoffice/js/orders.js') }}?v={{ filemtime(public_path('backoffice/js/orders.js')) }}"></script>
@endpush
