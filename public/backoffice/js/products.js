import App from "./app.js";

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
        if (originalClass) {
            icon.attr('class', originalClass);
        }
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

const loadSubCategories = (categoryId, selectedId = null) => {
    const $subSelect = $('#sub_category_id');
    if (!categoryId) {
        $subSelect.html('<option value="">Scegli</option>');
        return;
    }
    App.ajax({
        path: `/backoffice/products/sub-categories/${categoryId}`,
        method: 'get',
    }).then((data) => {
        let options = '<option value="">Scegli</option>';
        data.forEach(sub => {
            const selected = selectedId && sub.id == selectedId ? ' selected' : '';
            options += `<option value="${sub.id}"${selected}>${sub.label}</option>`;
        });
        $subSelect.html(options);
    }).catch(() => {
        $subSelect.html('<option value="">Scegli</option>');
    });
};

const initCategorySelect = () => {
    const initialCategoryId = window.PRODUCT_CATEGORY_ID;
    if (initialCategoryId) {
        $('#category_id').val(initialCategoryId);
        loadSubCategories(initialCategoryId, window.PRODUCT_SUB_CATEGORY_ID);
    }

    $(document).on('change', '#category_id', function () {
        loadSubCategories($(this).val());
    });
};

const renderLinkRow = (link) => `
    <div class="link-item" data-id="${link.id}">
        <div class="link-display d-flex align-items-center gap-3 py-2 border-bottom">
            <span class="link-col-lang text-secondary small">${link.language ?? '—'}</span>
            <span class="link-col-label fw-semibold flex-grow-1">${link.label}</span>
            <span class="link-col-url text-secondary small text-truncate" style="max-width:220px">${link.link}</span>
            <div class="d-flex gap-2">
                <button class="btn-link-edit btn-miticko outlined secondary small"><i class="fa-regular fa-pen icon"></i></button>
                <button class="btn-link-delete btn-miticko outlined danger small"><i class="fa-regular fa-trash icon"></i></button>
            </div>
        </div>
        <div class="link-edit d-none py-2 border-bottom">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-sm-3">
                    <select name="language_id" class="input-miticko">${$('#form-link-new select[name="language_id"]').html()}</select>
                </div>
                <div class="col-12 col-sm-3">
                    <input name="label" class="input-miticko" value="${link.label}">
                </div>
                <div class="col-12 col-sm-4">
                    <input name="link" class="input-miticko" value="${link.link}">
                </div>
                <div class="col-12 col-sm-2 d-flex gap-2">
                    <button class="btn-link-save btn-miticko primary success small"><i class="fa-regular fa-check icon"></i></button>
                    <button class="btn-link-cancel btn-miticko outlined secondary small"><i class="fa-regular fa-xmark icon"></i></button>
                </div>
            </div>
        </div>
    </div>`;

