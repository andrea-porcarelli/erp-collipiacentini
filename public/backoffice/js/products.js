import App from "./app.js";

// ---------------------------------------------------------------------------
// Form save configs (impostazioni, durata, categorie, pubblico)
// ---------------------------------------------------------------------------
const formConfigs = {
    'form-info-settings': {
        endpoint: () => `/backoffice/products/${window.PRODUCT_ID}`,
        method: 'put',
        section: 'settings',
        successMessage: 'Impostazioni prodotto aggiornate con successo',
        validate: (data) => {
            const errors = {};
            if (!data.label || data.label.trim() === '') {
                errors.label = ['Il nome prodotto interno è obbligatorio'];
            }
            return errors;
        },
    },
    'form-info-duration': {
        endpoint: () => `/backoffice/products/${window.PRODUCT_ID}`,
        method: 'put',
        section: 'duration',
        successMessage: 'Durata aggiornata con successo',
        validate: (data) => {
            const errors = {};
            const days = parseInt(data.duration_days) || 0;
            const hours = parseInt(data.duration_hours) || 0;
            const minutes = parseInt(data.duration_minutes) || 0;
            if (days === 0 && hours === 0 && minutes === 0) {
                errors.duration_minutes = ['Inserisci almeno un valore di durata'];
            }
            return errors;
        },
    },
    'form-info-categories': {
        endpoint: () => `/backoffice/products/${window.PRODUCT_ID}`,
        method: 'put',
        section: 'categories',
        successMessage: 'Categoria aggiornata con successo',
        validate: () => ({}),
    },
    'form-info-public': {
        endpoint: () => `/backoffice/products/${window.PRODUCT_ID}`,
        method: 'put',
        section: 'public',
        successMessage: 'Impostazioni pubbliche aggiornate con successo',
        validate: (data) => {
            const errors = {};
            if (!data.meta_title || data.meta_title.trim() === '') {
                errors.meta_title = ['Il nome prodotto pubblico è obbligatorio'];
            }
            return errors;
        },
    },
};

// ---------------------------------------------------------------------------
// Configurazione campi per le traduzioni (estendibile ad altri elementi)
// ---------------------------------------------------------------------------
const translationFields = {
    link: [
        { name: 'label', label: 'Label', type: 'input', placeholder: 'es. Prenota ora' },
        { name: 'link',  label: 'URL',   type: 'input', placeholder: 'https://...' },
    ],
    faq: [
        { name: 'question', label: 'Domanda',  type: 'input',    placeholder: 'es. Come posso prenotare?' },
        { name: 'answer',   label: 'Risposta', type: 'textarea', placeholder: 'Inserisci la risposta...' },
    ],
};

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------
const langFlag = (isoCode) => {
    const flags = { it: '🇮🇹', en: '🇬🇧', de: '🇩🇪', fr: '🇫🇷', es: '🇪🇸', pt: '🇵🇹', nl: '🇳🇱', ru: '🇷🇺', zh: '🇨🇳', ja: '🇯🇵', ar: '🇸🇦' };
    return flags[isoCode.toLowerCase()] || '🏳';
};

