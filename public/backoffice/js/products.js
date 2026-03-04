import App from "./app.js";
import {
    ClassicEditor, Essentials, Paragraph,
    Bold, Italic, Underline, Strikethrough, RemoveFormat,
    List, Link, Heading,
} from 'ckeditor5';

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
    'form-info-description': {
        endpoint: () => `/backoffice/products/${window.PRODUCT_ID}`,
        method: 'put',
        section: 'description',
        successMessage: 'Descrizione aggiornata con successo',
        validate: () => ({}),
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
// FAQ — CKEditor instances
// ---------------------------------------------------------------------------
const faqEditors = new Map(); // key: 'new' | String(faqId)

const faqEditorConfig = {
    plugins: [Essentials, Paragraph, Bold, Italic, Underline, Strikethrough, RemoveFormat, List, Link, Heading],
    toolbar: { items: ['heading', '|', 'bold', 'italic', 'underline', 'strikethrough', 'removeFormat', '|', 'bulletedList', 'numberedList', '|', 'link'] },
    licenseKey: 'GPL',
};

const initFaqEditor = async (key, textarea) => {
    if (faqEditors.has(key)) {
        await faqEditors.get(key).destroy();
        faqEditors.delete(key);
    }
    const editor = await ClassicEditor.create(textarea, faqEditorConfig);
    faqEditors.set(key, editor);
    if (key !== 'new') {
        editor.model.document.on('change:data', () => {
            $(`#faqs-list .faq-item[data-id="${key}"]`).attr('data-dirty', '1');
            updateSaveFaqsButton();
        });
    }
    return editor;
};

const initAllFaqEditors = () => {
    const newTextarea = document.querySelector('#form-faq-new textarea[name="answer"]');
    if (newTextarea) initFaqEditor('new', newTextarea);

    document.querySelectorAll('#faqs-list .faq-item').forEach(item => {
        const key = String($(item).data('id'));
        const textarea = item.querySelector('textarea[name="answer"]');
        if (textarea) initFaqEditor(key, textarea);
    });
};

// ---------------------------------------------------------------------------
// FAQ
// ---------------------------------------------------------------------------
const renderFaqRow = (faq) => {
    const question = escapeHtml(faq.question);
    const answer   = escapeHtml(faq.answer);
    return `
    <div class="faq-item py-1" data-id="${faq.id}">
        <div class="row g-2 align-items-start">
            <div class="col-12 col-sm-4">
                <div class="text-field" data-mode="medium">
                    <div class="text-field-container">
                        <input class="input-miticko" name="question" value="${question}">
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-7">
                <div class="text-field" data-mode="medium">
                    <div class="text-field-container">
                        <textarea class="input-miticko" name="answer" rows="2">${answer}</textarea>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-1 d-flex gap-1 align-items-end justify-content-end pb-1">
                <button type="button" data-mode="medium primary" class="bt-miticko btn-faq-delete bt-m-text-only"><i class="fa-regular fa-trash icon"></i></button>
            </div>
        </div>
    </div>`;
};

const updateSaveFaqsButton = () => {
    const hasDirty = $('#faqs-list .faq-item[data-dirty]').length > 0;
    if (hasDirty) {
        $('.btn-save-faq').attr('data-mode', 'medium primary').removeClass('btn-m-primary').addClass('btn-m-default');
    } else {
        $('.btn-save-faq').attr('data-mode', 'medium disabled').removeClass('btn-m-default').addClass('btn-m-primary');
    }
};

const addFaq = () => {
    const $form     = $('#form-faq-new');
    const langId    = parseInt($('#faq-language-select').val());
    const data = {
        question:    $form.find('input[name="question"]').val(),
        answer:      faqEditors.get('new')?.getData() ?? '',
        language_id: langId,
    };
    if (!data.question || !data.answer) {
        toastr.warning('Domanda e risposta sono obbligatorie');
        return;
    }
    App.ajax({ path: `/backoffice/products/${window.PRODUCT_ID}/faqs`, method: 'post', data })
        .then((faq) => {
            $('#faqs-empty').remove();
            $('#faqs-list').append(renderFaqRow(faq));
            const newItem = document.querySelector(`#faqs-list .faq-item[data-id="${faq.id}"]`);
            if (newItem) initFaqEditor(String(faq.id), newItem.querySelector('textarea[name="answer"]'));
            $form.find('input[name="question"]').val('');
            faqEditors.get('new')?.setData('');
            toastr.success('FAQ aggiunta');
        })
        .catch(() => toastr.error('Errore durante il salvataggio'));
};

const initFaqs = () => {
    // Dirty tracking — solo sul campo question (answer è gestito da CKEditor)
    $(document).on('input', '.faq-item input[name="question"]', function () {
        $(this).closest('.faq-item').attr('data-dirty', '1');
        updateSaveFaqsButton();
    });

    // Salva tutte le righe modificate
    $(document).on('click', '.btn-save-faq', function () {
        const $btn = $(this);
        const $dirtyItems = $('#faqs-list .faq-item[data-dirty]');
        if ($dirtyItems.length === 0) return;

        const langId = parseInt($('#faq-language-select').val());

        setLoading($btn, true);
        let pending = $dirtyItems.length;
        let hasError = false;

        $dirtyItems.each(function () {
            const $item = $(this);
            const id = $item.data('id');
            const question = $item.find('input[name="question"]').val();
            const answer   = faqEditors.get(String(id))?.getData() ?? '';

            App.ajax({
                path: `/backoffice/products/${window.PRODUCT_ID}/faqs/${id}`,
                method: 'put',
                data: { question, answer, language_id: langId },
            })
                .then(() => { $item.removeAttr('data-dirty'); })
                .catch(() => { hasError = true; })
                .finally(() => {
                    pending--;
                    if (pending === 0) {
                        setLoading($btn, false);
                        if (hasError) {
                            toastr.error('Alcune FAQ non sono state salvate. Riprova.');
                        } else {
                            toastr.success('FAQ aggiornate con successo');
                        }
                        updateSaveFaqsButton();
                    }
                });
        });
    });

    // Cambio lingua — sempre AJAX, italiano incluso
    // $(document).on('change', '#faq-language-select', function () {
    //     const langId = parseInt($(this).val());
    //
    //     $('#faqs-list .faq-item').each(function () {
    //         const $item = $(this);
    //         const id = $item.data('id');
    //         App.ajax({ path: `/backoffice/products/${window.PRODUCT_ID}/faqs/${id}/translations`, method: 'get' })
    //             .then((res) => {
    //                 const lang = res.data.find(l => l.language_id === langId);
    //                 $item.find('input[name="question"]').val(lang ? lang.question : '');
    //                 faqEditors.get(String(id))?.setData(lang ? lang.answer : '');
    //                 $item.removeAttr('data-dirty');
    //                 updateSaveFaqsButton();
    //             })
    //             .catch(() => toastr.error('Errore nel caricamento delle traduzioni'));
    //     });
    // });

    // Elimina FAQ
    $(document).on('click', '.btn-faq-delete', function () {
        const $item = $(this).closest('.faq-item');
        const id = $item.data('id');
        App.sweetConfirm('Vuoi eliminare questa FAQ?', () => {
            App.ajax({ path: `/backoffice/products/${window.PRODUCT_ID}/faqs/${id}`, method: 'delete' })
                .then(() => {
                    faqEditors.get(String(id))?.destroy();
                    faqEditors.delete(String(id));
                    $item.remove();
                    if ($('#faqs-list .faq-item').length === 0) {
                        $('#faqs-list').prepend('<p class="text-secondary small mb-0" id="faqs-empty">Nessuna FAQ aggiunta.</p>');
                    }
                    toastr.success('FAQ eliminata');
                })
                .catch(() => toastr.error('Errore durante l\'eliminazione'));
        }, null, 'Elimina FAQ');
    });

    // Aggiungi FAQ
    $(document).on('click', '.btn-link-faq', addFaq);

    // Init editors su tutti i textarea esistenti
    initAllFaqEditors();
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
            btn.attr('data-mode', 'medium disabled');
        }).catch(() => {
            toastr.error('Errore durante il salvataggio');
            setLoading(btn, false);
        });
    });
};

