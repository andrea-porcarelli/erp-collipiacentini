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
