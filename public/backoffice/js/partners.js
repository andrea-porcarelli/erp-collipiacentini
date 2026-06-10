import App from "./app.js";
import {
    ClassicEditor, Essentials, Paragraph,
    Bold, Italic, Underline, Strikethrough, RemoveFormat,
    List, Link, Heading,
} from 'ckeditor5';

// ---------------------------------------------------------------------------
// Form save configs
// ---------------------------------------------------------------------------
const formConfigs = {
    'form-partner-info': {
        endpoint: () => `/partners/${window.PARTNER_ID}`,
        method: 'put',
        section: 'info',
        successMessage: 'Informazioni partner aggiornate con successo',
        validate: (data) => {
            const errors = {};
            if (!data.partner_name || data.partner_name.trim() === '') {
                errors.partner_name = ['Il nome partner è obbligatorio'];
            }
            const codeDisabled = $('#form-partner-info [name="partner_code"]').prop('disabled');
            if (!codeDisabled && (!data.partner_code || data.partner_code.trim() === '')) {
                errors.partner_code = ['Il codice partner è obbligatorio'];
            }
            return errors;
        },
    },
    'form-partner-sale': {
        endpoint: () => `/partners/${window.PARTNER_ID}`,
        method: 'put',
        section: 'sale',
        successMessage: 'Configurazione vendita aggiornata con successo',
        validate: () => ({}),
    },
    'form-partner-commissions': {
        endpoint: () => `/partners/${window.PARTNER_ID}`,
        method: 'put',
        section: 'commissions',
        successMessage: 'Commissioni aggiornate con successo',
        validate: () => ({}),
    },
    'form-partner-billing': {
        endpoint: () => `/partners/${window.PARTNER_ID}`,
        method: 'put',
        section: 'billing',
        successMessage: 'Dati di fatturazione aggiornati con successo',
        validate: () => ({}),
    },
    'form-partner-description': {
        endpoint: () => `/partners/${window.PARTNER_ID}`,
        method: 'put',
        section: 'translatable',
        successMessage: 'Descrizione aggiornata con successo',
        validate: () => ({}),
        collect: () => ({
            description_short: richEditors.get('description_short')?.getData() ?? '',
            hero_title: document.querySelector('#form-partner-description [name="hero_title"]')?.value ?? '',
            hero_subtitle: document.querySelector('#form-partner-description [name="hero_subtitle"]')?.value ?? '',
        }),
    },
    'form-partner-policy-contatti': {
        endpoint: () => `/partners/${window.PARTNER_ID}`,
        method: 'put',
        section: 'contatti',
        successMessage: 'Pagina contatti aggiornata con successo',
        validate: () => ({}),
        collect: () => ({ contacts_content: richEditors.get('contacts_content')?.getData() ?? '' }),
    },
    'form-partner-policy-privacy-policy': {
        endpoint: () => `/partners/${window.PARTNER_ID}`,
        method: 'put',
        section: 'translatable',
        successMessage: 'Privacy Policy aggiornata con successo',
        validate: () => ({}),
        collect: () => ({ privacy_policy: richEditors.get('privacy_policy')?.getData() ?? '' }),
    },
    'form-partner-policy-cookie-policy': {
        endpoint: () => `/partners/${window.PARTNER_ID}`,
        method: 'put',
        section: 'translatable',
        successMessage: 'Cookie Policy aggiornata con successo',
        validate: () => ({}),
        collect: () => ({ cookie_policy: richEditors.get('cookie_policy')?.getData() ?? '' }),
    },
    'form-partner-policy-termini-condizioni': {
        endpoint: () => `/partners/${window.PARTNER_ID}`,
        method: 'put',
        section: 'translatable',
        successMessage: 'Termini e Condizioni aggiornati con successo',
        validate: () => ({}),
        collect: () => ({ terms_conditions: richEditors.get('terms_conditions')?.getData() ?? '' }),
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

    if (config.collect) {
        Object.assign(data, config.collect());
    }

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
                <div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting">
                    <div class="text-field-container">
                        <input class="input-miticko" name="name" value="${name}">
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-3">
                <div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting">
                    <div class="text-field-container">
                        <input class="input-miticko" name="email" type="email" value="${email}">
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-3">
                <div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting">
                    <div class="text-field-container">
                        <input class="input-miticko" name="password" type="password" placeholder="Nuova password...">
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-2">
                <div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting">
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
        $('.btn-save-users').attr('data-mode', 'buttonSize-Medium buttonEmphasis-High buttonAppearance-Primary').removeClass('btn-m-primary').addClass('btn-m-default');
    } else {
        $('.btn-save-users').attr('data-mode', 'buttonSize-Medium buttonEmphasis-Medium  buttonAppearance-Disabled').removeClass('btn-m-default').addClass('btn-m-primary');
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

    App.ajax({ path: `/partners/${window.PARTNER_ID}/users`, method: 'post', data })
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

            App.ajax({ path: `/partners/${window.PARTNER_ID}/users/${id}`, method: 'put', data })
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
            App.ajax({ path: `/partners/${window.PARTNER_ID}/users/${id}`, method: 'delete' })
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
// Logo partner
// ---------------------------------------------------------------------------
const initLogo = () => {
    $(document).on('click', '.btn-logo-upload', () => {
        $('#partner-logo-input').val('').trigger('click');
    });

    $(document).on('change', '#partner-logo-input', function () {
        const file = this.files[0];
        if (!file) return;

        const $btn = $('.btn-logo-upload');
        setLoading($btn, true);

        const fd = new FormData();
        fd.append('image', file);

        $.ajax({
            url: `/partners/${window.PARTNER_ID}/logo`,
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
        }).then((media) => {
            $('#partner-logo-preview').html(
                `<img src="${media.url}" alt="Logo" style="max-height:80px;width:auto;object-fit:contain">`
            );
            $('.btn-logo-delete').show();
            toastr.success('Logo aggiornato con successo');
        }).catch((err) => {
            toastr.error(err?.responseJSON?.message || 'Errore durante il caricamento');
        }).always(() => setLoading($btn, false));
    });

    $(document).on('click', '.btn-logo-delete', function () {
        const $btn = $(this);
        App.sweetConfirm('Vuoi rimuovere il logo del partner?', () => {
            setLoading($btn, true);
            App.ajax({ path: `/partners/${window.PARTNER_ID}/logo`, method: 'delete' })
                .then(() => {
                    $('#partner-logo-preview').html('<span class="text-secondary small">Nessun logo</span>');
                    $btn.hide();
                    toastr.success('Logo rimosso');
                })
                .catch(() => toastr.error('Errore durante la rimozione'))
                .finally(() => setLoading($btn, false));
        }, null, 'Rimuovi logo');
    });
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
                    path: `/partners/${window.PARTNER_ID}`,
                    method: 'delete',
                }).then((res) => {
                    toastr.success('Partner eliminato con successo');
                    setTimeout(() => {
                        window.location.href = res.redirect ?? '/partners';
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
// Rich text editors (descrizione + documenti legali) + modale traduzioni
// ---------------------------------------------------------------------------
const richEditors = new Map(); // field (es. 'description_short', 'privacy_policy') → ClassicEditor

const richEditorConfig = {
    plugins: [
        Essentials, Paragraph, Bold, Italic, Underline, Strikethrough, RemoveFormat,
        List, Link, Heading,
    ],
    toolbar: {
        items: [
            'heading', '|',
            'bold', 'italic', 'underline', 'strikethrough', 'removeFormat', '|',
            'bulletedList', 'numberedList', '|',
            'link',
        ],
    },
    link: {
        addTargetToExternalLinks: true,
        decorators: {
            openInNewTab: {
                mode: 'automatic',
                callback: () => true,
                attributes: {
                    target: '_blank',
                    rel: 'noopener noreferrer',
                },
            },
        },
    },
    licenseKey: 'GPL',
};

const fieldLabels = {
    description_short: 'Breve descrizione',
    contacts_content:  'Pagina contatti',
    privacy_policy:    'Privacy Policy',
    cookie_policy:     'Cookie Policy',
    terms_conditions:  'Termini e Condizioni',
};

const initRichEditors = async () => {
    // Editor per documenti legali e pagina contatti
    // (wrapper .legal-rich-editor con data-legal-field e data-it; textarea figlia)
    const legalWrappers = document.querySelectorAll('.legal-rich-editor');
    for (const wrapper of legalWrappers) {
        const textarea = wrapper.querySelector('textarea');
        if (!textarea) continue;
        const field = wrapper.dataset.legalField;
        const initialValue = wrapper.dataset.it || '';
        await mountRichEditor(textarea, field, initialValue);
    }

    // Editor per la breve descrizione (textarea con id partner-description-editor)
    const descEl = document.getElementById('partner-description-editor');
    if (descEl) {
        await mountRichEditor(descEl, 'description_short', descEl.dataset.it || '');
    }
};

const mountRichEditor = async (el, field, initialValue = '') => {
    const editor = await ClassicEditor.create(el, richEditorConfig);
    editor.setData(initialValue);
    editor.model.document.on('change:data', () => {
        $(el).closest('.card-miticko').find('.btn-save-card')
            .attr('data-mode', 'buttonSize-Medium buttonEmphasis-High buttonAppearance-Primary');
    });
    richEditors.set(field, editor);
};

const langFlag = (isoCode) => {
    const flags = { it: '🇮🇹', en: '🇬🇧', de: '🇩🇪', fr: '🇫🇷', es: '🇪🇸', pt: '🇵🇹', nl: '🇳🇱', ru: '🇷🇺', zh: '🇨🇳', ja: '🇯🇵', ar: '🇸🇦' };
    return flags[isoCode.toLowerCase()] || '🏳';
};

// Editor CKEditor montati per lingua nel modal traduzioni
const translationEditors = new Map(); // language_id → ClassicEditor

const destroyTranslationEditors = async () => {
    for (const editor of translationEditors.values()) {
        try { await editor.destroy(); } catch (e) {}
    }
    translationEditors.clear();
};

const renderTranslationBody = (data) => {
    if (!data || data.length === 0) {
        return '<p class="text-secondary small mb-0">Nessuna lingua disponibile nel sistema.</p>';
    }
    return data.map(lang => `
        <div class="translation-lang mb-3" data-language-id="${lang.language_id}">
            <div class="d-flex align-items-center gap-2 mb-1">
                <span>${langFlag(lang.iso_code)}</span>
                <span class="fw-semibold small">${escapeHtml(lang.iso_code.toUpperCase())}</span>
                <span class="text-secondary small">${escapeHtml(lang.language)}</span>
            </div>
            <div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting">
                <div class="text-field-container">
                    <textarea class="input-miticko translation-editor"
                              data-language-id="${lang.language_id}"
                              data-initial="${escapeHtml(lang.value || '')}"
                              name="value"
                              rows="6"></textarea>
                </div>
            </div>
        </div>
    `).join('');
};

const mountTranslationEditors = async () => {
    const textareas = document.querySelectorAll('#modal-trans-body .translation-editor');
    for (const el of textareas) {
        const editor = await ClassicEditor.create(el, richEditorConfig);
        editor.setData(el.dataset.initial || '');
        translationEditors.set(String(el.dataset.languageId), editor);
    }
};

const openTranslationsModal = async (field) => {
    const $modal = $('#modal-translations');
    const path = `/partners/${window.PARTNER_ID}/translations/${field}`;
    const label = fieldLabels[field] || 'Campo';

    await destroyTranslationEditors();

    $modal.find('.modal-title').text(`Traduci — ${label}`);
    $('#modal-trans-body').html('<div class="text-center py-3"><i class="fa-regular fa-spinner fa-spin"></i></div>');
    $modal.data('save-path', path);
    $modal.modal('show');

    App.ajax({ path, method: 'get' })
        .then(async (res) => {
            $('#modal-trans-body').html(renderTranslationBody(res.data));
            await mountTranslationEditors();
        })
        .catch(() => {
            $('#modal-trans-body').html('<p class="text-danger small">Errore nel caricamento delle traduzioni.</p>');
        });
};

const saveTranslations = () => {
    const $modal = $('#modal-translations');
    const savePath = $modal.data('save-path');
    if (!savePath) return;

    const translations = [];
    $modal.find('.translation-lang').each(function () {
        const languageId = parseInt($(this).data('language-id'));
        const editor = translationEditors.get(String(languageId));
        const value = editor ? editor.getData() : ($(this).find('[name="value"]').val() || '');
        translations.push({ language_id: languageId, value });
    });

    App.ajax({ path: savePath, method: 'put', data: { translations } })
        .then(() => {
            toastr.success('Traduzioni salvate con successo');
            setTimeout(() => $modal.modal('hide'), 1200);
        })
        .catch(() => toastr.error('Errore durante il salvataggio delle traduzioni'));
};

const initTranslations = () => {
    // Pulsante globo accanto agli editor delle policy
    $(document).on('click', '.btn-legal-translations', function () {
        const type = $(this).data('legal-type');
        const mapping = { 'contatti': 'contacts_content', 'privacy-policy': 'privacy_policy', 'cookie-policy': 'cookie_policy', 'termini-condizioni': 'terms_conditions' };
        const field = mapping[type];
        if (field) openTranslationsModal(field);
    });

    // Pulsante globo accanto all'editor della Breve descrizione
    $(document).on('click', '.btn-description-translations', function () {
        openTranslationsModal('description_short');
    });

    $(document).on('click', '#modal-translations .btn-save-translations', saveTranslations);
    $(document).on('hidden.bs.modal', '#modal-translations', destroyTranslationEditors);
};

// ---------------------------------------------------------------------------
// Copia URL pagina (slug di sistema)
// ---------------------------------------------------------------------------
const initCopyUrl = () => {
    $(document).on('click', '.partner-url-copy .fa-copy', function () {
        const $icon = $(this);
        const $wrapper = $icon.closest('.partner-url-copy');
        const value = $wrapper.data('url') || $wrapper.find('input').val();
        if (!value) return;

        const fallback = () => {
            const $tmp = $('<textarea>').val(value).appendTo('body').select();
            try { document.execCommand('copy'); } catch (e) {}
            $tmp.remove();
        };

        if (navigator.clipboard?.writeText) {
            navigator.clipboard.writeText(value).catch(fallback);
        } else {
            fallback();
        }

        const originalClass = $icon.attr('class');
        $icon.attr('class', 'fa-regular fa-check icon text-success');
        $wrapper.find('input').addClass('border-success');
        setTimeout(() => {
            $icon.attr('class', originalClass);
            $wrapper.find('input').removeClass('border-success');
        }, 1400);

        toastr.success('URL copiato negli appunti');
    });

    // Cursore pointer sull'icona copia
    $(document).on('mouseenter', '.partner-url-copy .fa-copy', function () {
        $(this).css('cursor', 'pointer');
    });
};

// ---------------------------------------------------------------------------
// Consensi utente
// ---------------------------------------------------------------------------
const consentState = new Map(); // domId → { editor, translations:{iso:html}, currentLang:'it', dirty:bool }

const partnerConsentsBasePath = () => `/partners/${window.PARTNER_ID}/consents`;

const markConsentItemDirty = ($item) => {
    $item.find('> .card-miticko .btn-save-card, .card-miticko .btn-save-card')
        .first()
        .attr('data-mode', 'buttonSize-Medium buttonEmphasis-High buttonAppearance-Primary');
};

const clearConsentItemDirty = ($item) => {
    $item.find('> .card-miticko .btn-save-card, .card-miticko .btn-save-card')
        .first()
        .attr('data-mode', 'medium disabled');
};

const setConsentDirty = ($item, dirty = true) => {
    const state = consentState.get($item.attr('data-dom-id'));
    if (state) state.dirty = dirty;
    if (dirty) markConsentItemDirty($item);
    else clearConsentItemDirty($item);
};

const assignDomId = ($item) => {
    let id = $item.attr('data-dom-id');
    if (!id) {
        id = 'consent-' + Math.random().toString(36).slice(2, 10);
        $item.attr('data-dom-id', id);
    }
    return id;
};

const mountConsentEditor = async ($item) => {
    const textarea = $item.find('.consent-editor').get(0);
    if (!textarea) return;
    const initial = textarea.dataset.initial || '';
    const editor = await ClassicEditor.create(textarea, richEditorConfig);
    editor.setData(initial);

    const domId = assignDomId($item);
    const state = {
        editor,
        translations: { it: initial },
        currentLang: 'it',
        dirty: false,
        suppressDirty: false,
    };
    consentState.set(domId, state);

    editor.model.document.on('change:data', () => {
        const s = consentState.get(domId);
        if (!s || s.suppressDirty) return;
        s.translations[s.currentLang] = editor.getData();
        setConsentDirty($item, true);
    });
};

const initExistingConsents = async () => {
    const items = $('#consents-list .consent-item').toArray();
    for (const el of items) {
        await mountConsentEditor($(el));
    }
};

const fetchConsentLanguage = async (consentId, iso) => {
    if (!consentId) return '';
    const res = await App.ajax({
        path: `${partnerConsentsBasePath()}/${consentId}/translations`,
        method: 'get',
    });
    const list = res?.data || [];
    const found = list.find(l => l.iso_code === iso);
    return found?.content ?? '';
};

const changeConsentLanguage = async ($item, iso) => {
    const domId = $item.attr('data-dom-id');
    const state = consentState.get(domId);
    if (!state) return;

    // Salva il contenuto corrente nella cache della lingua precedente
    state.translations[state.currentLang] = state.editor.getData();
    state.currentLang = iso;

    let next = state.translations[iso];
    if (next === undefined) {
        const consentId = $item.attr('data-consent-id');
        next = consentId ? await fetchConsentLanguage(consentId, iso) : '';
        state.translations[iso] = next;
    }
    state.suppressDirty = true;
    state.editor.setData(next || '');
    state.suppressDirty = false;
};

const buildConsentPayload = ($item) => {
    const domId = $item.attr('data-dom-id');
    const state = consentState.get(domId);
    if (state) {
        state.translations[state.currentLang] = state.editor.getData();
    }
    return {
        is_required:   $item.find('.consent-required-input').val() === '1' ? 1 : 0,
        expiry_days:   parseInt($item.find('.consent-expiry-days').val() || 0, 10),
        expiry_months: parseInt($item.find('.consent-expiry-months').val() || 0, 10),
        expiry_years:  parseInt($item.find('.consent-expiry-years').val() || 0, 10),
        content_translations: state?.translations || {},
    };
};

const saveSingleConsent = async ($item, $btn) => {
    setLoading($btn, true);
    try {
        const state = consentState.get($item.attr('data-dom-id'));
        const consentId = $item.attr('data-consent-id');
        const isNew = !consentId;
        const payload = buildConsentPayload($item);

        const res = isNew
            ? await App.ajax({ path: partnerConsentsBasePath(), method: 'post', data: payload })
            : await App.ajax({ path: `${partnerConsentsBasePath()}/${consentId}`, method: 'put', data: payload });

        if (isNew && res?.consent?.id) {
            $item.attr('data-consent-id', String(res.consent.id));
        }
        if (state) state.dirty = false;
        toastr.success('Consenso salvato');
        clearConsentItemDirty($item);
    } catch (e) {
        toastr.error('Errore durante il salvataggio del consenso');
    } finally {
        setLoading($btn, false);
    }
};

const addConsentRow = async () => {
    const tpl = document.getElementById('consent-item-template');
    if (!tpl) return;
    const html = tpl.innerHTML;
    const $new = $(html);
    $new.removeAttr('data-consent-id');
    $('#consents-list').append($new);
    await mountConsentEditor($new);
    setConsentDirty($new, true);
};

const deleteConsentRow = ($item) => {
    const consentId = $item.attr('data-consent-id');
    const domId = $item.attr('data-dom-id');

    const purge = () => {
        const s = consentState.get(domId);
        if (s) { try { s.editor.destroy(); } catch (e) {} }
        consentState.delete(domId);
        $item.remove();
    };

    if (!consentId) {
        purge();
        return;
    }

    App.sweetConfirm('Vuoi eliminare definitivamente questo consenso?', () => {
        App.ajax({ path: `${partnerConsentsBasePath()}/${consentId}`, method: 'delete' })
            .then(() => {
                purge();
                toastr.success('Consenso eliminato');
            })
            .catch(() => toastr.error('Errore durante l\'eliminazione'));
    }, null, 'Elimina consenso');
};

const enableConsents = () => {
    const $btn = $('.btn-consents-enable');
    setLoading($btn, true);
    App.ajax({ path: `${partnerConsentsBasePath()}/enable`, method: 'post' })
        .then(() => {
            toastr.success('Sezione consensi abilitata');
            setTimeout(() => window.location.reload(), 600);
        })
        .catch(() => {
            setLoading($btn, false);
            toastr.error('Errore durante l\'abilitazione');
        });
};

let _consentsSortable = null;
const initConsentsSortable = () => {
    const el = document.getElementById('consents-list');
    if (!el || typeof Sortable === 'undefined') return;
    if (_consentsSortable) { _consentsSortable.destroy(); _consentsSortable = null; }
    _consentsSortable = Sortable.create(el, {
        handle: '.consent-handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
        onEnd() {
            const ids = [...el.querySelectorAll('.consent-item[data-consent-id]')]
                .map(it => it.getAttribute('data-consent-id'))
                .filter(id => id);
            if (ids.length === 0) return;
            App.ajax({
                path: `${partnerConsentsBasePath()}/reorder`,
                method: 'post',
                data: { ordered_ids: ids },
            })
                .then(() => toastr.success('Ordine aggiornato'))
                .catch(() => toastr.error('Errore durante il riordinamento'));
        },
    });
};

const toggleConsentActive = ($item, $btn) => {
    const consentId = $item.attr('data-consent-id');
    if (!consentId) {
        toastr.warning('Salva prima il consenso, poi potrai disabilitarlo.');
        return;
    }
    const wasActive = $item.attr('data-is-active') === '1';
    const nextActive = !wasActive;

    setLoading($btn, true);
    App.ajax({
        path: `${partnerConsentsBasePath()}/${consentId}/toggle-active`,
        method: 'put',
        data: { is_active: nextActive ? 1 : 0 },
    }).then(() => {
        // Stop loading PRIMA di aggiornare l'icona: altrimenti setLoading
        // ripristina l'icona "originale" salvata e annulla il cambio.
        setLoading($btn, false);
        $item.attr('data-is-active', nextActive ? '1' : '0');
        $item.toggleClass('consent-disabled', !nextActive);
        $item.find('.consent-disabled-badge').css('display', nextActive ? 'none' : 'inline-block');
        $btn.find('.consent-toggle-label').text(nextActive ? 'Disabilita' : 'Abilita');
        const $icon = $btn.find('i');
        $icon.attr('class', `fa-regular ${nextActive ? 'fa-toggle-on' : 'fa-toggle-off'} icon me-1`);
        $icon.removeData('original-class');
        $btn.attr('title', nextActive ? 'Disabilita questo consenso lato frontend' : 'Riabilita questo consenso lato frontend');
        toastr.success(nextActive ? 'Consenso abilitato' : 'Consenso disabilitato');
    }).catch(() => {
        setLoading($btn, false);
        toastr.error('Errore durante l\'aggiornamento dello stato');
    });
};

const initConsents = () => {
    $(document).on('click', '.btn-consents-enable', enableConsents);
    $(document).on('click', '.btn-consent-add', addConsentRow);
    $(document).on('click', '.btn-consent-delete', function () {
        deleteConsentRow($(this).closest('.consent-item'));
    });
    $(document).on('click', '.btn-consent-toggle', function () {
        toggleConsentActive($(this).closest('.consent-item'), $(this));
    });
    $(document).on('click', '.consent-item .btn-save-card', function (e) {
        e.stopImmediatePropagation();
        saveSingleConsent($(this).closest('.consent-item'), $(this));
    });
    $(document).on('change', '.consent-item .consent-language', function () {
        const $item = $(this).closest('.consent-item');
        changeConsentLanguage($item, $(this).val());
    });
    $(document).on('change input', '.consent-item .consent-expiry-days, .consent-item .consent-expiry-months, .consent-item .consent-expiry-years', function () {
        const $item = $(this).closest('.consent-item');
        setConsentDirty($item, true);
    });
    $(document).on('click', '.consent-item .consent-required-wrap:not(.disabled)', function () {
        const $wrap  = $(this);
        const $input = $wrap.find('.consent-required-input');
        const $box   = $wrap.find('.consent-check-box');
        const nowChecked = $input.val() !== '1';
        if (nowChecked) {
            $input.val('1');
            $box.addClass('checked').html('<i class="fa-solid fa-check"></i>');
        } else {
            $input.val('0');
            $box.removeClass('checked').empty();
        }
        setConsentDirty($wrap.closest('.consent-item'), true);
    });

    if (document.getElementById('consents-list')) {
        initExistingConsents();
        initConsentsSortable();
    }
};

// ---------------------------------------------------------------------------
// Init
// ---------------------------------------------------------------------------
const init = () => {
    $(document).on('input change', 'form :input:not([disabled])', function () {
        $(this).closest('.card-miticko').find('.btn-save-card').attr('data-mode', 'buttonSize-Medium buttonEmphasis-High buttonAppearance-Primary');
    });

    $(document).on('click', '.btn-save-card', function () {
        if ($(this).closest('.consent-item').length) return; // gestito da initConsents
        const card = $(this).closest('.card-miticko');
        const form = card.find('form');
        if (!form.length) return;
        saveForm(form.attr('id'), $(this));
    });

    initUsers();
    initLogo();
    initDeletePartner();
    initTranslations();
    initCopyUrl();
    initConsents();
    initRichEditors();
};

$(function () {
    init();
});
