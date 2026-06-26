import App from "./app.js";

const showError = (errors) => {
    if (errors?.responseJSON?.message) {
        toastr.error(errors.responseJSON.message);
    } else if (errors?.responseJSON?.response) {
        toastr.error(errors.responseJSON.response);
    } else {
        toastr.error('Errore durante l\'operazione. Riprova.');
    }
};

const submitForm = (formSelector, endpoint, method, modalId, successMessage) => {
    const { data } = App.serialize(formSelector);
    App.ajax({ path: endpoint, method, data }).then(() => {
        toastr.success(successMessage);
        if (modalId) {
            const el = document.getElementById(modalId);
            if (el) bootstrap.Modal.getOrCreateInstance(el).hide();
        }
        setTimeout(() => location.reload(), 800);
    }).catch((errors) => {
        showError(errors);
    });
};

$(function () {
    const routes = window.orderRoutes || {};

    // Modali: chiudi su click "Annulla" (il componente x-modal non aggiunge data-bs-dismiss)
    $(document).on('click', '#modal-edit-booking .btn-cancel, #modal-edit-notes .btn-cancel, #modal-edit-customer .btn-cancel, #modal-cancel-order .btn-cancel', function () {
        const modalEl = this.closest('.modal');
        if (modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).hide();
    });

    // Stato cliente: change → PUT customer-status
    $('select[name="customer_status"]').on('change', function () {
        App.ajax({
            path: routes.updateCustomerStatus,
            method: 'PUT',
            data: { customer_status: this.value },
        }).then(() => {
            toastr.success('Stato cliente aggiornato');
        }).catch(showError);
    });

    // Modali — pulsanti "Salva" (.btn-success interno al modal-footer)
    $(document).on('click', '#modal-edit-booking .btn-success', function () {
        const $btn = $(this);
        if ($btn.prop('disabled')) return;
        const { data } = App.serialize('#form-edit-booking');
        if (!data.booking_date) { toastr.error('Seleziona una data'); return; }
        if (!data.booking_time) { toastr.error('Seleziona un orario'); return; }
        $btn.prop('disabled', true);
        $.ajax({
            url: routes.updateBooking,
            method: 'PUT',
            dataType: 'json',
            data,
        })
        .done(function () {
            toastr.success('Data/orario aggiornati');
            const modalEl = document.getElementById('modal-edit-booking');
            if (modalEl) {
                try { bootstrap.Modal.getOrCreateInstance(modalEl).hide(); } catch (_) {}
            }
            setTimeout(() => location.reload(), 800);
        })
        .fail(function (xhr) {
            $btn.prop('disabled', false);
            const msg = xhr?.responseJSON?.message || xhr?.responseJSON?.response || 'Errore durante l\'operazione. Riprova.';
            toastr.error(msg);
        });
    });
    $(document).on('click', '#modal-edit-notes .btn-success', function () {
        submitForm('#form-edit-notes', routes.updateNotes, 'PUT', 'modal-edit-notes', 'Note aggiornate');
    });
    $(document).on('click', '#modal-edit-customer .btn-success', function () {
        submitForm('#form-edit-customer', routes.updateCustomer, 'PUT', 'modal-edit-customer', 'Cliente aggiornato');
    });

    // Invia email ordine
    $('#btn-send-email').on('click', function () {
        const $btn = $(this);
        $btn.prop('disabled', true);
        App.ajax({ path: routes.sendEmail, method: 'POST' }).then((res) => {
            toastr.success(res?.response || 'Email inviata');
        }).catch(showError).finally(() => $btn.prop('disabled', false));
    });

    // Scarica ricevuta
    $('#btn-download-receipt').on('click', function () {
        window.location.href = routes.receipt;
    });

    // --- Modifica data/ora modale ---
    const bookingModalEl = document.getElementById('modal-edit-booking');
    if (bookingModalEl && window.flatpickr && routes.availabilityDays) {
        const initial = window.orderBooking || {};
        const state = {
            picker: null,
            availableDays: [],
            selectedDate: initial.currentDate || null,
            selectedTime: initial.currentTime || null,
            selectedSlotType: null,
            selectedSlotId: null,
            initialized: false,
        };

        const $dateInput = $('#booking-date-input');
        const $timeInput = $('#booking-time-input');
        const $slotTypeInput = $('#booking-slot-type-input');
        const $slotIdInput = $('#booking-slot-id-input');
        const $chipDate = $('[data-role="booking-chip-date"]');
        const $chipTime = $('[data-role="booking-chip-time"]');
        const $chipDateLabel = $('[data-role="chip-date-label"]');
        const $chipTimeLabel = $('[data-role="chip-time-label"]');
        const $stepCalendar = $('.booking-step-calendar');
        const $stepSlots = $('.booking-step-slots');
        const $slotsContainer = $('#booking-time-slots');

        const monthsShort = ['gen','feb','mar','apr','mag','giu','lug','ago','set','ott','nov','dic'];
        const formatDateChip = (d) => {
            const dt = new Date(d + 'T00:00:00');
            return `${dt.getDate()} ${monthsShort[dt.getMonth()]} ${String(dt.getFullYear()).slice(-2)}`;
        };

        const showStep = (step) => {
            $stepCalendar.toggleClass('d-none', step !== 'calendar');
            $stepSlots.toggleClass('d-none', step !== 'slots');
        };

        const updateChips = () => {
            if (state.selectedDate) {
                $chipDate.removeClass('booking-chip-empty');
                $chipDateLabel.text(formatDateChip(state.selectedDate));
            } else {
                $chipDate.addClass('booking-chip-empty');
                $chipDateLabel.text('Seleziona data');
            }
            if (state.selectedTime) {
                $chipTime.prop('disabled', false).removeClass('booking-chip-empty');
                $chipTimeLabel.text(state.selectedTime);
            } else {
                $chipTime.prop('disabled', !state.selectedDate).addClass('booking-chip-empty');
                $chipTimeLabel.text('Orario');
            }
        };

        const loadSlots = (date) => {
            $slotsContainer.html('<div class="booking-time-slots-loading">Caricamento orari...</div>');
            showStep('slots');

            $.ajax({
                url: routes.availabilitySlots,
                method: 'GET',
                dataType: 'json',
                data: { date },
            })
            .done((res) => {
                const times = res?.times || [];
                if (!times.length) {
                    $slotsContainer.html('<div class="booking-time-slots-empty">Nessun orario disponibile per questa data.</div>');
                    return;
                }
                $slotsContainer.empty();
                times.forEach((slot) => {
                    const isSelected = state.selectedTime === slot.time && state.selectedDate === date;
                    const availLabel = slot.availability === null ? '∞' : slot.availability;
                    const $el = $(`
                        <div class="booking-time-slot ${slot.is_available ? '' : 'disabled'} ${isSelected ? 'selected' : ''}">
                            <div class="booking-time-slot-time">${slot.time}</div>
                            <div class="booking-time-slot-avail"><i class="fa-regular fa-user"></i> ${availLabel}</div>
                        </div>
                    `);
                    if (slot.is_available) {
                        $el.on('click', function () {
                            $slotsContainer.find('.booking-time-slot').removeClass('selected');
                            $(this).addClass('selected');
                            state.selectedTime = slot.time;
                            state.selectedSlotType = slot.slot_type;
                            state.selectedSlotId = slot.slot_id;
                            $timeInput.val(slot.time);
                            $slotTypeInput.val(slot.slot_type || '');
                            $slotIdInput.val(slot.slot_id || '');
                            updateChips();
                        });
                    }
                    $slotsContainer.append($el);
                });
            })
            .fail((xhr) => {
                const msg = xhr?.responseJSON?.response || xhr?.responseJSON?.message || 'Errore nel caricamento degli orari';
                $slotsContainer.html(`<div class="booking-time-slots-empty">${msg}</div>`);
            });
        };

        const initPicker = (availableDays) => {
            if (state.picker) {
                state.picker.destroy();
            }
            const enable = availableDays.length ? availableDays : [];
            state.picker = flatpickr('#booking-calendar', {
                inline: true,
                locale: 'it',
                dateFormat: 'Y-m-d',
                minDate: 'today',
                disableMobile: true,
                monthSelectorType: 'static',
                enable,
                defaultDate: state.selectedDate || undefined,
                onChange: function (selectedDates, dateStr) {
                    if (!dateStr) return;
                    if (state.selectedDate !== dateStr) {
                        state.selectedTime = null;
                        state.selectedSlotType = null;
                        state.selectedSlotId = null;
                        $timeInput.val('');
                        $slotTypeInput.val('');
                        $slotIdInput.val('');
                    }
                    state.selectedDate = dateStr;
                    $dateInput.val(dateStr);
                    updateChips();
                    loadSlots(dateStr);
                },
                onReady: function (selectedDates, dateStr, instance) {
                    if (availableDays.length) {
                        const today = instance.formatDate(new Date(), 'Y-m-d');
                        const jumpTo = state.selectedDate && availableDays.includes(state.selectedDate)
                            ? state.selectedDate
                            : availableDays.filter((d) => d >= today).sort()[0];
                        if (jumpTo) instance.jumpToDate(jumpTo);
                    }
                },
            });
        };

        const ensureInitialized = () => {
            if (state.initialized) return;
            state.initialized = true;

            $.ajax({
                url: routes.availabilityDays,
                method: 'GET',
                dataType: 'json',
            })
            .done((res) => {
                state.availableDays = res?.days || [];
                initPicker(state.availableDays);
                if (state.selectedDate && state.availableDays.includes(state.selectedDate)) {
                    loadSlots(state.selectedDate);
                    showStep('calendar');
                }
                updateChips();
            })
            .fail(() => {
                toastr.error('Errore nel caricamento delle disponibilità');
            });
        };

        $(bookingModalEl).on('shown.bs.modal', ensureInitialized);

        $chipDate.on('click', function () {
            showStep('calendar');
        });
        $chipTime.on('click', function () {
            if ($(this).prop('disabled')) return;
            if (state.selectedDate) loadSlots(state.selectedDate);
        });

        updateChips();
    }

    // --- Check-in visitatori (card su /orders/{id}) ---
    const $checkinCard = $('.order-checkin-content');
    if ($checkinCard.length) {
        const statusClasses = ['ts-status-booked', 'ts-status-checked_in', 'ts-status-no_show', 'ts-status-refunded', 'ts-status-cancelled'];

        const updateSelectClass = ($sel, status) => {
            statusClasses.forEach((cls) => $sel.removeClass(cls));
            $sel.addClass('ts-status-' + status);
        };

        const updateCounter = () => {
            const $selects = $checkinCard.find('[data-role="card-status-select"]');
            const total = $selects.length;
            const checked = $selects.filter(function () { return $(this).val() === 'checked_in'; }).length;
            $checkinCard.find('[data-role="card-checkin-count"]').text(checked);
            $checkinCard.find('[data-role="card-checkin-total"]').text(total);
        };

        $checkinCard.on('change', '[data-role="card-status-select"]', function () {
            const $sel = $(this);
            updateSelectClass($sel, $sel.val());
            updateCounter();
        });

        $checkinCard.on('click', '[data-role="card-all-arrived"]', function () {
            $checkinCard.find('[data-role="card-status-select"]').each(function () {
                const $sel = $(this);
                $sel.val('checked_in');
                updateSelectClass($sel, 'checked_in');
            });
            updateCounter();
        });

        $checkinCard.on('click', '[data-role="card-save-changes"]', function () {
            const $btn = $(this);
            const $selects = $checkinCard.find('[data-role="card-status-select"]');
            const changes = [];
            $selects.each(function () {
                const $sel = $(this);
                const original = $sel.attr('data-original');
                const current = $sel.val();
                if (current !== original) {
                    const $row = $sel.closest('[data-participant-id]');
                    changes.push({ id: parseInt($row.attr('data-participant-id'), 10), status: current });
                }
            });

            if (!changes.length) {
                toastr.info('Nessuna modifica da salvare');
                return;
            }

            $btn.prop('disabled', true);
            $.ajax({
                url: routes.ticketsBatchStatus,
                method: 'PUT',
                dataType: 'json',
                data: { participants: changes },
            })
            .done(function () {
                $selects.each(function () { $(this).attr('data-original', $(this).val()); });
                toastr.success('Modifiche salvate');
            })
            .fail(function (xhr) {
                let msg = 'Errore durante il salvataggio';
                if (xhr && xhr.responseJSON) {
                    msg = xhr.responseJSON.response || xhr.responseJSON.message || msg;
                }
                toastr.error(msg);
            })
            .always(function () { $btn.prop('disabled', false); });
        });
    }

    // Annulla ordine (apre il modale con scelta rimborso sì/no)
    const cancelModalEl = document.getElementById('modal-cancel-order');
    $('#btn-cancel-order').on('click', function () {
        if (cancelModalEl) bootstrap.Modal.getOrCreateInstance(cancelModalEl).show();
    });

    $(document).on('click', '#modal-cancel-order .btn-success', function () {
        const $btn = $(this);
        if ($btn.prop('disabled')) return;
        const issueRefund = $('#form-cancel-order input[name="issue_refund"]:checked').val();
        if (typeof issueRefund === 'undefined') {
            toastr.error('Seleziona un\'opzione');
            return;
        }
        $btn.prop('disabled', true);
        App.ajax({
            path: routes.cancel,
            method: 'POST',
            data: { issue_refund: issueRefund },
        }).then((res) => {
            toastr.success(res?.response || 'Ordine annullato');
            if (cancelModalEl) {
                try { bootstrap.Modal.getOrCreateInstance(cancelModalEl).hide(); } catch (_) {}
            }
            setTimeout(() => location.reload(), 1000);
        }).catch((errors) => {
            $btn.prop('disabled', false);
            showError(errors);
        });
    });
});