const initLinks = () => {
    // Seleziona valore lingua nella riga di edit dopo il render
    const syncLangSelect = ($item, langId) => {
        $item.find('.link-edit select[name="language_id"]').val(langId);
    };

    // Mostra form di modifica
    $(document).on('click', '.btn-link-edit', function () {
        const $item = $(this).closest('.link-item');
        $item.find('.link-display').addClass('d-none');
        $item.find('.link-edit').removeClass('d-none');
        syncLangSelect($item, $item.data('lang-id'));
    });

    // Annulla modifica
    $(document).on('click', '.btn-link-cancel', function () {
        const $item = $(this).closest('.link-item');
        $item.find('.link-edit').addClass('d-none');
        $item.find('.link-display').removeClass('d-none');
    });

    // Salva modifica
    $(document).on('click', '.btn-link-save', function () {
        const $item = $(this).closest('.link-item');
        const id = $item.data('id');
        const data = {
            language_id: $item.find('.link-edit select[name="language_id"]').val(),
            label:       $item.find('.link-edit input[name="label"]').val(),
            link:        $item.find('.link-edit input[name="link"]').val(),
        };
        App.ajax({ path: `/backoffice/products/${window.PRODUCT_ID}/links/${id}`, method: 'put', data })
            .then((link) => {
                const $new = $(renderLinkRow(link));
                $new.data('lang-id', link.language_id);
                $item.replaceWith($new);
                toastr.success('Link aggiornato');
                $('#links-empty').remove();
            })
            .catch(() => toastr.error('Errore durante il salvataggio'));
    });

    // Elimina
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

    // Aggiungi nuovo
    $(document).on('click', '#btn-link-add', function () {
        const $form = $('#form-link-new');
        const data = {
            language_id: $form.find('select[name="language_id"]').val(),
            label:       $form.find('input[name="label"]').val(),
            link:        $form.find('input[name="link"]').val(),
        };
        if (!data.label || !data.link) {
            toastr.warning('Label e URL sono obbligatori');
            return;
        }
        App.ajax({ path: `/backoffice/products/${window.PRODUCT_ID}/links`, method: 'post', data })
            .then((link) => {
                $('#links-empty').remove();
                const $row = $(renderLinkRow(link));
                $row.data('lang-id', link.language_id);
                $('#links-list').append($row);
                $form[0].reset();
                toastr.success('Link aggiunto');
            })
            .catch(() => toastr.error('Errore durante il salvataggio'));
    });
};

const renderFaqRow = (faq) => `
    <div class="faq-item" data-id="${faq.id}">
        <div class="faq-display py-3 border-bottom">
            <div class="d-flex align-items-start gap-3">
                <span class="badge bg-secondary mt-1" style="font-size:11px;white-space:nowrap">${faq.language ?? '—'}</span>
                <div class="flex-grow-1">
                    <p class="fw-semibold mb-1">${faq.question}</p>
                    <p class="text-secondary small mb-0">${faq.answer}</p>
                </div>
                <div class="d-flex gap-2 flex-shrink-0">
                    <button class="btn-faq-edit btn-miticko outlined secondary small"><i class="fa-regular fa-pen icon"></i></button>
                    <button class="btn-faq-delete btn-miticko outlined danger small"><i class="fa-regular fa-trash icon"></i></button>
                </div>
            </div>
        </div>
        <div class="faq-edit d-none py-3 border-bottom">
            <div class="row g-2">
                <div class="col-12 col-sm-3">
                    <select name="language_id" class="input-miticko">${$('#form-faq-new select[name="language_id"]').html()}</select>
                </div>
                <div class="col-12">
                    <input name="question" class="input-miticko" value="${faq.question}">
                </div>
                <div class="col-12">
                    <textarea name="answer" class="input-miticko" rows="3">${faq.answer}</textarea>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn-faq-save btn-miticko primary success small"><i class="fa-regular fa-check icon"></i> Salva</button>
                    <button class="btn-faq-cancel btn-miticko outlined secondary small"><i class="fa-regular fa-xmark icon"></i> Annulla</button>
                </div>
            </div>
        </div>
    </div>`;

