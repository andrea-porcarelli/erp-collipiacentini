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
    $(document).on('click', '#modal-edit-booking .btn-cancel, #modal-edit-notes .btn-cancel, #modal-edit-customer .btn-cancel', function () {
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
        submitForm('#form-edit-booking', routes.updateBooking, 'PUT', 'modal-edit-booking', 'Data/orario aggiornati');
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

    // --- Check-in visitatori (card su /orders/{id}) ---
    const $checkinCard = $('.order-checkin-content');
    if ($checkinCard.length) {
        const statusClasses = ['ts-status-booked', 'ts-status-checked_in', 'ts-status-no_show', 'ts-status-cancelled'];

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

    // Rimborso (con conferma SweetAlert)
    $('#btn-refund').on('click', function () {
        App.sweetConfirm(
            'Confermi il rimborso totale di questo ordine? L\'azione non è reversibile.',
            () => {
                App.ajax({ path: routes.refund, method: 'POST' }).then((res) => {
                    toastr.success(res?.response || 'Rimborso eseguito');
                    setTimeout(() => location.reload(), 1000);
                }).catch(showError);
            }
        );
    });
});
