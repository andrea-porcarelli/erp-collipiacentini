import App from "./app.js";

// ---------------------------------------------------------------------------
// Form save configs
// ---------------------------------------------------------------------------
const formConfigs = {
    'form-partner-status': {
        endpoint: () => `/backoffice/partners/${window.PARTNER_ID}`,
        method: 'put',
        section: 'status',
        successMessage: 'Stato partner aggiornato con successo',
        validate: () => ({}),
    },
    'form-partner-info': {
        endpoint: () => `/backoffice/partners/${window.PARTNER_ID}`,
        method: 'put',
        section: 'info',
        successMessage: 'Informazioni partner aggiornate con successo',
        validate: (data) => {
            const errors = {};
            if (!data.partner_name || data.partner_name.trim() === '') {
                errors.partner_name = ['Il nome partner è obbligatorio'];
            }
            if (!data.partner_code || data.partner_code.trim() === '') {
                errors.partner_code = ['Il codice partner è obbligatorio'];
            }
            return errors;
        },
    },
    'form-partner-commissions': {
        endpoint: () => `/backoffice/partners/${window.PARTNER_ID}`,
        method: 'put',
        section: 'commissions',
        successMessage: 'Commissioni aggiornate con successo',
        validate: () => ({}),
    },
};

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------
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
        btn.attr('data-mode', 'medium disabled');
    }).catch((errors) => {
        setLoading(btn, false);
        if (errors.responseJSON) {
            App.renderErrors(errors, form);
        } else {
            toastr.error('Errore durante il salvataggio. Riprova.');
        }
    });
};

