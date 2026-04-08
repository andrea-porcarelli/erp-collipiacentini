@props(['model'])

<div class="tab-pane fade" id="special-schedule-panel" role="tabpanel" aria-labelledby="special-schedule-tab">
    <div class="row">
        <div class="col-12">
            <x-card title="Date e orari speciali" sub_title="Configura orari e disponibilità per date specifiche, sovrascrivendo il template settimanale">
                <div class="row g-4">
                    {{-- Colonna sinistra: mini-calendario --}}
                    <div class="col-12 col-lg-3">
                        <x-card>
                            <div id="special-schedule-calendar"></div>
                        </x-card>
                    </div>

                    {{-- Colonna destra: dettaglio data selezionata --}}
                    <div class="col-12 col-lg-9" id="special-schedule-detail" style="display:none">
                        <h3 id="special-schedule-date-title" class="mb-spacing-m" style="font-size:18px;font-weight:600"></h3>
                        <div class="d-flex align-items-center gap-2 mb-spacing-l flex-wrap">
                            <x-button label="Aggiungi orario" emphasis="High" trailing="fa-plus" class="btn-add-special-slot" />
                            <x-button label="Ripristina default" emphasis="Low" status="Primary" trailing="fa-arrow-rotate-right" class="btn-reset-special-date" />
                            <x-button label="Vedi prenotazioni" emphasis="Low" status="Primary" trailing="fa-arrow-right"  />
                        </div>
                        <div id="special-slots-list">
                            <p class="text-secondary small" id="special-slots-empty">Nessun orario speciale per questa data. Usa il template settimanale.</p>
                        </div>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</div>

