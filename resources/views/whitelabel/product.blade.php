@extends('whitelabel.layout', compact('company'))

@section('content')
    <div class="container mt-5">
        <div class="row w-100">
            <aside class="col-12 col-sm-3 sidebar">
                <x-whitelabel.sidebar :company="$company" :date="false" />
            </aside>
            <div class="col-12 col-sm-9">
                <x-card :pre_title="$product->partner?->company?->company_name" :title="$product->meta_title" class="product-card" h1="true" leading="fa-shield-check">
                    {!! $product->product_tags !!}
                    <div class="button-progress">
                        <button id="btn-date" data-mode="small secondary" type="button" class="bt-miticko btn-date bt-m-outlined">
                            <i class="fa-regular fa-calendar icon"></i>Data
                        </button>
                        <div class="progress-connector"></div>
                        <button id="btn-time" data-mode="small disabled" type="button" class="bt-miticko btn-time bt-m-default" disabled>
                            <i class="fa-regular fa-clock-three icon"></i>Orario
                        </button>
                        <div class="progress-connector"></div>
                        <button id="btn-visitors" data-mode="small disabled" type="button" class="bt-miticko btn-visitors bt-m-default" disabled>
                            <i class="fa-regular fa-user icon"></i>Visitatori
                        </button>
                    </div>
                    <div id="calendar-container" class="w-100 mt-3">
                        <div id="calendar" class="w-100"></div>
                    </div>
                    <div id="time-slots" class="time-slots-grid w-100 mt-3" style="display: none;"></div>
                    <div id="ticket-selection" class="w-100 mt-3" style="display: none;">
                        <div id="ticket-quantity"></div>
                    </div>
                </x-card>
                <x-card title="Galleria" class="product-card" h1="true" leading="fa-shield-check">
                    @livewire('product-gallery', ['product' => $product])
                </x-card>
                <x-card title="Descrizione" class="product-card" h1="true" leading="fa-shield-check">
                    {!! $product->description !!}
                </x-card>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const availableDates = @json($product->getSharedAvailabilities()->pluck('date')->unique()->values()->toArray());
    const productId = @json($product->id);

    // Variabili per memorizzare la selezione
    let selectedDate = null;
    let selectedTime = null;
    let selectedSlotType = null;
    let selectedSlotId = null;
    let selectedVariants = [];   // [{id, label, price}] dal server per lo slot scelto
    let variantQuantities = {};  // {variantId: quantity}
    let maxAvailability = 0;
