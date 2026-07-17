@extends('backoffice.layout', ['title' => 'Calendario', 'active' => $active])

@section('main-content')
    <x-header-page title="Calendario" />

    <div class="calendar-page"
         data-week-start="{{ $weekStart }}"
         data-today="{{ $today }}"
         @if($selectedPartner) data-partner-id="{{ $selectedPartner->id }}" @endif
    >
        @if($canPickPartner)
            <div class="calendar-partner-picker mb-spacing-l">
                @if($partners->isEmpty())
                    <p class="text-muted mb-0">Nessun partner accessibile al tuo account.</p>
                @else
                    <x-select
                        label="Partner"
                        name="calendar_partner_id"
                        class="js-calendar-partner-select"
                        :value="$selectedPartner?->id"
                        :options="$partners->map(fn($p) => ['id' => $p->id, 'label' => $p->partner_name])->all()"
                    />
                @endif
            </div>
        @endif

        <div class="calendar-toolbar d-flex flex-wrap align-items-center justify-content-between mb-spacing-l">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="calendar-week-selector">
                    <button type="button" class="calendar-week-nav" data-direction="prev" aria-label="Settimana precedente">
                        <i class="fa fa-chevron-left"></i>
                    </button>
                    <div class="calendar-week-label">
                        <small class="d-block text-muted">SETTIMANA</small>
                        <strong class="js-week-label">—</strong>
                    </div>
                    <button type="button" class="calendar-week-nav" data-direction="next" aria-label="Settimana successiva">
                        <i class="fa fa-chevron-right"></i>
                    </button>
                </div>
                <button type="button" class="calendar-today-btn js-calendar-today">Oggi</button>
            </div>
            <div class="calendar-groupby segmented-control" role="tablist">
                <button type="button" class="segmented-option active" data-group="product">
                    <i class="fa fa-border-all"></i>
                    Prodotto
                </button>
                <button type="button" class="segmented-option" data-group="slot">
                    <i class="fa fa-clock"></i>
                    Fascia oraria
                </button>
            </div>
        </div>

        <div class="calendar-days-strip mb-spacing-l" id="calendar-days-strip">
            @for($i = 0; $i < 7; $i++)
                <button type="button" class="calendar-day-btn" data-index="{{ $i }}">
                    <small class="calendar-day-name">—</small>
                    <span class="calendar-day-num">–</span>
                    <span class="calendar-day-dot"></span>
                </button>
            @endfor
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div id="calendar-day-content" class="calendar-day-content">
                    @if(! $selectedPartner)
                        <div class="calendar-empty-state">
                            <i class="fa fa-calendar-days"></i>
                            <p class="mb-0">Seleziona un partner per visualizzare il calendario.</p>
                        </div>
                    @else
                        <div class="calendar-loading">Caricamento…</div>
                    @endif
                </div>
            </div>
            <div class="col-lg-4">
                <x-card title="Arrivi previsti">
                    <div class="calendar-arrivals" id="calendar-arrivals" data-empty>
                        <div class="calendar-arrivals-filters">
                            <div class="row g-2">
                                <div class="col-6">
                                    <x-select
                                        label="Stato ordine"
                                        name="calendar_arrivals_order_status"
                                        class="js-arrivals-filter"
                                        data-filter="order_status"
                                        value="all"
                                        :options="array_merge([['id' => 'all', 'label' => 'Tutti']], collect($orderStatuses)->map(fn($label, $value) => ['id' => $value, 'label' => $label])->values()->all())"
                                    />
                                </div>
                                <div class="col-6">
                                    <x-select
                                        label="Check-in"
                                        name="calendar_arrivals_check_in"
                                        class="js-arrivals-filter"
                                        data-filter="check_in"
                                        value="all"
                                        :options="[
                                            ['id' => 'all', 'label' => 'Tutti'],
                                            ['id' => 'none', 'label' => 'Nessun arrivo'],
                                            ['id' => 'partial', 'label' => 'Parziale'],
                                            ['id' => 'complete', 'label' => 'Completo'],
                                        ]"
                                    />
                                </div>
                                <div class="col-12">
                                    <x-input
                                        name="calendar_arrivals_search"
                                        class="js-arrivals-search"
                                        leading="fa-magnifying-glass"
                                        placeholder="Cerca tra gli arrivi previsti"
                                    />
                                </div>
                            </div>
                        </div>
                        <div class="calendar-arrivals-body" id="calendar-arrivals-body">
                            <div class="calendar-arrivals-placeholder">
                                <i class="fa fa-list"></i>
                                <p class="mb-0">Seleziona una fascia oraria per visualizzare gli ordini.</p>
                            </div>
                        </div>
                    </div>
                </x-card>
            </div>
        </div>
    </div>

    @include('backoffice.calendar._modal_checkin')

    <script>
        window.calendarConfig = {
            urls: {
                week:        @json(route('calendar.week')),
                day:         @json(route('calendar.day')),
                slotOrders:  @json(route('calendar.slot.orders')),
                orderDetail: @json(url('/calendar/orders')),
                batchStatus: @json(route('calendar.participants.batchStatus')),
            },
            weekStart: @json($weekStart),
            today:     @json($today),
            partnerId: @json($selectedPartner?->id),
            canPickPartner: @json($canPickPartner),
        };
    </script>
    <script type="module" src="{{ asset('backoffice/js/calendar.js') }}"></script>
@endsection