const escapeHtml = (text) =>
    String(text ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

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

const saveForm = (formId, btn) => {
    const config = formConfigs[formId];
    if (!config) return;

    const selector = `#${formId}`;
    const { data, form } = App.serialize(selector);

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
// Modale traduzioni condivisa
// ---------------------------------------------------------------------------
const renderTranslationBody = (data, entityType) => {
    const fields = translationFields[entityType];

    if (!data || data.length === 0) {
        return '<p class="text-secondary small mb-0">Nessuna lingua disponibile nel sistema.</p>';
    }


    const headerRow = `<div class="row g-2 align-items-center mb-1">
        <div class="col-auto" style="width:64px"></div>
        <div class="col"><span class="small fw-semibold">Nome variante *</span></div>
        <div class="col"><span class="small fw-semibold">URL</span></div>
    </div>`;

    const rowsHtml = data.map(lang => {
        const fieldsHtml = fields.map(f => {
            const value = escapeHtml(lang[f.name] || '');
            if (f.type === 'textarea') {
                return `<div class="col">
                    <div class="text-field" data-mode="medium">
                        <div class="text-field-container">
                            <textarea class="input-miticko" name="${f.name}" rows="2" placeholder="${f.placeholder || ''}">${value}</textarea>
                        </div>
                    </div>
                </div>`;
            }
            return `<div class="col">
                <div class="text-field" data-mode="medium">
                    <div class="text-field-container">
                        <input class="input-miticko" name="${f.name}" value="${value}" placeholder="${f.placeholder || ''}">
                    </div>
                </div>
            </div>`;
        }).join('');

        return `<div class="translation-lang row g-2 align-items-center mb-2" data-language-id="${lang.language_id}">
            <div class="col-auto d-flex align-items-center gap-1" style="width:64px">
                <span>${langFlag(lang.iso_code)}</span>
                <span class="fw-semibold small">${escapeHtml(lang.iso_code.toUpperCase())}</span>
            </div>
            ${fieldsHtml}
        </div>`;
    }).join('');

    return headerRow + rowsHtml;
};

const openTranslationsModal = (entityType, id) => {
    const $modal = $('#modal-translations');
    const path = entityType === 'link'
        ? `/backoffice/products/${window.PRODUCT_ID}/links/${id}/translations`
        : `/backoffice/products/${window.PRODUCT_ID}/faqs/${id}/translations`;

    $('#modal-trans-body').html('<div class="text-center py-3"><i class="fa-regular fa-spinner fa-spin"></i></div>');
    $modal.data('save-path', path).data('entity-type', entityType);
    $modal.modal('show');

    App.ajax({ path, method: 'get' })
        .then((res) => {
            $('#modal-trans-body').html(renderTranslationBody(res.data, entityType));
        })
        .catch(() => {
            $('#modal-trans-body').html('<p class="text-danger small">Errore nel caricamento delle traduzioni.</p>');
        });
};

const saveTranslations = () => {
    const $modal = $('#modal-translations');
    const savePath = $modal.data('save-path');
    const entityType = $modal.data('entity-type');
    const fields = translationFields[entityType];

    const translations = [];
    $modal.find('.translation-lang').each(function () {
        const langId = parseInt($(this).data('language-id'));
        const translation = { language_id: langId };
        fields.forEach(f => {
            translation[f.name] = $(this).find(`[name="${f.name}"]`).val() || '';
        });
        translations.push(translation);
    });

    App.ajax({ path: savePath, method: 'put', data: { translations } })
        .then(() => {
            toastr.success('Traduzioni salvate con successo');
            $modal.modal('hide');
        })
        .catch(() => toastr.error('Errore durante il salvataggio delle traduzioni'));
};

// ---------------------------------------------------------------------------
// Links
// ---------------------------------------------------------------------------
const renderLinkRow = (link) => {
    const label = escapeHtml(link.label);
    const url   = escapeHtml(link.link);
    return `
    <div class="link-item py-1" data-id="${link.id}">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-sm-4">
                <div class="text-field" data-mode="medium">
                    <div class="text-field-container">
                        <input class="input-miticko" name="label" value="${label}">
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-7">
                <div class="text-field" data-mode="medium">
                    <div class="text-field-container">
                        <input class="input-miticko" name="link" value="${url}">
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-1 d-flex gap-1 align-items-end justify-content-end pb-1">
                <button type="button" data-mode="medium primary" class="bt-miticko btn-link-translations bt-m-light"><i class="fa-regular fa-language icon"></i></button>
                <button type="button" data-mode="medium primary" class="bt-miticko btn-link-delete bt-m-text-only"><i class="fa-regular fa-trash icon"></i></button>
            </div>
        </div>
    </div>`;
};

const updateSaveLinksButton = () => {
    const hasDirty = $('#links-list .link-item[data-dirty]').length > 0;
    $('.btn-save-links').attr('data-mode', hasDirty ? 'medium primary' : 'medium disabled');
    if (hasDirty) {
        $('.btn-save-links').attr('data-mode', 'medium primary').removeClass('btn-m-primary').addClass('btn-m-default');
    } else {
        $('.btn-save-links').attr('data-mode', 'medium disabled').removeClass('btn-m-default').addClass('btn-m-primary');
    }
};

const addLink = () => {
    const $form = $('#form-link-new');
    const data = {
        label: $form.find('input[name="label"]').val(),
        link:  $form.find('input[name="link"]').val(),
    };
    if (!data.label || !data.link) {
        toastr.warning('Label e URL sono obbligatori');
        return;
    }
    App.ajax({ path: `/backoffice/products/${window.PRODUCT_ID}/links`, method: 'post', data })
        .then((link) => {
            $('#links-empty').remove();
            $('#links-list').append(renderLinkRow(link));
            $form[0].reset();
            toastr.success('Link aggiunto');
        })
        .catch(() => toastr.error('Errore durante il salvataggio'));
};

const initLinks = () => {
    // Modifica input → segna la riga come dirty e attiva "Salva modifiche"
    $(document).on('input', '.link-item input', function () {
        $(this).closest('.link-item').attr('data-dirty', '1');
        updateSaveLinksButton();
    });

    // Salva tutte le righe modificate
    $(document).on('click', '.btn-save-links', function () {
        const $btn = $(this);
        const $dirtyItems = $('#links-list .link-item[data-dirty]');
        if ($dirtyItems.length === 0) return;

        setLoading($btn, true);
        let pending = $dirtyItems.length;
        let hasError = false;

        $dirtyItems.each(function () {
            const $item = $(this);
            const id = $item.data('id');
            const data = {
                label: $item.find('input[name="label"]').val(),
                link:  $item.find('input[name="link"]').val(),
            };
            App.ajax({ path: `/backoffice/products/${window.PRODUCT_ID}/links/${id}`, method: 'put', data })
                .then(() => {
                    $item.removeAttr('data-dirty');
                })
                .catch(() => { hasError = true; })
                .finally(() => {
                    pending--;
                    if (pending === 0) {
                        setLoading($btn, false);
                        if (hasError) {
                            toastr.error('Alcuni link non sono stati salvati. Riprova.');
                        } else {
                            toastr.success('Link aggiornati con successo');
                        }
                        updateSaveLinksButton();
                    }
                });
        });
    });

    $(document).on('click', '.btn-link-delete', function () {
        const $item = $(this).closest('.link-item');
        const id = $item.data('id');
        App.sweetConfirm('Vuoi eliminare questo link?', () => {
            App.ajax({ path: `/backoffice/products/${window.PRODUCT_ID}/links/${id}`, method: 'delete' })
                .then(() => {
                    $item.remove();
                    if ($('#links-list .link-item').length === 0) {
                        $('#links-list').prepend('<p class="text-secondary small mb-0" id="links-empty">Nessun link aggiunto.</p>');
                    }
                    toastr.success('Link eliminato');
                })
                .catch(() => toastr.error('Errore durante l\'eliminazione'));
        }, null, 'Elimina link');
    });

    $(document).on('click', '.btn-link-translations', function () {
        const $item = $(this).closest('.link-item');
        const id = $item.data('id');
        openTranslationsModal('link', id);
    });

    $(document).on('click', '.btn-link-add', addLink);
};

// ---------------------------------------------------------------------------
// FAQ
// ---------------------------------------------------------------------------
const renderFaqRow = (faq) => {
    const question = escapeHtml(faq.question);
    const answer   = escapeHtml(faq.answer);
    return `
    <div class="faq-item py-3 border-bottom" data-id="${faq.id}">
        <div class="faq-view">
            <div class="row g-2 align-items-start">
                <div class="col-12 col-sm-10">
                    <div class="text-field" data-mode="medium">
                        <label>Domanda</label>
                        <div class="text-field-container">
                            <input class="input-miticko" name="question" value="${question}" disabled>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-2 d-flex gap-1 align-items-start justify-content-end pt-4">
                    <button type="button" data-mode="small secondary" class="bt-miticko btn-faq-edit bt-m-outlined"><i class="fa-regular fa-pen icon"></i></button>
                    <button type="button" data-mode="small danger" class="bt-miticko btn-faq-delete bt-m-outlined"><i class="fa-regular fa-trash icon"></i></button>
                    <button type="button" data-mode="small secondary" class="bt-miticko btn-faq-translations bt-m-outlined"><i class="fa-regular fa-globe icon"></i></button>
                </div>
                <div class="col-12">
                    <div class="text-field" data-mode="medium">
                        <label>Risposta</label>
                        <div class="text-field-container">
                            <textarea class="input-miticko" name="answer" rows="3" disabled>${answer}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="faq-edit d-none">
            <div class="row g-2">
                <div class="col-12">
                    <div class="text-field" data-mode="medium">
                        <label>Domanda</label>
                        <div class="text-field-container">
                            <input class="input-miticko" name="question" value="${question}" placeholder="es. Come posso prenotare?">
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="text-field" data-mode="medium">
                        <label>Risposta</label>
                        <div class="text-field-container">
                            <textarea class="input-miticko" name="answer" rows="3" placeholder="Inserisci la risposta...">${answer}</textarea>
                        </div>
                    </div>
                </div>
                <div class="col-12 d-flex gap-1 justify-content-end">
                    <button type="button" data-mode="small success" class="bt-miticko btn-faq-save bt-m-primary"><i class="fa-regular fa-check icon"></i> Salva</button>
                    <button type="button" data-mode="small secondary" class="bt-miticko btn-faq-cancel bt-m-outlined"><i class="fa-regular fa-xmark icon"></i> Annulla</button>
                </div>
            </div>
        </div>
    </div>`;
};

const initFaqs = () => {
    $(document).on('click', '.btn-faq-edit', function () {
        const $item = $(this).closest('.faq-item');
        $item.find('.faq-view').addClass('d-none');
        $item.find('.faq-edit').removeClass('d-none');
    });

    $(document).on('click', '.btn-faq-cancel', function () {
        const $item = $(this).closest('.faq-item');
        $item.find('.faq-edit').addClass('d-none');
        $item.find('.faq-view').removeClass('d-none');
    });

    $(document).on('click', '.btn-faq-save', function () {
        const $item = $(this).closest('.faq-item');
        const id = $item.data('id');
        const data = {
            question: $item.find('.faq-edit input[name="question"]').val(),
            answer:   $item.find('.faq-edit textarea[name="answer"]').val(),
        };
        App.ajax({ path: `/backoffice/products/${window.PRODUCT_ID}/faqs/${id}`, method: 'put', data })
            .then((res) => {
                $item.find('.faq-view input[name="question"]').val(res.question);
                $item.find('.faq-view textarea[name="answer"]').val(res.answer);
                $item.find('.faq-edit').addClass('d-none');
                $item.find('.faq-view').removeClass('d-none');
                toastr.success('FAQ aggiornata');
            })
            .catch(() => toastr.error('Errore durante il salvataggio'));
    });

    $(document).on('click', '.btn-faq-delete', function () {
        const $item = $(this).closest('.faq-item');
        const id = $item.data('id');
        App.sweetConfirm('Vuoi eliminare questa FAQ?', () => {
            App.ajax({ path: `/backoffice/products/${window.PRODUCT_ID}/faqs/${id}`, method: 'delete' })
                .then(() => {
                    $item.remove();
                    if ($('#faqs-list .faq-item').length === 0) {
                        $('#faqs-list').prepend('<p class="text-secondary small mb-0" id="faqs-empty">Nessuna FAQ aggiunta.</p>');
                    }
                    toastr.success('FAQ eliminata');
                })
                .catch(() => toastr.error('Errore durante l\'eliminazione'));
        }, null, 'Elimina FAQ');
    });

    $(document).on('click', '.btn-faq-translations', function () {
        const $item = $(this).closest('.faq-item');
        const id = $item.data('id');
        openTranslationsModal('faq', id);
    });

    $(document).on('click', '#btn-faq-add', function () {
        const $form = $('#form-faq-new');
        const data = {
            question: $form.find('input[name="question"]').val(),
            answer:   $form.find('textarea[name="answer"]').val(),
        };
        if (!data.question || !data.answer) {
            toastr.warning('Domanda e risposta sono obbligatorie');
            return;
        }
        App.ajax({ path: `/backoffice/products/${window.PRODUCT_ID}/faqs`, method: 'post', data })
            .then((faq) => {
                $('#faqs-empty').remove();
                $('#faqs-list').append(renderFaqRow(faq));
                $form[0].reset();
                toastr.success('FAQ aggiunta');
            })
            .catch(() => toastr.error('Errore durante il salvataggio'));
    });
};

// ---------------------------------------------------------------------------
// Campi cliente
// ---------------------------------------------------------------------------
const initCustomerFields = () => {
    $(document).on('change', '.customer-field-enabled', function () {
        const $wrap = $(this).closest('.customer-field-item').find('.customer-field-required-wrap');
        if ($(this).is(':checked')) {
            $wrap.css({ opacity: 1, 'pointer-events': 'auto' });
        } else {
            $wrap.css({ opacity: 0.35, 'pointer-events': 'none' });
            $wrap.find('.customer-field-required').prop('checked', false);
        }
    });

    $(document).on('click', '.btn-save-customer-fields', function () {
        const btn = $(this);
        const fields = [];

        $('#form-customer-fields .customer-field-item[data-field-id]').each(function () {
            const $item = $(this);
            const fieldId = $item.data('field-id');
            const enabled = $item.find('.customer-field-enabled').is(':checked');
            if (enabled) {
                fields.push({
                    customer_field_type_id: fieldId,
                    is_required: $item.find('.customer-field-required').is(':checked') ? 1 : 0,
                });
            }
        });

        setLoading(btn, true);
        App.ajax({
            path: `/backoffice/products/${window.PRODUCT_ID}/customer-fields/sync`,
            method: 'post',
            data: { fields },
        }).then(() => {
            toastr.success('Dati cliente aggiornati con successo');
            setLoading(btn, false);
        }).catch(() => {
            toastr.error('Errore durante il salvataggio');
            setLoading(btn, false);
        });
    });
};

// ---------------------------------------------------------------------------
// Prodotti correlati
// ---------------------------------------------------------------------------
const MAX_RELATED = 5;

const renderRelatedRow = (related) => `
    <div class="related-item d-flex align-items-center gap-3 py-2 border-bottom" data-id="${related.id}">
        <span class="fw-semibold flex-grow-1">${escapeHtml(related.label)}</span>
        <span class="text-secondary small">${escapeHtml(related.product_code ?? '')}</span>
        <button class="btn-related-delete btn-miticko outlined danger small"><i class="fa-regular fa-trash icon"></i></button>
    </div>`;

const initRelated = () => {
    let searchTimeout = null;
    let selectedProduct = null;

    $(document).on('input', '#related-search-input', function () {
        const q = $(this).val().trim();
        clearTimeout(searchTimeout);
        if (q.length < 2) {
            $('#related-search-results').hide().empty();
            selectedProduct = null;
            return;
        }
        searchTimeout = setTimeout(() => {
            App.ajax({
                path: `/backoffice/products/${window.PRODUCT_ID}/related/search?q=${encodeURIComponent(q)}`,
                method: 'get',
            }).then((res) => {
                const $results = $('#related-search-results');
                $results.empty();
                if (!res.data || res.data.length === 0) {
                    $results.append('<div class="list-group-item text-secondary small">Nessun risultato</div>');
                } else {
                    res.data.forEach(p => {
                        $results.append(
                            `<button type="button" class="list-group-item list-group-item-action btn-related-pick" data-id="${p.id}" data-label="${escapeHtml(p.label)}" data-code="${escapeHtml(p.product_code ?? '')}">
                                <span class="fw-semibold">${escapeHtml(p.label)}</span>
                                <span class="text-secondary small ms-2">${escapeHtml(p.product_code ?? '')}</span>
                            </button>`
                        );
                    });
                }
                $results.show();
            }).catch(() => {});
        }, 300);
    });

    $(document).on('click', '.btn-related-pick', function () {
        selectedProduct = {
            id:    $(this).data('id'),
            label: $(this).data('label'),
            code:  $(this).data('code'),
        };
        $('#related-search-input').val(selectedProduct.label);
        $('#related-search-results').hide().empty();
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('#related-search-input, #related-search-results').length) {
            $('#related-search-results').hide();
        }
    });

    $(document).on('click', '#btn-related-add', function () {
        if (!selectedProduct) {
            toastr.warning('Seleziona un prodotto dalla lista');
            return;
        }
        App.ajax({
            path: `/backoffice/products/${window.PRODUCT_ID}/related`,
            method: 'post',
            data: { related_product_id: selectedProduct.id },
        }).then((related) => {
            $('#related-empty').remove();
            $('#related-list').append(renderRelatedRow(related));
            $('#related-search-input').val('');
            selectedProduct = null;

            const count = $('#related-list .related-item').length;
            if (count >= MAX_RELATED) {
                $('#related-add-section').hide();
                if (!$('#related-limit-msg').length) {
                    $('#related-add-section').after('<p class="text-secondary small mt-2 mb-0" id="related-limit-msg">Hai raggiunto il limite massimo di ' + MAX_RELATED + ' prodotti correlati.</p>');
                }
            }
            toastr.success('Prodotto correlato aggiunto');
        }).catch((err) => {
            toastr.error(err?.responseJSON?.message || 'Errore durante il salvataggio');
        });
    });

    $(document).on('click', '.btn-related-delete', function () {
        const $item = $(this).closest('.related-item');
        const id = $item.data('id');
        App.sweetConfirm('Vuoi rimuovere questo prodotto correlato?', () => {
            App.ajax({
                path: `/backoffice/products/${window.PRODUCT_ID}/related/${id}`,
                method: 'delete',
            }).then(() => {
                $item.remove();
                if ($('#related-list .related-item').length === 0) {
                    $('#related-list').prepend('<p class="text-secondary small mb-0" id="related-empty">Nessun prodotto correlato aggiunto.</p>');
                }
                $('#related-add-section').show();
                $('#related-limit-msg').remove();
                toastr.success('Prodotto correlato rimosso');
            }).catch(() => toastr.error('Errore durante l\'eliminazione'));
        }, null, 'Rimuovi prodotto correlato');
    });
};

// ---------------------------------------------------------------------------
// Categoria
// ---------------------------------------------------------------------------
const initCategorySelect = () => {
    const initialCategoryId = window.PRODUCT_CATEGORY_ID;
    if (initialCategoryId) {
        $('#category_id').val(initialCategoryId);
    }
};

// ---------------------------------------------------------------------------
// Elimina prodotto
// ---------------------------------------------------------------------------
const initDeleteProduct = () => {
    $(document).on('click', '.btn-delete-product', function () {
        App.sweetConfirm(
            'Vuoi eliminare definitivamente questo prodotto? L\'operazione è irreversibile.',
            () => {
                App.ajax({
                    path: `/backoffice/products/${window.PRODUCT_ID}`,
                    method: 'delete',
                }).then((res) => {
                    toastr.success('Prodotto eliminato con successo');
                    setTimeout(() => {
                        window.location.href = res.redirect ?? '/backoffice/products';
                    }, 800);
                }).catch((err) => {
                    toastr.error(err?.responseJSON?.message || 'Errore durante l\'eliminazione');
                });
            },
            null,
            'Elimina prodotto'
        );
    });
};

// ---------------------------------------------------------------------------
// Init
// ---------------------------------------------------------------------------
const init = () => {
    $(document).on('click', '.btn-save-card', function () {
        const card = $(this).closest('.card-miticko');
        const form = card.find('form');
        if (!form.length) return;
        saveForm(form.attr('id'), $(this));
    });

    $(document).on('click', '.btn-save-translations', function () {
        saveTranslations();
    });

    initCategorySelect();
    initLinks();
    initFaqs();
    initRelated();
    initCustomerFields();
    initDeleteProduct();
};

$(function () {
    init();
});
