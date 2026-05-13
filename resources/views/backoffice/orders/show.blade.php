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
        <div class="col-12 col-md-6 col-xl">
            <x-card>
                <div class="stat-label">STATO CLIENTE</div>
                <x-select
                    name="customer_status"
                    :value="$order->customer_status?->value ?? 'booked'"
                    :options="collect(\App\Enums\CustomerStatus::statuses())->map(fn($l, $v) => ['id' => $v, 'label' => $l])->values()->all()"
                    trailing="fa-chevron-down"
                    trailing_style="solid"
                />
            </x-card>
        </div>
    </div>

    {{-- BODY GRID --}}
    <div class="row g-3">
        <div class="col-12 col-lg-8">
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
            @php($participants = $order->participants()->with('orderProductItem.variant')->orderBy('id')->get())
            <x-card class="position-relative" title="Partecipanti" :sub_title="$participants->count() . ' biglietti'">
                @if($participants->isEmpty())
                    <div class="text-secondary">Nessun partecipante per questo ordine.</div>
                @else
                    <div class="participants-list d-flex flex-column gap-2">
                        @foreach($participants as $i => $participant)
                            <div class="participant-row d-flex justify-content-between align-items-center py-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-secondary small" style="min-width:24px">#{{ $i + 1 }}</span>
                                    <span><i class="fa-regular fa-ticket me-1 text-secondary"></i>{{ $participant->orderProductItem?->variant?->label ?? '—' }}</span>
                                </div>
                                @include('backoffice.components.label', [
                                    'status' => 'success',
                                    'label'  => $participant->status_label,
                                ])
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>

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

            <div class="d-flex gap-2 order-show-footer-actions">
                <a href="{{ route('orders.index') }}" class="text-decoration-none flex-grow-1">
                    <x-button label="Torna agli ordini" status="Neutral" emphasis="Medium" class="w-100" />
                </a>
                <x-button id="btn-refund" label="Rimborsa ordine" status="Error" emphasis="MediumLow" />
            </div>
        </div>
    </div>

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
        };
    </script>
    <script type="module" src="{{ asset('backoffice/js/orders.js') }}"></script>
@endpush