{{-- Modal: aggiungi orario speciale --}}
{{-- (già definito in show.blade.php come #modal-add-special-slot) --}}

{{-- Modal: aggiungi variante allo slot speciale --}}
<div class="modal fade" id="modal-add-special-variant" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content modal-miticko">
            <div class="modal-header">
                <h1 class="modal-title">Aggiungi variante</h1>
                <button type="button" class="close" data-bs-dismiss="modal"><span class="fa-regular fa-times"></span></button>
            </div>
            <div class="modal-body w-100">
                <input type="hidden" id="ssv-slot-id" value="">
                <div class="row g-3">
                    <div class="col-12 col-sm-5">
                        <x-input name="ssv_label" label="Nome variante" required
                                 placeholder="es. Intero, Ridotto, Gratuito..."
                                 message="Visualizzato dai clienti in fase di selezione"
                                 icon="fa-regular fa-circle-info" />
                    </div>
                    <div class="col-12 col-sm-5">
                        <x-input name="ssv_description" label="Descrizione breve"
                                 placeholder="es. Biglietto intero per adulti"
                                 message="Descrizione breve visualizzata in fase di selezione"
                                 icon="fa-regular fa-circle-info" />
                    </div>
                    <div class="col-12 col-sm-2">
                        <x-input name="ssv_max_quantity" type="number" label="Max consentiti" placeholder="∞" />
                    </div>
                    <div class="col-12"><hr class="my-1"/></div>
                    <div class="col-12">
                        <p class="small fw-semibold mb-2">Componenti IVA</p>
                    </div>
                    <div class="col-12" id="ssv-prices-list"></div>
                    <div class="col-12 mt-1">
                        <button type="button" id="btn-ssv-add-price-row" class="bt-miticko bt-m-text-only secondary small">
                            <i class="fa-regular fa-plus icon"></i> Aggiungi componente IVA
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <x-button size="Small" emphasis="Low" status="Primary" label="annulla" :dataset="['bs-dismiss' => 'modal']" />
                <x-button size="Small" emphasis="High" status="Primary" label="Crea variante" id="btn-ssv-create" />
            </div>
        </div>
    </div>
</div>

@once
<style>
    .cal-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 2px;
    }
    .cal-header-cell {
        text-align: center;
        font-size: 11px;
        font-weight: 600;
        color: var(--text-secondary, #666);
        padding: 4px 0;
    }
    .cal-cell {
        aspect-ratio: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        font-size: 13px;
        cursor: default;
        position: relative;
        user-select: none;
    }
    .cal-day {
        cursor: pointer;
        transition: background 0.15s;
    }
    .cal-day:hover {
        background: var(--bg-hover, #f5f5f5);
    }
    .cal-day--past {
        color: var(--text-disabled, #bbb);
        cursor: not-allowed;
        pointer-events: none;
    }
    .cal-day--today {
        border-radius: var(--border-radius-0, 0);
        background: var(--background-global-paper2, #FAFAFA);
        color: var(--brand-secondary-brand, #3948D3);
        text-align: center;

        /* Title */
        font-family: var(--typography-title-font, "DM Sans"), sans-serif;
        font-size: var(--size, 14px);
        font-style: normal;
        font-weight: var(--weight, 700);
        line-height: var(--line-height, 18px); /* 128.571% */
    }
    .cal-day--selected {
        background: var(--primary, #E87722) !important;
        color: #fff;
    }
    .cal-day--override::after {
        content: '';
        position: absolute;
        bottom: 3px;
        left: 50%;
        transform: translateX(-50%);
        width: 5px;
        height: 5px;
        background: var(--brand-secondary-brand, #3948D3);
        border-radius: 50%;
    }
    .cal-day--selected.cal-day--override::after {
        background: rgba(255,255,255,0.8);
    }
    .special-slot-item {
        border-bottom: 1px solid #f0f0f0;
    }
    .special-slot-header {
        display: flex;
        align-items: center;
        gap: 12px;
        cursor: default;
        justify-content: space-between;
    }
    .special-slot-header .special-slot-header-tools {
        display: flex;
        cursor: default;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
    }
    .special-slot-body {
        padding: 16px;
        background: #f9f9f9;
        border-radius: 0 0 6px 6px;
        margin-bottom: 4px;
    }
    /* Varianti nello slot speciale */
    .ss-variant-item {
        border: 1px solid #e8e8e8;
        border-radius: 8px;
        margin-bottom: 8px;
        background: #fff;
    }
    .ss-variant-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
    }
    .ss-variant-edit-panel {
        padding: 14px;
        border-top: 1px solid #e8e8e8;
        display: none;
    }
    .ss-edit-price-row {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
    }
</style>
@endonce

@push('scripts')
<script>
(function () {
    var PRODUCT_ID = window.PRODUCT_ID;
    var _calYear, _calMonth, _selectedDate = null, _overrideDates = new Set();
    var _today = new Date();
    _today.setHours(0, 0, 0, 0);

    var MONTH_NAMES = ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
    var DAY_NAMES_LONG = ['Domenica','Lunedì','Martedì','Mercoledì','Giovedì','Venerdì','Sabato'];
    var VAT_OPTIONS = [
        {v:'0',  l:'Esente'},
        {v:'4',  l:'4%'},
        {v:'5',  l:'5%'},
        {v:'10', l:'10%'},
        {v:'22', l:'22%'},
    ];
    var pad2 = function(n) { return String(n).padStart(2,'0'); };

    /* ── Calendario ──────────────────────────────────────── */
    function buildCalendar(year, month) {
        var firstDay = new Date(year, month, 1);
        var lastDay  = new Date(year, month + 1, 0);
        var startDow = firstDay.getDay();
        startDow = startDow === 0 ? 6 : startDow - 1;

        var headers = ['L','M','M','G','V','S','D'].map(function(d) {
            return '<div class="cal-header-cell">' + d + '</div>';
        }).join('');

        var cells = '';
        for (var i = 0; i < startDow; i++) cells += '<div class="cal-cell"></div>';
        for (var d = 1; d <= lastDay.getDate(); d++) {
            var date = new Date(year, month, d);
            var isoDate = year + '-' + pad2(month + 1) + '-' + pad2(d);
            var isPast = date.getTime() < _today.getTime();
            var cls = isPast ? 'cal-cell cal-day cal-day--past' : 'cal-cell cal-day';
            if (date.getTime() === _today.getTime()) cls += ' cal-day--today';
            if (isoDate === _selectedDate) cls += ' cal-day--selected';
            if (_overrideDates.has(isoDate)) cls += ' cal-day--override';
            cells += '<div class="' + cls + '" data-date="' + isoDate + '">' + d + '</div>';
        }

        return '<div class="d-flex align-items-center justify-content-between mb-3">' +
            '<button type="button" class="bt-miticko btn-cal-prev" data-mode="small"><i class="fa-regular fa-chevron-left icon"></i></button>' +
            '<span class="fw-medium">' + MONTH_NAMES[month] + ' ' + year + '</span>' +
            '<button type="button" class="bt-miticko btn-cal-next" data-mode="small"><i class="fa-regular fa-chevron-right icon"></i></button>' +
            '</div>' +
            '<div class="cal-grid">' + headers + cells + '</div>';
    }

    function renderCalendar() {
        document.getElementById('special-schedule-calendar').innerHTML = buildCalendar(_calYear, _calMonth);
    }

    function formatDateTitle(isoDate) {
        var parts = isoDate.split('-');
        var d = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
        return DAY_NAMES_LONG[d.getDay()] + ' ' + parseInt(parts[2]) + ' ' + MONTH_NAMES[d.getMonth()] + ' ' + parts[0];
    }

    /* ── VAT select helper ───────────────────────────────── */
    function vatSelect(name, selectedVal) {
        return '<select class="input-miticko" name="' + name + '">' +
            VAT_OPTIONS.map(function(o) {
                return '<option value="' + o.v + '"' + (String(selectedVal) === o.v ? ' selected' : '') + '>' + o.l + '</option>';
            }).join('') +
        '</select>';
    }

    function renderEditPriceRow(p) {
        var pid = p && p.id ? p.id : '';
        return '<div class="ss-edit-price-row" data-price-id="' + pid + '">' +
            '<div class="flex-grow-1"><div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting"><div class="text-field-container">' +
                '<input type="text" class="input-miticko ssv-price-label" value="' + escAttr(p && p.label ? p.label : '') + '" placeholder="es. Visita">' +
            '</div></div></div>' +
            '<div style="width:160px;flex-shrink:0"><div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting"><div class="text-field-container">' +
                '<input type="number" class="input-miticko ssv-price-value" value="' + (p && p.price ? p.price : '') + '" placeholder="0.00" step="0.01" min="0">' +
            '</div></div></div>' +
            '<div style="width:130px;flex-shrink:0"><div class="text-field" data-mode="textfieldSize-Medium"><div class="text-field-container">' +
                vatSelect('ssv_price_vat', p && p.vat_rate !== undefined ? p.vat_rate : 0) +
            '</div></div></div>' +
            '<div style="width:36px;flex-shrink:0">' +
                '<button type="button" class="bt-miticko outlined danger small btn-ssv-remove-price"><i class="fa-regular fa-trash-can icon"></i></button>' +
            '</div>' +
        '</div>';
    }

    function escHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
    function escAttr(s) { return String(s).replace(/"/g,'&quot;'); }

    /* ── Carica slots ────────────────────────────────────── */
    function loadSpecialSlots(isoDate) {
        var $list = $('#special-slots-list');
        $list.html('<p class="text-secondary small"><i class="fa-regular fa-spinner fa-spin me-1"></i>Caricamento...</p>');

        $(document).trigger('fetch', [{
            path: '/products/' + PRODUCT_ID + '/special-schedule/' + isoDate,
            method: 'get',
            then: function (res) {
                if (!res.html) {
                    $list.html('<p class="text-secondary small mb-0" id="special-slots-empty">Nessun orario speciale per questa data. Usa il template settimanale.</p>');
                } else {
                    $list.html(res.html);
                }
                if (res.is_override) { _overrideDates.add(isoDate); }
                else { _overrideDates.delete(isoDate); }
                renderCalendar();
            },
            catch: function () {
                $list.html('<p class="text-danger small mb-0">Errore nel caricamento degli orari.</p>');
            },
        }]);
    }

    /* ── Carica varianti di uno slot ─────────────────────── */
    function loadSlotVariants($body, slotId) {
        var $list = $body.find('.ssv-list');
        $list.html('<p class="text-secondary small ssv-empty">Caricamento...</p>');

        $(document).trigger('fetch', [{
            path: '/products/' + PRODUCT_ID + '/special-schedule/' + slotId + '/variants',
            method: 'get',
            then: function (res) {
                if (!res.html) {
                    $list.html('<p class="text-secondary small ssv-empty mb-0">Nessuna variante. Aggiungi la prima.</p>');
                } else {
                    $list.html(res.html);
                    initSsvSortable($list, slotId);
                }
                $body.data('loaded', 1);
            },
            catch: function () {
                $list.html('<p class="text-danger small mb-0">Errore nel caricamento delle varianti.</p>');
            },
        }]);
    }

    /* ── Sortable varianti slot ──────────────────────────── */
    function initSsvSortable($list, slotId) {
        if (!$list.length || typeof Sortable === 'undefined') return;
        Sortable.create($list[0], {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function () {
                var ids = [...$list[0].querySelectorAll('.ss-variant-item[data-variant-id]')]
                    .map(function (el) { return parseInt(el.dataset.variantId); });
                $(document).trigger('fetch', [{
                    path: '/products/' + PRODUCT_ID + '/special-schedule/' + slotId + '/variants/reorder',
                    method: 'post',
                    data: { ordered_ids: ids },
                    then: function (res) { toastr.success('Riordinamento effettuato con successo'); },
                    catch: function (err) { console.log(err); toastr.error('Errore durante il riordinamento'); },
                }]);
            },
        });
    }

    /* ── Override dates ──────────────────────────────────── */
    function loadOverrideDates() {
        $.ajax({
            url: '/products/' + PRODUCT_ID + '/special-schedule/dates',
            method: 'GET',
            success: function (res) {
                _overrideDates = new Set(res.dates || []);
                renderCalendar();
            },
        });
    }

    /* ═══ EVENT HANDLERS ════════════════════════════════════ */

    /* Navigazione mese */
    $(document).on('click', '.btn-cal-prev', function () {
        _calMonth--;
        if (_calMonth < 0) { _calMonth = 11; _calYear--; }
        renderCalendar();
    });
    $(document).on('click', '.btn-cal-next', function () {
        _calMonth++;
        if (_calMonth > 11) { _calMonth = 0; _calYear++; }
        renderCalendar();
    });

    /* Click su giorno */
    $(document).on('click', '.cal-day', function () {
        var isoDate = $(this).data('date');
        if (!isoDate || $(this).hasClass('cal-day--past')) return;
        _selectedDate = isoDate;
        renderCalendar();
        $('#special-schedule-detail').show();
        $('#special-schedule-date-title').text(formatDateTitle(isoDate));
        loadSpecialSlots(isoDate);
    });

    /* Aggiungi orario speciale */
    $(document).on('click', '.btn-add-special-slot', function () {
        if (!_selectedDate) return;
        $('#special-slot-hour').val('09');
        $('#special-slot-minute').val('00');
        $('#special-slot-availability').val('');
        $('#modal-add-special-slot').modal('show');
    });

    /* Confirm aggiungi slot */
    $(document).on('click', '#btn-confirm-add-special-slot', function () {
        if (!_selectedDate) return;
        var hour   = String($('#special-slot-hour').val()).padStart(2, '0');
        var minute = String($('#special-slot-minute').val()).padStart(2, '0');
        var $btn = $(this).prop('disabled', true);

        $(document).trigger('fetch', [{
            path: '/products/' + PRODUCT_ID + '/special-schedule',
            method: 'post',
            data: { date: _selectedDate, time: hour + ':' + minute },
            then: function () {
                _overrideDates.add(_selectedDate);
                renderCalendar();
                loadSpecialSlots(_selectedDate);
                $('#modal-add-special-slot').modal('hide');
                toastr.success('Orario aggiunto');
                $btn.prop('disabled', false);
            },
            catch: function (err) {
                toastr.error((err && err.responseJSON && err.responseJSON.message) || 'Errore durante il salvataggio');
                $btn.prop('disabled', false);
            },
        }]);
    });

    /* Ripristina default */
    $(document).on('click', '.btn-reset-special-date', function () {
        if (!_selectedDate) return;
        var date = _selectedDate;
        $(document).trigger('sweetConfirmTrigger', [{
            title: 'Ripristina default',
            text: 'Vuoi eliminare tutti gli orari speciali di questa data e ripristinare il template settimanale?',
            callback: function () {
                $(document).trigger('fetch', [{
                    path: '/products/' + PRODUCT_ID + '/special-schedule/' + date + '/reset',
                    method: 'delete',
                    then: function () {
                        $('#special-slots-list').html('<p class="text-secondary small mb-0" id="special-slots-empty">Nessun orario speciale per questa data. Usa il template settimanale.</p>');
                        _overrideDates.delete(date);
                        renderCalendar();
                        toastr.success('Data ripristinata al template settimanale');
                    },
                    catch: function () { toastr.error('Errore durante l\'operazione'); },
                }]);
            },
        }]);
    });

    /* Espandi/chiudi slot */
    $(document).on('click', '.btn-special-slot-toggle', function () {
        var $item = $(this).closest('.special-slot-item');
        var $body = $item.find('.special-slot-body');
        var $icon = $(this).find('i');
        var isOpen = !$body.hasClass('d-none');
        $body.toggleClass('d-none', isOpen);
        $icon.attr('class', isOpen ? 'fa-regular fa-chevron-down icon' : 'fa-regular fa-chevron-up icon');

        if (!isOpen && $body.data('loaded') == 0) {
            loadSlotVariants($body, $item.data('id'));
        }
    });

    /* Salva capienza slot */
    $(document).on('click', '.btn-special-slot-save', function () {
        var $item  = $(this).closest('.special-slot-item');
        var slotId = $item.data('id');
        var avail  = parseInt($item.find('.special-slot-avail-input').val());
        if (isNaN(avail) || avail < 0) { toastr.warning('Inserisci una capienza valida'); return; }
        var $btn = $(this).prop('disabled', true);

        $(document).trigger('fetch', [{
            path: '/products/' + PRODUCT_ID + '/special-schedule/' + slotId + '/availability',
            method: 'put',
            data: { availability: avail },
            then: function () {
                toastr.success('Capienza aggiornata');
                $btn.prop('disabled', false);
                var $badge = $item.find('.special-slot-header .badge');
                if ($badge.length) $badge.text('Cap. ' + avail);
                else $item.find('.special-slot-header .flex-grow-1').after('<span class="badge rounded-pill" style="background:var(--primary,#E87722);color:#fff;font-size:11px;padding:3px 8px">Cap. ' + avail + '</span>');
            },
            catch: function (err) {
                toastr.error((err && err.responseJSON && err.responseJSON.message) || 'Errore');
                $btn.prop('disabled', false);
            },
        }]);
    });

    /* Elimina slot */
    $(document).on('click', '.btn-special-slot-delete', function () {
        var $item  = $(this).closest('.special-slot-item');
        var slotId = $item.data('id');
        $(document).trigger('sweetConfirmTrigger', [{
            title: 'Elimina orario',
            text: 'Vuoi eliminare questo orario speciale?',
            callback: function () {
                $(document).trigger('fetch', [{
                    path: '/products/' + PRODUCT_ID + '/special-schedule/' + slotId,
                    method: 'delete',
                    then: function () {
                        $item.remove();
                        if ($('#special-slots-list .special-slot-item').length === 0) {
                            _overrideDates.delete(_selectedDate);
                            renderCalendar();
                            $('#special-slots-list').html('<p class="text-secondary small mb-0" id="special-slots-empty">Nessun orario speciale per questa data. Usa il template settimanale.</p>');
                        }
                        toastr.success('Orario eliminato');
                    },
                    catch: function () { toastr.error('Errore durante l\'eliminazione'); },
                }]);
            },
        }]);
    });

    /* ── Apri modale aggiungi variante ───────────────────── */
    $(document).on('click', '.btn-ssv-open-modal', function () {
        var slotId = $(this).closest('.special-slot-item').data('id');
        $('#ssv-slot-id').val(slotId);
        $('[name="ssv_label"]').val('');
        $('[name="ssv_description"]').val('');
        $('[name="ssv_max_quantity"]').val('');
        $('#ssv-prices-list').html('');
        $('#modal-add-special-variant').modal('show');
    });

    /* Aggiungi riga prezzo nel modale */
    $(document).on('click', '#btn-ssv-add-price-row', function () {
        $('#ssv-prices-list').append(buildModalPriceRow());
    });

    function buildModalPriceRow() {
        return '<div class="d-flex align-items-center gap-2 mb-2 ssv-modal-price-row">' +
            '<div class="flex-grow-1"><div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting"><div class="text-field-container">' +
                '<input type="text" class="input-miticko ssv-modal-price-label" placeholder="es. Visita">' +
            '</div></div></div>' +
            '<div style="width:160px;flex-shrink:0"><div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting"><div class="text-field-container">' +
                '<input type="number" class="input-miticko ssv-modal-price-value" placeholder="0.00" step="0.01" min="0">' +
            '</div></div></div>' +
            '<div style="width:130px;flex-shrink:0"><div class="text-field" data-mode="textfieldSize-Medium"><div class="text-field-container">' +
                vatSelect('ssv_modal_vat', 0) +
            '</div></div></div>' +
            '<div style="width:36px;flex-shrink:0">' +
                '<button type="button" class="bt-miticko outlined danger small btn-ssv-modal-remove-price"><i class="fa-regular fa-trash-can icon"></i></button>' +
            '</div>' +
        '</div>';
    }

    $(document).on('click', '.btn-ssv-modal-remove-price', function () {
        $(this).closest('.ssv-modal-price-row').remove();
    });

    /* Crea variante */
    $(document).on('click', '#btn-ssv-create', function () {
        var slotId = $('#ssv-slot-id').val();
        var prices = [];
        $('#ssv-prices-list .ssv-modal-price-row').each(function () {
            prices.push({
                label:    $(this).find('.ssv-modal-price-label').val(),
                price:    parseFloat($(this).find('.ssv-modal-price-value').val()) || 0,
                vat_rate: parseFloat($(this).find('[name="ssv_modal_vat"]').val()) || 0,
            });
        });
        if (!$('[name="ssv_label"]').val()) { toastr.warning('Inserisci il nome della variante'); return; }
        if (prices.length === 0) { toastr.warning('Aggiungi almeno un componente IVA'); return; }

        var $btn = $(this).prop('disabled', true);

        $(document).trigger('fetch', [{
            path: '/products/' + PRODUCT_ID + '/special-schedule/' + slotId + '/variants',
            method: 'post',
            data: {
                label:        $('[name="ssv_label"]').val(),
                description:  $('[name="ssv_description"]').val(),
                max_quantity: $('[name="ssv_max_quantity"]').val() || null,
                prices:       prices,
            },
            then: function (res) {
                var $item = $('#special-slots-list .special-slot-item[data-id="' + slotId + '"]');
                var $list = $item.find('.ssv-list');
                $list.find('.ssv-empty').remove();
                $list.append(res.html);
                initSsvSortable($list, slotId);
                $('#modal-add-special-variant').modal('hide');
                toastr.success('Variante aggiunta');
                $btn.prop('disabled', false);
            },
            catch: function (err) {
                toastr.error((err && err.responseJSON && err.responseJSON.message) || 'Errore durante il salvataggio');
                $btn.prop('disabled', false);
            },
        }]);
    });

    /* ── Toggle edit panel variante ──────────────────────── */
    $(document).on('click', '.btn-ssv-toggle', function () {
        var $v    = $(this).closest('.ss-variant-item');
        var $panel = $v.find('.ss-variant-edit-panel');
        var $icon  = $(this).find('i');
        var isOpen = $panel.is(':visible');
        $panel.toggle(!isOpen);
        $icon.attr('class', isOpen ? 'fa-regular fa-chevron-down icon' : 'fa-regular fa-chevron-up icon');
    });

    /* Annulla edit variante */
    $(document).on('click', '.btn-ssv-cancel', function () {
        var $v = $(this).closest('.ss-variant-item');
        $v.find('.ss-variant-edit-panel').hide();
        $v.find('.btn-ssv-toggle i').attr('class', 'fa-regular fa-chevron-down icon');
    });

    /* Aggiungi riga prezzo in edit panel */
    $(document).on('click', '.btn-ssv-add-price', function () {
        $(this).closest('.ss-variant-edit-panel').find('.ssv-edit-prices').append(renderEditPriceRow({}));
    });

    /* Rimuovi riga prezzo in edit panel */
    $(document).on('click', '.btn-ssv-remove-price', function () {
        $(this).closest('.ss-edit-price-row').remove();
    });

    /* Salva variante */
    $(document).on('click', '.btn-ssv-save', function () {
        var $v      = $(this).closest('.ss-variant-item');
        var $slot   = $v.closest('.special-slot-item');
        var slotId  = $slot.data('id');
        var varId   = $v.data('variant-id');
        var prices  = [];

        $v.find('.ssv-edit-prices .ss-edit-price-row').each(function () {
            var pid = $(this).data('price-id');
            prices.push({
                id:       pid || null,
                label:    $(this).find('.ssv-price-label').val(),
                price:    parseFloat($(this).find('.ssv-price-value').val()) || 0,
                vat_rate: parseFloat($(this).find('[name="ssv_price_vat"]').val()) || 0,
            });
        });

        if (!$v.find('.ssv-edit-label').val()) { toastr.warning('Inserisci il nome della variante'); return; }
        if (prices.length === 0) { toastr.warning('Aggiungi almeno un componente IVA'); return; }

        var $btn = $(this).prop('disabled', true);

        $(document).trigger('fetch', [{
            path: '/products/' + PRODUCT_ID + '/special-schedule/' + slotId + '/variants/' + varId,
            method: 'put',
            data: {
                label:        $v.find('.ssv-edit-label').val(),
                description:  $v.find('.ssv-edit-description').val(),
                max_quantity: $v.find('.ssv-edit-max').val() || null,
                prices:       prices,
            },
            then: function (res) {
                var $list = $v.closest('.ssv-list');
                $v.replaceWith(res.html);
                initSsvSortable($list, slotId);
                toastr.success('Variante aggiornata');
                $btn.prop('disabled', false);
            },
            catch: function (err) {
                toastr.error((err && err.responseJSON && err.responseJSON.message) || 'Errore');
                $btn.prop('disabled', false);
            },
        }]);
    });

    /* Elimina variante */
    $(document).on('click', '.btn-ssv-delete', function () {
        var $v     = $(this).closest('.ss-variant-item');
        var $slot  = $v.closest('.special-slot-item');
        var slotId = $slot.data('id');
        var varId  = $v.data('variant-id');

        $(document).trigger('sweetConfirmTrigger', [{
            title: 'Elimina variante',
            text: 'Vuoi eliminare questa variante?',
            callback: function () {
                $(document).trigger('fetch', [{
                    path: '/products/' + PRODUCT_ID + '/special-schedule/' + slotId + '/variants/' + varId,
                    method: 'delete',
                    then: function () {
                        $v.remove();
                        if ($slot.find('.ss-variant-item').length === 0) {
                            $slot.find('.ssv-list').html('<p class="text-secondary small ssv-empty mb-0">Nessuna variante. Aggiungi la prima.</p>');
                        }
                        toastr.success('Variante eliminata');
                    },
                    catch: function () { toastr.error('Errore durante l\'eliminazione'); },
                }]);
            },
        }]);
    });

    /* ── Init ────────────────────────────────────────────── */
    (function initSpecialScheduleCalendar() {
        var now = new Date();
        _calYear  = now.getFullYear();
        _calMonth = now.getMonth();
        renderCalendar();
        loadOverrideDates();
    })();
})();
</script>
@endpush
