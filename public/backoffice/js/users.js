import App from "./app.js";

const formConfigs = {
    'form-user-info': {
        endpoint: () => `/users/${window.USER_ID}`,
        method: 'put',
        section: 'info',
        successMessage: 'Informazioni utente aggiornate con successo',
        validate: (data) => {
            const errors = {};
            if (!data.name || data.name.trim() === '') {
                errors.name = ['Il nome è obbligatorio'];
            }
            if (!data.email || data.email.trim() === '') {
                errors.email = ['L\'email è obbligatoria'];
            }
            return errors;
        },
    },
    'form-user-partner-role': {
        endpoint: () => `/users/${window.USER_ID}`,
        method: 'put',
        section: 'partner_role',
        successMessage: 'Partner e ruolo aggiornati con successo',
        validate: (data) => {
            const errors = {};
            if (!data.role) {
                errors.role = ['Il ruolo è obbligatorio'];
            }
            return errors;
        },
    },
    'form-user-password': {
        endpoint: () => `/users/${window.USER_ID}`,
        method: 'put',
        section: 'password',
        successMessage: 'Password aggiornata con successo',
        validate: (data) => {
            const errors = {};
            if (!data.password || data.password.length < 8) {
                errors.password = ['La password deve contenere almeno 8 caratteri'];
            }
            if (data.password !== data.password_confirmation) {
                errors.password_confirmation = ['Le password non coincidono'];
            }
            return errors;
        },
        onSuccess: (formId) => {
            $(`#${formId} input[name="password"]`).val('');
            $(`#${formId} input[name="password_confirmation"]`).val('');
        },
    },
};

const setLoading = (btn, loading) => {
    const icon = btn.find('i');
    if (loading) {
        btn.prop('disabled', true);
        icon.data('original-class', icon.attr('class'));
        icon.attr('class', 'fa-regular fa-spinner fa-spin icon');
    } else {
        btn.prop('disabled', false);
        const originalClass = icon.data('original-class');
        if (originalClass) icon.attr('class', originalClass);
    }
};

const showClientErrors = (formId, errors) => {
    for (const [field, messages] of Object.entries(errors)) {
        const message = Array.isArray(messages) ? messages[0] : messages;
        const input = $(`#${formId} [name="${field}"]`);
        input
            .closest('.text-field-container')
            .addClass('is-invalid')
            .parent()
            .find('.supporting-text')
            .show()
            .addClass('danger')
            .html(message);
    }
    setTimeout(() => {
        $(`#${formId} .supporting-text`).removeClass('danger').html('');
        $(`#${formId} .text-field-container`).removeClass('is-invalid');
    }, 8000);
};

const saveForm = (formId, btn) => {
    const config = formConfigs[formId];
    if (!config) return;

    const { data, form } = App.serialize(`#${formId}`);

    if (config.validate) {
        const errors = config.validate(data);
        if (Object.keys(errors).length > 0) {
            showClientErrors(formId, errors);
            return;
        }
    }

    setLoading(btn, true);

    App.ajax({
        path: config.endpoint(),
        method: config.method,
        data: { ...data, section: config.section },
    }).then(() => {
        toastr.success(config.successMessage);
        setLoading(btn, false);
        btn.attr('data-mode', 'buttonSize-Medium buttonEmphasis-Medium  buttonAppearance-Disabled');
        if (config.onSuccess) config.onSuccess(formId);
    }).catch((errors) => {
        setLoading(btn, false);
        if (errors.responseJSON) {
            App.renderErrors(errors, form);
        } else {
            toastr.error('Errore durante il salvataggio. Riprova.');
        }
    });
};

const init = () => {
    $(document).on('input change', 'form :input:not([disabled])', function () {
        $(this).closest('.card-miticko').find('.btn-save-card').attr('data-mode', 'buttonSize-Medium buttonEmphasis-High buttonAppearance-Primary');
    });

    $(document).on('click', '.btn-save-card', function () {
        const card = $(this).closest('.card-miticko');
        const form = card.find('form');
        if (!form.length) return;
        saveForm(form.attr('id'), $(this));
    });
};

$(function () {
    init();
});