const initFaqs = () => {
    const syncLangSelect = ($item, langId) => {
        $item.find('.faq-edit select[name="language_id"]').val(langId);
    };

    $(document).on('click', '.btn-faq-edit', function () {
        const $item = $(this).closest('.faq-item');
        $item.find('.faq-display').addClass('d-none');
        $item.find('.faq-edit').removeClass('d-none');
        syncLangSelect($item, $item.data('lang-id'));
    });

    $(document).on('click', '.btn-faq-cancel', function () {
        const $item = $(this).closest('.faq-item');
        $item.find('.faq-edit').addClass('d-none');
        $item.find('.faq-display').removeClass('d-none');
    });

    $(document).on('click', '.btn-faq-save', function () {
        const $item = $(this).closest('.faq-item');
        const id = $item.data('id');
        const data = {
            language_id: $item.find('.faq-edit select[name="language_id"]').val(),
            question:    $item.find('.faq-edit input[name="question"]').val(),
            answer:      $item.find('.faq-edit textarea[name="answer"]').val(),
        };
        App.ajax({ path: `/backoffice/products/${window.PRODUCT_ID}/faqs/${id}`, method: 'put', data })
            .then((faq) => {
                const $new = $(renderFaqRow(faq));
                $new.data('lang-id', faq.language_id);
                $item.replaceWith($new);
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

    $(document).on('click', '#btn-faq-add', function () {
        const $form = $('#form-faq-new');
        const data = {
            language_id: $form.find('select[name="language_id"]').val(),
            question:    $form.find('input[name="question"]').val(),
            answer:      $form.find('textarea[name="answer"]').val(),
        };
        if (!data.question || !data.answer) {
            toastr.warning('Domanda e risposta sono obbligatorie');
            return;
        }
        App.ajax({ path: `/backoffice/products/${window.PRODUCT_ID}/faqs`, method: 'post', data })
            .then((faq) => {
                $('#faqs-empty').remove();
                const $row = $(renderFaqRow(faq));
                $row.data('lang-id', faq.language_id);
                $('#faqs-list').append($row);
                $form[0].reset();
                toastr.success('FAQ aggiunta');
            })
            .catch(() => toastr.error('Errore durante il salvataggio'));
    });
};

const initCustomerFields = () => {
    // Abilita/disabilita il toggle "Obbligatorio" in base alla spunta principale
    $(document).on('change', '.customer-field-enabled', function () {
        const $wrap = $(this).closest('.customer-field-item').find('.customer-field-required-wrap');
        if ($(this).is(':checked')) {
            $wrap.css({ opacity: 1, 'pointer-events': 'auto' });
        } else {
            $wrap.css({ opacity: 0.35, 'pointer-events': 'none' });
            $wrap.find('.customer-field-required').prop('checked', false);
        }
    });

    // Salva la configurazione
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

const MAX_RELATED = 5;

const renderRelatedRow = (related) => `
    <div class="related-item d-flex align-items-center gap-3 py-2 border-bottom" data-id="${related.id}">
        <span class="fw-semibold flex-grow-1">${related.label}</span>
        <span class="text-secondary small">${related.product_code ?? ''}</span>
        <button class="btn-related-delete btn-miticko outlined danger small"><i class="fa-regular fa-trash icon"></i></button>
    </div>`;

const initRelated = () => {
    let searchTimeout = null;
    let selectedProduct = null;

    // Ricerca prodotti al typing
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
                            `<button type="button" class="list-group-item list-group-item-action btn-related-pick" data-id="${p.id}" data-label="${p.label}" data-code="${p.product_code ?? ''}">
                                <span class="fw-semibold">${p.label}</span>
                                <span class="text-secondary small ms-2">${p.product_code ?? ''}</span>
                            </button>`
                        );
                    });
                }
                $results.show();
            }).catch(() => {});
        }, 300);
    });

    // Selezione da dropdown
    $(document).on('click', '.btn-related-pick', function () {
        selectedProduct = {
            id:    $(this).data('id'),
            label: $(this).data('label'),
            code:  $(this).data('code'),
        };
        $('#related-search-input').val(selectedProduct.label);
        $('#related-search-results').hide().empty();
    });

    // Chiudi dropdown cliccando fuori
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#related-search-input, #related-search-results').length) {
            $('#related-search-results').hide();
        }
    });

    // Aggiungi prodotto correlato
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
            const msg = err?.responseJSON?.message || 'Errore durante il salvataggio';
            toastr.error(msg);
        });
    });

    // Elimina prodotto correlato
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
                    const msg = err?.responseJSON?.message || 'Errore durante l\'eliminazione';
                    toastr.error(msg);
                });
            },
            null,
            'Elimina prodotto'
        );
    });
};

const init = () => {
    $(document).on('click', '.btn-save-card', function () {
        const card = $(this).closest('.card-miticko');
        const form = card.find('form');
        if (!form.length) return;
        saveForm(form.attr('id'), $(this));
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