// ---------------------------------------------------------------------------
// Users
// ---------------------------------------------------------------------------
const escapeHtml = (text) =>
    String(text ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

const roleOptions = [
    { value: 'partner', label: 'Collaboratore' },
    { value: 'admin',   label: 'Proprietario' },
];

const renderRoleSelect = (selected) => {
    const options = roleOptions.map(o =>
        `<option value="${o.value}" ${o.value === selected ? 'selected' : ''}>${o.label}</option>`
    ).join('');
    return `<select class="input-miticko" name="role">${options}</select>`;
};

const showFormErrors = ($container, errors) => {
    for (const [field, messages] of Object.entries(errors)) {
        const message = Array.isArray(messages) ? messages[0] : messages;
        $container.find(`[name="${field}"]`)
            .closest('.text-field-container')
            .addClass('is-invalid')
            .parent()
            .find('.supporting-text')
            .show()
            .addClass('danger')
            .html(message);
    }
    setTimeout(() => {
        $container.find('.supporting-text').removeClass('danger').html('');
        $container.find('.text-field-container').removeClass('is-invalid');
    }, 8000);
};

const renderUserRow = (user) => {
    const name  = escapeHtml(user.name);
    const email = escapeHtml(user.email);
    const role  = user.role ?? 'partner';
    return `
    <div class="user-item py-1" data-id="${user.id}">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-sm-3">
                <div class="text-field" data-mode="medium">
                    <div class="text-field-container">
                        <input class="input-miticko" name="name" value="${name}">
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-3">
                <div class="text-field" data-mode="medium">
                    <div class="text-field-container">
                        <input class="input-miticko" name="email" type="email" value="${email}">
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-3">
                <div class="text-field" data-mode="medium">
                    <div class="text-field-container">
                        <input class="input-miticko" name="password" type="password" placeholder="Nuova password...">
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-2">
                <div class="text-field" data-mode="medium">
                    <div class="text-field-container">
                        ${renderRoleSelect(role)}
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-1 d-flex gap-1 align-items-end justify-content-end pb-1">
                <button type="button" data-mode="medium primary" class="bt-miticko btn-user-delete bt-m-text-only"><i class="fa-regular fa-trash icon"></i></button>
            </div>
        </div>
    </div>`;
};

const updateSaveUsersButton = () => {
    const hasDirty = $('#users-list .user-item[data-dirty]').length > 0;
    if (hasDirty) {
        $('.btn-save-users').attr('data-mode', 'medium primary').removeClass('btn-m-primary').addClass('btn-m-default');
    } else {
        $('.btn-save-users').attr('data-mode', 'medium disabled').removeClass('btn-m-default').addClass('btn-m-primary');
    }
};

const addUser = () => {
    const $form = $('#form-user-new');
    const data = {
        name:     $form.find('input[name="name"]').val(),
        email:    $form.find('input[name="email"]').val(),
        password: $form.find('input[name="password"]').val(),
        role:     $form.find('select[name="role"]').val(),
    };

    if (!data.name || !data.email || !data.password || !data.role) {
        toastr.warning('Nome, email, ruolo e password sono obbligatori');
        return;
    }

    App.ajax({ path: `/backoffice/partners/${window.PARTNER_ID}/users`, method: 'post', data })
        .then((user) => {
            $('#users-empty').remove();
            $('#users-list').append(renderUserRow(user));
            $form[0].reset();
            toastr.success('Account aggiunto con successo');
        })
        .catch((err) => {
            if (err.responseJSON?.errors) {
                showFormErrors($form, err.responseJSON.errors);
            } else {
                toastr.error('Errore durante la creazione dell\'account');
            }
        });
};

const initUsers = () => {
    $(document).on('input', '.user-item input', function () {
        $(this).closest('.user-item').attr('data-dirty', '1');
        updateSaveUsersButton();
    });

    $(document).on('click', '.btn-save-users', function () {
        const $btn = $(this);
        const $dirtyItems = $('#users-list .user-item[data-dirty]');
        if ($dirtyItems.length === 0) return;

        setLoading($btn, true);
        let pending = $dirtyItems.length;
        let hasError = false;

        $dirtyItems.each(function () {
            const $item = $(this);
            const id = $item.data('id');
            const data = {
                name:  $item.find('input[name="name"]').val(),
                email: $item.find('input[name="email"]').val(),
            };
            const password = $item.find('input[name="password"]').val();
            if (password) data.password = password;

            App.ajax({ path: `/backoffice/partners/${window.PARTNER_ID}/users/${id}`, method: 'put', data })
                .then(() => {
                    $item.removeAttr('data-dirty');
                    $item.find('input[name="password"]').val('');
                })
                .catch(() => { hasError = true; })
                .finally(() => {
                    pending--;
                    if (pending === 0) {
                        setLoading($btn, false);
                        if (hasError) {
                            toastr.error('Alcuni account non sono stati salvati. Riprova.');
                        } else {
                            toastr.success('Account aggiornati con successo');
                        }
                        updateSaveUsersButton();
                    }
                });
        });
    });

    $(document).on('click', '.btn-user-delete', function () {
        const $item = $(this).closest('.user-item');
        const id = $item.data('id');
        App.sweetConfirm('Vuoi eliminare questo account?', () => {
            App.ajax({ path: `/backoffice/partners/${window.PARTNER_ID}/users/${id}`, method: 'delete' })
                .then(() => {
                    $item.remove();
                    if ($('#users-list .user-item').length === 0) {
                        $('#users-list').prepend('<p class="text-secondary small mb-0" id="users-empty">Nessun account associato.</p>');
                    }
                    toastr.success('Account eliminato');
                })
                .catch(() => toastr.error('Errore durante l\'eliminazione'));
        }, null, 'Elimina account');
    });

    $(document).on('click', '.btn-user-add', addUser);
};

// ---------------------------------------------------------------------------
// Elimina partner
// ---------------------------------------------------------------------------
const initDeletePartner = () => {
    $(document).on('click', '.btn-delete-partner', function () {
        App.sweetConfirm(
            'Vuoi eliminare definitivamente questo partner? L\'operazione è irreversibile.',
            () => {
                App.ajax({
                    path: `/backoffice/partners/${window.PARTNER_ID}`,
                    method: 'delete',
                }).then((res) => {
                    toastr.success('Partner eliminato con successo');
                    setTimeout(() => {
                        window.location.href = res.redirect ?? '/backoffice/partners';
                    }, 800);
                }).catch((err) => {
                    toastr.error(err?.responseJSON?.message || 'Errore durante l\'eliminazione');
                });
            },
            null,
            'Elimina partner'
        );
    });
};

// ---------------------------------------------------------------------------
// Init
// ---------------------------------------------------------------------------
const init = () => {
    $(document).on('input change', 'form :input:not([disabled])', function () {
        $(this).closest('.card-miticko').find('.btn-save-card').attr('data-mode', 'medium primary');
    });

    $(document).on('click', '.btn-save-card', function () {
        const card = $(this).closest('.card-miticko');
        const form = card.find('form');
        if (!form.length) return;
        saveForm(form.attr('id'), $(this));
    });

    initUsers();
    initDeletePartner();
};

$(function () {
    init();
});