</script>
<style>
    .flatpickr-calendar.inline {
        width: 100% !important;
        max-width: none !important;
        box-shadow: none !important;
    }

    .flatpickr-calendar .flatpickr-months, .flatpickr-wrapper {
        width: 100% !important;
    }

    /* Disabilita i dropdown di mese e anno */
    .flatpickr-calendar .flatpickr-monthDropdown-months,
    .flatpickr-calendar .numInputWrapper {
        pointer-events: none !important;
        cursor: default !important;
    }

    /* Nascondi i dropdown select */
    .flatpickr-calendar select.flatpickr-monthDropdown-months,
    .flatpickr-calendar .numInput.cur-year {
        pointer-events: none !important;
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        appearance: none !important;
        cursor: default !important;
    }

    /* Stile per mese e anno */
    .flatpickr-calendar .flatpickr-current-month .flatpickr-monthDropdown-months,
    .flatpickr-calendar .flatpickr-current-month .numInputWrapper .numInput.cur-year {
        color: var(--text-main, #0D0D0D) !important;
        text-align: center !important;
        font-family: var(--font-font-1, "DM Sans"), sans-serif !important;
        font-size: var(--typography-title-size-small, 14px) !important;
        font-style: normal !important;
        font-weight: var(--typography-title-weight-small, 700) !important;
        line-height: var(--typography-title-lineheight-small, 18px) !important;
    }

    .flatpickr-calendar .flatpickr-current-month .flatpickr-monthDropdown-months {
        background: transparent !important;
        border: none !important;
    }

    .flatpickr-calendar .flatpickr-current-month .numInputWrapper {
        background: transparent !important;
        width: 5ch !important;
    }

    .flatpickr-calendar .flatpickr-innerContainer {
        width: 100% !important;
        display: block !important;
    }

    .flatpickr-calendar .flatpickr-rContainer {
        width: 100% !important;
    }

    .flatpickr-calendar .flatpickr-days {
        width: 100% !important;
    }

    .flatpickr-calendar .dayContainer {
        width: 100% !important;
        min-width: 100% !important;
        max-width: none !important;
        display: flex !important;
        flex-wrap: wrap !important;
        justify-content: space-between !important;
    }

    .flatpickr-calendar .flatpickr-day {
        flex: 0 0 calc(14.28% - 2px) !important;
        max-width: calc(14.28% - 2px) !important;
        height: 50px !important;
        line-height: 50px !important;
        margin: 1px !important;
    }

    .flatpickr-calendar .flatpickr-weekdays {
        width: 100% !important;
    }

    .flatpickr-calendar .flatpickr-weekday {
        flex: 0 0 14.28% !important;
        max-width: 14.28% !important;
    }

    /* Stile per date non disponibili */
    .flatpickr-calendar .flatpickr-day.flatpickr-disabled,
    .flatpickr-calendar .flatpickr-day.flatpickr-disabled:hover,
    .flatpickr-calendar .flatpickr-day.prevMonthDay,
    .flatpickr-calendar .flatpickr-day.nextMonthDay {
        color: var(--text-disabled, #999) !important;
        text-align: center !important;
        font-family: var(--font-font-2, "DM Sans"), sans-serif !important;
        font-size: var(--typography-body-size-medium, 16px) !important;
        font-style: normal !important;
        font-weight: var(--typography-body-weight-medium, 400) !important;
        text-decoration-line: line-through !important;
        cursor: not-allowed !important;
    }

    /* Stile per il giorno corrente */
    .flatpickr-calendar .flatpickr-day.today {
        position: relative !important;
    }

    .flatpickr-calendar .flatpickr-day.today::after {
        content: '' !important;
        position: absolute !important;
        bottom: 4px !important;
        left: 50% !important;
        transform: translateX(-50%) !important;
        width: 8px !important;
        height: 8px !important;
        background-color: var(--secondary-brand, #2A3493);
        border-radius: 50% !important;
    }

    /* Linea di progresso tra i bottoni */
    .button-progress {
        display: grid;
        grid-template-columns: 1fr auto 1fr auto 1fr;
        align-items: center;
        padding: 10px 0;
        gap: 0;
    }

    .button-progress > button,
    .button-progress > a {
        justify-self: center;
    }

    .button-progress .progress-connector {
        width: 8px;
        height: 1px;
        background-color: var(--neutral-grey-10, #E6E6E6);
        justify-self: stretch;
    }

    /* Stili per gli orari */
    #time-slots,
    .time-slots-grid {
        display: grid !important;
        grid-template-columns: repeat(4, 1fr) !important;
        gap: var(--spacing-xs, 4px); !important;
        padding: 16px 0 !important;
        width: 100% !important;
        box-sizing: border-box !important;
    }

    @media (min-width: 768px) {
        #time-slots,
        .time-slots-grid {
            gap: var(--spacing-xs, 4px); !important;
        }
    }

    .time-slot {
        padding: 12px 8px;
        border: 1px solid var(--neutral-grey-10, #E6E6E6);
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
        border-radius: var(--border-radius-s, 8px);
        background: var(--secondary-brandlight, #EAEBF4);
    }

    @media (min-width: 768px) {
        .time-slot {
            padding: 12px 16px;
        }
    }

    .time-slot:hover {
        border-color: var(--primary-main, #007bff);
        background-color: var(--primary-light, #e7f3ff);
    }

    .time-slot.selected {
        border-color: var(--primary-main, #007bff);
        background-color: var(--primary-main, #007bff);
        color: white;
    }
    .flatpickr-day.selected {
        border-radius: var(--border-radius-xs, 4px);
        border: 1px solid var(--secondary-brand, #2A3493);
        background: var(--secondary-brand, #2A3493);
        font-family: var(--font-font-1, "DM Sans"), sans-serif !important;
        font-size: var(--typography-title-size-small, 14px);
        font-style: normal;
        font-weight: var(--typography-title-weight-small, 600);
        line-height: var(--typography-title-lineheight-small, 18px);
    }

    .time-slot .time {
        font-weight: 700;
        font-size: 14px;
        margin-bottom: 4px;
    }

    .time-slot .availability {
        color: var(--text-disabled, #999);
        /* bodySmall */
        font-family: var(--font-font-2, "DM Sans"), sans-serif;
        font-size: var(--typography-body-size-small, 14px);
        font-style: normal;
        font-weight: var(--typography-body-weight-small, 300);
        line-height: var(--typography-body-lineheight-small, 16px);
    }

    @media (min-width: 768px) {
        .time-slot .time {
            font-size: 16px;
        }
    }

    .time-slot.selected .availability {
        color: white;
    }

    .time-slot.disabled {
        border-radius: var(--border-radius-s, 8px);
        background: var(--page-paper, #FFF);
        border-color: var(--page-paper, #FFF);
        color: var(--text-disabled, #999);
    }


    .time-slot.disabled .availability {
        color: var(--text-disabled, #999);
    }


    #ticket-quantity {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .quantity-control {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .quantity-control .label {
        font-weight: 700;
        font-size: var(--typography-body-size-medium, 16px);
        color: var(--text-main, #0D0D0D);
    }

    .quantity-control .controls {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .quantity-control .btn-quantity {
        width: 40px;
        height: 40px;
        border-radius: var(--border-radius, 8px);
        border: var(--button-color-primary-light-borderwidth, 0) solid var(--light-bordercolor, rgba(255, 255, 255, 0.00));
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        color: #2A3493 !important   ;
    }

    .quantity-control .btn-quantity:hover:not(:disabled) {
        border-color: var(--secondary-brand, #2A3493);
        background-color: var(--secondary-brand, #2A3493);
        color: white;
    }

    .quantity-control .btn-quantity:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }

    .quantity-control .quantity-value {
        min-width: 40px;
        text-align: center;
        font-weight: 700;
        font-size:  var(--typography-title-size-large, 28px);
    }

    .quantity-control .description {
        color: var(--text-secondary, #666);
        /* bodySmall */
        font-family: var(--font-font-2, "DM Sans"), sans-serif;
        font-size: var(--typography-body-size-small, 14px);
        font-style: normal;
        font-weight: var(--typography-body-weight-small, 300);
        line-height: var(--typography-body-lineheight-small, 16px);
    }

    .quantity-control .price {
        color: var(--text-secondary, #666);
        /* bodySmall */
        font-family: var(--font-font-2, "DM Sans"), sans-serif;
        font-size: var(--typography-body-size-small, 14px);
        font-style: normal;
        font-weight: var(--typography-body-weight-small, 300);
        line-height: var(--typography-body-lineheight-small, 16px);
    }

    .total-info {
        margin-top: 16px;
        padding: 16px;
        background-color: var(--neutral-grey-2, #F5F5F5);
        border-radius: var(--border-radius-m, 8px);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .total-info .label {
        font-weight: var(--typography-title-weight-large, 600);
        line-height: var(--typography-title-lineheight-large, 34px);
        font-size: var(--typography-title-size-large, 18px);
        color: var(--text-secondary, #666);
    }

    .total-info .amount {
        font-weight: var(--typography-title-weight-large, 600);
        line-height: var(--typography-title-lineheight-large, 34px);
        font-size: var(--typography-title-size-large, 18px);
        color: var(--text-secondary, #666);
    }

</style>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configurazione italiana
        const Italian = {
            weekdays: {
                shorthand: ["Dom", "Lun", "Mar", "Mer", "Gio", "Ven", "Sab"],
                longhand: ["Domenica", "Lunedì", "Martedì", "Mercoledì", "Giovedì", "Venerdì", "Sabato"]
            },
            months: {
                shorthand: ["Gen", "Feb", "Mar", "Apr", "Mag", "Giu", "Lug", "Ago", "Set", "Ott", "Nov", "Dic"],
                longhand: ["Gennaio", "Febbraio", "Marzo", "Aprile", "Maggio", "Giugno", "Luglio", "Agosto", "Settembre", "Ottobre", "Novembre", "Dicembre"]
            },
            firstDayOfWeek: 1,
            ordinal: function() {
                return "°";
            },
            rangeSeparator: " al ",
            weekAbbreviation: "Se",
            scrollTitle: "Scrolla per aumentare",
            toggleTitle: "Clicca per cambiare"
        };

        const fp = flatpickr("#calendar", {
            inline: true,
            locale: Italian,
            dateFormat: "Y-m-d",
            minDate: "today",
            static: true,
            disableMobile: true,
            enable: availableDates.length > 0 ? availableDates : [],
            onChange: function(selectedDates, dateStr, instance) {
                console.log('Data selezionata:', dateStr);
                loadAvailableTimes(dateStr);
            },
            onReady: function(selectedDates, dateStr, instance) {
                // Rimuovi gli stili inline che limitano la larghezza
                const calendar = instance.calendarContainer;
                calendar.style.width = '';
                const dayContainer = calendar.querySelector('.dayContainer');
                if (dayContainer) {
                    dayContainer.style.width = '';
                    dayContainer.style.minWidth = '';
                    dayContainer.style.maxWidth = '';
                }
            }
        });

        // Aggiungi evento click al bottone Data per tornare al calendario
        const btnDateElement = document.getElementById('btn-date');
        if (btnDateElement) {
            btnDateElement.addEventListener('click', function() {
                const calendarContainer = document.getElementById('calendar-container');
                const timeSlotsContainer = document.getElementById('time-slots');
                const ticketSelectionContainer = document.getElementById('ticket-selection');
                const btnTime = document.getElementById('btn-time');
                const btnVisitors = document.getElementById('btn-visitors');

                // Mostra il calendario e nascondi orari e selezione biglietti
                if (calendarContainer) calendarContainer.style.display = 'block';
                if (timeSlotsContainer) timeSlotsContainer.style.setProperty('display', 'none', 'important');
                if (ticketSelectionContainer) ticketSelectionContainer.style.display = 'none';

                // Ripristina il bottone Data allo stato iniziale
                this.classList.remove('bt-m-default');
                this.classList.add('bt-m-outlined');
                this.setAttribute('data-mode', 'small secondary');
                this.innerHTML = '<i class="fa-regular fa-calendar icon"></i>Data';

                // Ripristina il bottone Orario a disabled
                if (btnTime) {
                    btnTime.classList.remove('bt-m-outlined');
                    btnTime.classList.add('bt-m-default');
                    btnTime.setAttribute('data-mode', 'small disabled');
                    btnTime.setAttribute('disabled', 'disabled');
                    btnTime.innerHTML = '<i class="fa-regular fa-clock-three icon"></i>Orario';
                }

                // Ripristina il bottone Visitatori a disabled
                if (btnVisitors) {
                    btnVisitors.classList.remove('bt-m-outlined');
                    btnVisitors.classList.add('bt-m-default');
                    btnVisitors.setAttribute('data-mode', 'small disabled');
                    btnVisitors.setAttribute('disabled', 'disabled');
                }

                // Reset delle variabili
                selectedDate = null;
                selectedTime = null;
                selectedSlotType = null;
                selectedSlotId = null;
                selectedVariants = [];
                variantQuantities = {};
                maxAvailability = 0;
            });
        }

        // Aggiungi evento click al bottone Orario per tornare alla selezione orari
        const btnTimeElement = document.getElementById('btn-time');
        if (btnTimeElement) {
            btnTimeElement.addEventListener('click', function() {
                // Solo se non è disabled
                if (!this.hasAttribute('disabled')) {
                    const timeSlotsContainer = document.getElementById('time-slots');
                    const ticketSelectionContainer = document.getElementById('ticket-selection');
                    const btnVisitors = document.getElementById('btn-visitors');

                    // Mostra gli orari e nascondi la selezione biglietti
                    if (timeSlotsContainer) timeSlotsContainer.style.display = 'grid';
                    if (ticketSelectionContainer) ticketSelectionContainer.style.display = 'none';

                    // Ripristina il bottone Orario a outlined
                    this.classList.remove('bt-m-default');
                    this.classList.add('bt-m-outlined');
                    this.innerHTML = '<i class="fa-regular fa-clock-three icon"></i>Orario';

                    // Ripristina il bottone Visitatori a disabled
                    if (btnVisitors) {
                        btnVisitors.classList.remove('bt-m-outlined');
                        btnVisitors.classList.add('bt-m-default');
                        btnVisitors.setAttribute('data-mode', 'small disabled');
                        btnVisitors.setAttribute('disabled', 'disabled');
                    }

                    // Reset delle quantità biglietti
                    variantQuantities = {};
                    selectedVariants.forEach(v => { variantQuantities[v.id] = 0; });
                }
            });
        }

        // Funzione per formattare la data in italiano (es: "14 Gen")
        function formatDateItalian(dateStr) {
            const months = ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'];
            const date = new Date(dateStr);
            const day = date.getDate();
            const month = months[date.getMonth()];
            return `${day} ${month}`;
        }

        // Funzione per caricare gli orari disponibili
        function loadAvailableTimes(date) {
            const timeSlotsContainer = document.getElementById('time-slots');
            const calendarContainer = document.getElementById('calendar-container');
            const btnDate = document.getElementById('btn-date');
            const btnTime = document.getElementById('btn-time');

            if (!timeSlotsContainer || !calendarContainer || !btnDate || !btnTime) {
                console.error('Elementi non trovati nel DOM');
                return;
            }

            // Salva la data selezionata
            selectedDate = date;

            // Nascondi il calendario e mostra la sezione orari
            calendarContainer.style.display = 'none';
            timeSlotsContainer.style.display = 'grid';
            timeSlotsContainer.innerHTML = '<p>Caricamento orari...</p>';

            // Aggiorna il bottone Data
            btnDate.classList.remove('bt-m-outlined');
            btnDate.classList.add('bt-m-default');
            btnDate.setAttribute('data-mode', 'small secondary');
            // Aggiorna il testo del bottone con la data selezionata
            const dateText = formatDateItalian(date);
            const btnDateIcon = btnDate.querySelector('i');
            btnDate.innerHTML = '';
            if (btnDateIcon) {
                btnDate.appendChild(btnDateIcon.cloneNode(true));
                btnDate.appendChild(document.createTextNode(' '));
            }
            btnDate.appendChild(document.createTextNode(dateText));

            // Chiamata AJAX per recuperare gli orari
            fetch(`/shop/product/${productId}/available-times?date=${date}`)
                .then(response => response.json())
                .then(data => {
                    if (data.times && data.times.length > 0) {
                        timeSlotsContainer.innerHTML = '';
                        data.times.forEach(timeSlot => {
                            const slotElement = document.createElement('div');
                            slotElement.className = 'time-slot';

                            if (!timeSlot.is_available) {
                                slotElement.classList.add('disabled');
                            }

                            const availLabel = timeSlot.availability === null ? '∞' : timeSlot.availability;
                            slotElement.innerHTML = `
                                <div class="time">${timeSlot.time}</div>
                                <div class="availability"><span class="far fa-user"></span> ${availLabel}</div>
                            `;

                            if (timeSlot.is_available) {
                                slotElement.addEventListener('click', function() {
                                    document.querySelectorAll('.time-slot:not(.disabled)').forEach(slot => {
                                        slot.classList.remove('selected');
                                    });
                                    this.classList.add('selected');

                                    selectedTime     = timeSlot.time;
                                    selectedSlotType = timeSlot.slot_type;
                                    selectedSlotId   = timeSlot.slot_id;
                                    selectedVariants = timeSlot.variants;
                                    maxAvailability  = timeSlot.availability === null ? Infinity : timeSlot.availability;

                                    showTicketSelection();
                                });
                            }

                            timeSlotsContainer.appendChild(slotElement);
                        });

                        // Aggiorna il bottone Orario: da disabled a secondary outlined
                        btnTime.classList.remove('bt-m-default');
                        btnTime.classList.add('bt-m-outlined');
                        btnTime.setAttribute('data-mode', 'small secondary');
                        btnTime.removeAttribute('disabled');
                    } else {
                        timeSlotsContainer.innerHTML = '<p>Nessun orario disponibile per questa data.</p>';
                    }
                })
                .catch(error => {
                    console.error('Errore nel caricamento degli orari:', error);
                    timeSlotsContainer.innerHTML = '<p>Errore nel caricamento degli orari.</p>';
                });
        }

        // Funzione per mostrare la selezione biglietti
        function showTicketSelection() {
            const timeSlotsContainer = document.getElementById('time-slots');
            const ticketSelectionContainer = document.getElementById('ticket-selection');
            const btnTime = document.getElementById('btn-time');
            const btnVisitors = document.getElementById('btn-visitors');

            // Nascondi gli orari e mostra la selezione biglietti
            if (timeSlotsContainer) timeSlotsContainer.style.setProperty('display', 'none', 'important');
            if (ticketSelectionContainer) ticketSelectionContainer.style.display = 'block';

            // Aggiorna il bottone Orario
            if (btnTime) {
                btnTime.classList.remove('bt-m-outlined');
                btnTime.classList.add('bt-m-default');
                btnTime.setAttribute('data-mode', 'small secondary');
                // Aggiorna il testo del bottone con l'orario selezionato
                btnTime.innerHTML = `<i class="fa-regular fa-clock-three icon"></i> ${selectedTime}`;
            }

            // Abilita il bottone Visitatori
            if (btnVisitors) {
                btnVisitors.classList.remove('bt-m-default');
                btnVisitors.classList.add('bt-m-outlined');
                btnVisitors.setAttribute('data-mode', 'small secondary');
                btnVisitors.removeAttribute('disabled');
            }


            // Crea i controlli per ogni variante restituita dall'API
            const quantityContainer = document.getElementById('ticket-quantity');

            variantQuantities = {};
            selectedVariants.forEach(v => { variantQuantities[v.id] = 0; });

            let ticketTypesHTML = selectedVariants.map(v => `
                <div class="quantity-control" data-variant-id="${v.id}">
                    <div>
                        <div class="label">${v.label}</div>
                    </div>
                    <div class="controls">
                        <div>
                            <div class="price">€ ${parseFloat(v.price).toFixed(2)}</div>
                        </div>
                        <button class="btn-quantity btn-decrease-variant" type="button" data-variant-id="${v.id}" disabled>
                            <i class="fa-solid fa-minus"></i>
                        </button>
                        <span class="quantity-value" id="qty-variant-${v.id}">0</span>
                        <button class="btn-quantity btn-increase-variant" type="button" data-variant-id="${v.id}">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                </div>
            `).join('');

            ticketTypesHTML += `
                <div class="total-info">
                    <div class="label">Totale</div>
                    <div class="amount" id="total-amount">€ 0.00</div>
                </div>
                <button id="btn-purchase" data-mode="small disabled" type="button" class="bt-miticko btn-purchase bt-m-default" disabled>
                    Acquista
                </button>
            `;

            quantityContainer.innerHTML = ticketTypesHTML;

            function getTotalTickets() {
                return Object.values(variantQuantities).reduce((s, q) => s + q, 0);
            }

            function calculateTotal() {
                const total = selectedVariants.reduce((s, v) => s + (variantQuantities[v.id] * parseFloat(v.price)), 0);
                document.getElementById('total-amount').textContent = `€ ${total.toFixed(2)}`;
            }

            function updateAllButtons() {
                const total = getTotalTickets();
                document.querySelectorAll('.btn-decrease-variant').forEach(btn => {
                    btn.disabled = variantQuantities[btn.dataset.variantId] <= 0;
                });
                document.querySelectorAll('.btn-increase-variant').forEach(btn => {
                    btn.disabled = total >= maxAvailability;
                });
                const btnPurchase = document.getElementById('btn-purchase');
                const isDisabled = total <= 0;
                btnPurchase.disabled = isDisabled;
                btnPurchase.setAttribute('data-mode', isDisabled ? 'small disabled' : 'small');
            }

            document.querySelectorAll('.btn-decrease-variant').forEach(btn => {
                btn.addEventListener('click', function() {
                    const vid = this.dataset.variantId;
                    if (variantQuantities[vid] > 0) {
                        variantQuantities[vid]--;
                        document.getElementById(`qty-variant-${vid}`).textContent = variantQuantities[vid];
                        calculateTotal();
                        updateAllButtons();
                    }
                });
            });

            document.querySelectorAll('.btn-increase-variant').forEach(btn => {
                btn.addEventListener('click', function() {
                    const vid = this.dataset.variantId;
                    if (getTotalTickets() < maxAvailability) {
                        variantQuantities[vid]++;
                        document.getElementById(`qty-variant-${vid}`).textContent = variantQuantities[vid];
                        calculateTotal();
                        updateAllButtons();
                    }
                });
            });

            // Event listener per il bottone Acquista
            document.getElementById('btn-purchase').addEventListener('click', function() {
                if (this.disabled) return;

                const btn = this;
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Caricamento...';

                fetch('/shop/cart/add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        date: selectedDate,
                        time: selectedTime,
                        items: Object.entries(variantQuantities)
                            .filter(([, qty]) => qty > 0)
                            .map(([vid, qty]) => ({ variant_id: parseInt(vid), quantity: qty }))
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.redirect_url) {
                        window.location.href = data.redirect_url;
                    } else {
                        alert(data.error || 'Errore durante l\'aggiunta al carrello');
                        btn.disabled = false;
                        btn.innerHTML = 'Acquista';
                    }
                })
                .catch(error => {
                    console.error('Errore:', error);
                    alert('Errore durante l\'aggiunta al carrello');
                    btn.disabled = false;
                    btn.innerHTML = 'Acquista';
                });
            });

            updateAllButtons();
        }
    });

    // Gallery thumbnails scroll management
    document.addEventListener('livewire:initialized', () => {
        Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
            succeed(({ snapshot, effect }) => {
                // Dopo ogni aggiornamento Livewire, scorri al thumbnail attivo
                setTimeout(() => {
                    const activeThumbnail = document.querySelector('.thumbnail-item.active');
                    const thumbnailsContainer = document.querySelector('.gallery-thumbnails');

                    if (activeThumbnail && thumbnailsContainer) {
                        const scrollLeft = activeThumbnail.offsetLeft - (thumbnailsContainer.offsetWidth / 2) + (activeThumbnail.offsetWidth / 2);
                        thumbnailsContainer.scrollTo({
                            left: scrollLeft,
                            behavior: 'smooth'
                        });
                    }
                }, 100);
            });
        });
    });
</script>
@endpush