// ---------------------------------------------------------------------------
// Prodotti correlati
// ---------------------------------------------------------------------------
const initRelated = () => {
    $(document).on('change', '.related-slot-select', function () {
        $('.btn-save-related').attr('data-mode', 'medium primary');
    });

    $(document).on('click', '.btn-save-related', function () {
        const $btn = $(this);
        const relatedIds = $('.related-slot-select').map(function () {
            const val = $(this).val();
            return val ? parseInt(val) : null;
        }).get();

        setLoading($btn, true);
        App.ajax({
            path: `/backoffice/products/${window.PRODUCT_ID}/related`,
            method: 'put',
            data: { related_ids: relatedIds },
        }).then(() => {
            toastr.success('Prodotti correlati salvati');
            $('.btn-save-related').attr('data-mode', 'medium disabled');
        }).catch((err) => {
            toastr.error(err?.responseJSON?.message || 'Errore durante il salvataggio');
        }).finally(() => setLoading($btn, false));
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
    // Dirty tracking sui form delle card — attiva il btn-save-card quando un campo cambia
    $(document).on('input change', 'form:not(#form-customer-fields) :input:not([disabled])', function () {
        $(this).closest('.card-miticko').find('.btn-save-card').attr('data-mode', 'medium primary');
    });

    // Dirty tracking per Dati cliente
    $(document).on('change', '#form-customer-fields :input', function () {
        $('.btn-save-customer-fields').attr('data-mode', 'medium primary');
    });

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
