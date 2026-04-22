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
        endpoint: () => `/products/${window.PRODUCT_ID}`,
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
        endpoint: () => `/products/${window.PRODUCT_ID}`,
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
        endpoint: () => `/products/${window.PRODUCT_ID}`,
        method: 'put',
        section: 'categories',
        successMessage: 'Categoria aggiornata con successo',
        validate: () => ({}),
    },
    'form-info-features': {
        endpoint: () => `/products/${window.PRODUCT_ID}`,
        method: 'put',
        section: 'features',
        successMessage: 'Caratteristiche aggiornate con successo',
        validate: () => ({}),
        collect: () => ({
            features: $('#form-info-features input[name="features"]:checked')
                .map(function () { return $(this).val(); })
                .get(),
        }),
    },
    'form-info-public': {
        endpoint: () => `/products/${window.PRODUCT_ID}`,
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
        endpoint: () => `/products/${window.PRODUCT_ID}`,
        method: 'put',
        section: 'description',
        successMessage: 'Descrizione aggiornata con successo',
        validate: () => ({}),
    },
    'form-variants-occupancy': {
        endpoint: () => `/products/${window.PRODUCT_ID}`,
        method: 'put',
        section: 'occupancy',
        successMessage: 'Capienza aggiornata con successo',
        validate: (data) => {
            const errors = {};
            if (!data.occupancy || parseInt(data.occupancy) < 1) {
                errors.occupancy = ['Inserisci una capienza valida'];
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
    variant: [
        { name: 'label',       label: 'Nome variante',   type: 'input',    placeholder: 'es. Intero' },
        { name: 'description', label: 'Descrizione breve', type: 'input',  placeholder: 'es. Biglietto intero per adulti' },
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
        return '<p class="text-secondary small  mb-0">Nessuna lingua disponibile nel sistema.</p>';
    }


    const headerRow = `<div class="row g-2 align-items-center mb-1">
        <div class="col-auto" style="width:64px"></div>
        <div class="col"><span class="small fw-semibold">Nome variante *</span></div>
        <div class="col"><span class="small fw-semibold">Descrizione breve *</span></div>
    </div>`;

    const rowsHtml = data.map(lang => {
        const fieldsHtml = fields.map(f => {
            const value = escapeHtml(lang[f.name] || '');
            if (f.type === 'textarea') {
                return `<div class="col">
                    <div class="text-field"
                        <div class="text-field-container">
                            <textarea class="input-miticko" name="${f.name}" rows="2" placeholder="${f.placeholder || ''}">${value}</textarea>
                        </div>
                    </div>
                </div>`;
            }
            return `<div class="col" style="${f.name == 'label' ? 'max-width:35%' : ''}">
                <div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting" >
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
    const paths = {
        link:    `/products/${window.PRODUCT_ID}/links/${id}/translations`,
        faq:     `/products/${window.PRODUCT_ID}/faqs/${id}/translations`,
        variant: `/products/${window.PRODUCT_ID}/variants/${id}/translations`,
    };
    const path = paths[entityType];

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
            setTimeout(() => {
                $modal.modal('hide');
            }, 1500)
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
    App.ajax({ path: `/products/${window.PRODUCT_ID}/links`, method: 'post', data })
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
            App.ajax({ path: `/products/${window.PRODUCT_ID}/links/${id}`, method: 'put', data })
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
            App.ajax({ path: `/products/${window.PRODUCT_ID}/links/${id}`, method: 'delete' })
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
                <div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting">
                    <div class="text-field-container">
                        <input class="input-miticko" name="question" value="${question}">
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-7">
                <div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting">
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
        $('.btn-save-faq').attr('data-mode', 'buttonSize-Medium buttonEmphasis-Medium buttonAppearance-Primary').removeClass('btn-m-primary').addClass('btn-m-default');
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
    App.ajax({ path: `/products/${window.PRODUCT_ID}/faqs`, method: 'post', data })
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
                path: `/products/${window.PRODUCT_ID}/faqs/${id}`,
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
    //         App.ajax({ path: `/products/${window.PRODUCT_ID}/faqs/${id}/translations`, method: 'get' })
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
            App.ajax({ path: `/products/${window.PRODUCT_ID}/faqs/${id}`, method: 'delete' })
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
            path: `/products/${window.PRODUCT_ID}/customer-fields/sync`,
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
        $('.btn-save-related').attr('data-mode', 'buttonSize-Medium buttonEmphasis-Medium buttonAppearance-Primary');
    });

    $(document).on('click', '.btn-save-related', function () {
        const $btn = $(this);
        const relatedIds = $('.related-slot-select').map(function () {
            const val = $(this).val();
            return val ? parseInt(val) : null;
        }).get();

        setLoading($btn, true);
        App.ajax({
            path: `/products/${window.PRODUCT_ID}/related`,
            method: 'put',
            data: { related_ids: relatedIds },
        }).then(() => {
            toastr.success('Prodotti correlati salvati');
            $('.btn-save-related').attr('data-mode', 'buttonSize-Medium buttonEmphasis-Medium buttonAppearance-Disabled');
        }).catch((err) => {
            toastr.error(err?.responseJSON?.message || 'Errore durante il salvataggio');
        }).finally(() => setLoading($btn, false));
    });
};

// ---------------------------------------------------------------------------
// Varianti — lista (edit inline, delete, reorder)
// ---------------------------------------------------------------------------
const editPriceRowTemplate = () => `
<div class="edit-price-row d-flex align-items-center gap-2 mb-2">
    <div class="flex-grow-1">
        <div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting">
            <div class="text-field-container">
                <input class="input-miticko " name="price_label[]" type="text" placeholder="es. Visita">
            </div>
        </div>
    </div>
    <div style="width:180px;flex-shrink:0">
        <div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting">
            <div class="text-field-container">
                <input class="input-miticko " name="price_value[]" type="number" min="0" step="0.01" placeholder="0.00">
                <i class="extra">EUR</i>
            </div>
        </div>
    </div>
    <div style="width:150px;flex-shrink:0">
        <div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting">
            <div class="text-field-container">
                <select class="input-miticko" name="price_vat[]">
                    <option value="0" selected="">Esente</option>
                    <option value="4">4%</option>
                    <option value="5">5%</option>
                    <option value="10">10%</option>
                    <option value="22">22%</option>
                </select>
            </div>
        </div>
    </div>
    <div style="width:40px;flex-shrink:0">
        <button type="button" class="bt-miticko outlined danger small btn-edit-remove-price">
            <i class="fa-regular fa-trash-can icon"></i>
        </button>
    </div>
</div>`;

const openVariantEdit = ($item) => {
    $item.find('.variant-header').addClass('border-bottom');
    $item.find('.variant-edit-panel').removeClass('d-none');
    $item.find('.btn-variant-toggle i').attr('class', 'fa-regular fa-chevron-up icon');
    $item.find('.btn-variant-delete').addClass('d-none');
};

const closeVariantEdit = ($item) => {
    $item.find('.variant-header').removeClass('border-bottom');
    $item.find('.variant-edit-panel').addClass('d-none');
    $item.find('.btn-variant-toggle i').attr('class', 'fa-regular fa-chevron-down icon');
    $item.find('.btn-variant-delete').removeClass('d-none');
};

const initVariants = () => {
    let _sortable = null;

    const initSortable = () => {
        const el = document.getElementById('sortable-variants');
        if (!el) return;
        if (_sortable) { _sortable.destroy(); _sortable = null; }
        _sortable = Sortable.create(el, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd() {
                const ids = [...el.querySelectorAll('.variant-item[data-id]')]
                    .map(el => parseInt(el.dataset.id));
                App.ajax({
                    path: `/products/${window.PRODUCT_ID}/variants/reorder`,
                    method: 'post',
                    data: { ordered_ids: ids },
                }).catch(() => toastr.error('Errore durante il riordinamento'));
            },
        });
    };

    initSortable();

    $(document).on('click', '.btn-variant-toggle', function () {
        const $item = $(this).closest('.variant-item');
        const isOpen = !$item.find('.variant-edit-panel').hasClass('d-none');
        isOpen ? closeVariantEdit($item) : openVariantEdit($item);
    });

    $(document).on('click', '.btn-variant-cancel', function () {
        closeVariantEdit($(this).closest('.variant-item'));
    });

    $(document).on('click', '.btn-variant-translations', function () {
        const id = $(this).closest('.variant-item').data('id');
        openTranslationsModal('variant', id);
    });

    $(document).on('click', '.btn-slot-variant-translations', function () {
        const id = $(this).data('id');
        openTranslationsModal('variant', id);
    });

    $(document).on('click', '.btn-variant-delete', function () {
        const $item = $(this).closest('.variant-item');
        const id = $item.data('id');
        const label = $item.find('.variant-label-text').text();
        App.sweetConfirm(`Eliminare la variante "${label}" e tutte le sue componenti IVA?`, () => {
            App.ajax({
                path: `/products/${window.PRODUCT_ID}/variants/${id}`,
                method: 'delete',
            }).then(() => {
                $item.remove();
                if ($('#sortable-variants .variant-item').length === 0) {
                    $('#sortable-variants').html('<p class="text-secondary small mb-4" id="variants-empty">Nessuna variante aggiunta.</p>');
                }
                toastr.success('Variante eliminata');
            }).catch(() => toastr.error('Errore durante l\'eliminazione'));
        }, null, 'Elimina variante');
    });

    $(document).on('click', '.btn-edit-add-price', function () {
        $(this).closest('.variant-edit-panel').find('.variant-edit-prices').append(editPriceRowTemplate());
    });

    $(document).on('click', '.btn-edit-remove-price', function () {
        const $prices = $(this).closest('.variant-edit-prices');
        if ($prices.find('.edit-price-row').length > 1) {
            $(this).closest('.edit-price-row').remove();
        }
    });

    $(document).on('click', '.btn-variant-save', function () {
        const $btn = $(this);
        const $item = $btn.closest('.variant-item');
        const $panel = $item.find('.variant-edit-panel');
        const id = $item.data('id');

        const label = $panel.find('[name="edit_label"]').val().trim();
        if (!label) { toastr.warning('Il nome variante è obbligatorio'); return; }

        const prices = [];
        let valid = true;
        $panel.find('.edit-price-row').each(function () {
            const priceLabel = $(this).find('[name="price_label[]"]').val().trim();
            const price      = $(this).find('[name="price_value[]"]').val().trim();
            if (!priceLabel || price === '') { valid = false; return false; }
            const priceId = $(this).data('price-id') || undefined;
            prices.push({
                ...(priceId && { id: priceId }),
                label: priceLabel,
                price: parseFloat(price),
                vat_rate: parseFloat($(this).find('[name="price_vat[]"]').val()),
            });
        });

        if (!valid) { toastr.warning('Compila tutti i campi delle componenti IVA'); return; }

        setLoading($btn, true);
        App.ajax({
            path: `/products/${window.PRODUCT_ID}/variants/${id}`,
            method: 'put',
            data: {
                label,
                description:  $panel.find('[name="edit_description"]').val().trim() || null,
                max_quantity: $panel.find('[name="edit_max_quantity"]').val() || null,
                prices,
            },
        }).then(() => {
            $item.find('.variant-label-text').text(label);
            const count = prices.length;
            $item.find('.variant-prices-count').text(count + (count === 1 ? ' componente IVA' : ' componenti IVA'));
            closeVariantEdit($item);
            toastr.success('Variante aggiornata');
        }).catch(err => {
            toastr.error(err?.responseJSON?.message || 'Errore durante il salvataggio');
        }).finally(() => setLoading($btn, false));
    });
};

// ---------------------------------------------------------------------------
// Varianti (modal aggiungi)
// ---------------------------------------------------------------------------
const priceRowTemplate = () => `
<div class="mb-2 variant-price-row">
    <div class="d-flex align-items-end gap-2">
        <div class="flex-grow-1">
            <div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting">
                <label>Servizio * </label>
                <div class="text-field-container">
                    <input class="input-miticko " name="price_label[]" id="variant_label" type="text" placeholder="es. Adulto" required="">
                </div>
            </div>
        </div>
        <div style="width:200px;flex-shrink:0">
            <div class="text-field" data-mode="medium">
                <label>Prezzo al pubblico *</label>
                <div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting">
                    <div class="text-field-container">
                        <input type="number" min="0" step="0.01" class="input-miticko" name="price_value[]" placeholder="0.00" style="min-width:0">
                        <i class="extra">EUR</i>
                    </div>
                </div>
            </div>
        </div>
        <div style="width:150px;flex-shrink:0">
            <div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting">
                <label>IVA *</label>
                <div class="text-field-container">
                    <select  class="input-miticko" name="price_vat[]">
                        <option value="">Scegli</option>
                        <option value="0">Esente</option>
                        <option value="4">4%</option>
                        <option value="5">5%</option>
                        <option value="10">10%</option>
                        <option value="22">22%</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>`;

const initVariantModal = () => {
    const $modal = $('#modal-add-variant');

    $modal.on('show.bs.modal', () => {
        if ($('#variant-prices-list .variant-price-row').length === 0) {
            $('#variant-prices-list').html(priceRowTemplate());
        }
    });

    $modal.on('hidden.bs.modal', () => {
        $('#variant_label').val('');
        $('#variant_description').val('');
        $('#variant_max_quantity').val('');
        $('#variant-prices-list').empty();
    });

    $(document).on('click', '#btn-add-price-row', () => {
        $('#variant-prices-list').append(priceRowTemplate());
    });

    $(document).on('click', '.btn-remove-price-row', function () {
        if ($('#variant-prices-list .variant-price-row').length > 1) {
            $(this).closest('.variant-price-row').remove();
        }
    });

    $(document).on('click', '#btn-create-variant', function () {
        const $btn = $(this);
        const label = $('#variant_label').val().trim();

        if (!label) {
            toastr.warning('Il nome variante è obbligatorio');
            return;
        }

        const prices = [];
        let valid = true;

        $('#variant-prices-list .variant-price-row').each(function () {
            const priceLabel = $(this).find('input[name="price_label[]"]').val().trim();
            const price      = $(this).find('input[name="price_value[]"]').val().trim();
            const vatRate    = $(this).find('select[name="price_vat[]"]').val();

            if (!priceLabel || price === '') {
                valid = false;
                return false;
            }
            prices.push({ label: priceLabel, price: parseFloat(price), vat_rate: parseFloat(vatRate) });
        });

        if (!valid) {
            toastr.warning('Compila tutti i campi delle componenti IVA');
            return;
        }

        setLoading($btn, true);

        App.ajax({
            path: `/products/${window.PRODUCT_ID}/variants`,
            method: 'post',
            data: {
                label,
                description:  $('#variant_description').val().trim() || null,
                max_quantity: $('#variant_max_quantity').val() || null,
                prices,
            },
        }).then(() => {
            toastr.success('Variante aggiunta con successo');
            $modal.modal('hide');
            setTimeout(() => {
                window.location.hash = 'variants-panel';
                window.location.reload();
            }, 800);
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
                    path: `/products/${window.PRODUCT_ID}`,
                    method: 'delete',
                }).then((res) => {
                    toastr.success('Prodotto eliminato con successo');
                    setTimeout(() => {
                        window.location.href = res.redirect ?? '/products';
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
// Media gallery
// ---------------------------------------------------------------------------
const initMediaGallery = () => {
    const el = document.getElementById('media-list');
    if (!el) return;

    Sortable.create(el, {
        handle: '.drag-handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
    });

    $(document).on('click', '.btn-add-media', () => {
        $('#media-file-input').val('').trigger('click');
    });

    $(document).on('change', '#media-file-input', function () {
        const file = this.files[0];
        if (!file) return;

        const $btn = $('.btn-add-media');
        setLoading($btn, true);

        const fd = new FormData();
        fd.append('image', file);

        $.ajax({
            url: `/products/${window.PRODUCT_ID}/media`,
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
        }).then((media) => {
            $('#media-empty').remove();
            const html =
                `<div class="media-item d-flex align-items-center gap-3 py-2 border-bottom" data-id="${media.id}">` +
                    `<span class="drag-handle text-secondary" style="cursor:grab;font-size:18px;line-height:1">⠿</span>` +
                    `<img src="${media.url}" alt="${media.file_name}" style="width:56px;height:56px;object-fit:cover;border-radius:6px;flex-shrink:0">` +
                    `<span class="flex-grow-1 small text-truncate">${media.file_name}</span>` +
                    `<button type="button" class="bt-miticko btn-media-delete" data-mode="small primary bt-m-text-only"><i class="fa-regular fa-trash icon"></i></button>` +
                `</div>`;
            $('#media-list').append(html);
            toastr.success('Immagine aggiunta');
        }).catch((err) => {
            toastr.error(err?.responseJSON?.message || 'Errore durante il caricamento');
        }).always(() => {
            setLoading($btn, false);
        });
    });

    $(document).on('click', '.btn-save-media-order', function () {
        const $btn = $(this);
        const ids = [...document.querySelectorAll('#media-list .media-item[data-id]')]
            .map(el => parseInt(el.dataset.id));

        setLoading($btn, true);
        App.ajax({
            path: `/products/${window.PRODUCT_ID}/media/reorder`,
            method: 'post',
            data: { ordered_ids: ids },
        }).then(() => {
            toastr.success('Ordine salvato');
        }).catch(() => {
            toastr.error('Errore durante il salvataggio');
        }).finally(() => setLoading($btn, false));
    });

    $(document).on('click', '.btn-media-delete', function () {
        const $item = $(this).closest('.media-item');
        const id    = $item.data('id');
        App.sweetConfirm('Vuoi eliminare questa immagine?', () => {
            App.ajax({ path: `/products/${window.PRODUCT_ID}/media/${id}`, method: 'delete' })
                .then(() => {
                    $item.remove();
                    if ($('#media-list .media-item').length === 0) {
                        $('#media-list').html('<p class="text-secondary small mb-0" id="media-empty">Nessuna immagine aggiunta.</p>');
                    }
                    toastr.success('Immagine eliminata');
                })
                .catch(() => toastr.error('Errore durante l\'eliminazione'));
        }, null, 'Elimina immagine');
    });
};

// ---------------------------------------------------------------------------
// Long description — CKEditor
// ---------------------------------------------------------------------------
let _longDescEditor = null;
let _longDescLangId = null;

const initLongDescription = async () => {
    const textarea = document.getElementById('long-description-editor');
    if (!textarea) return;

    // Leggi la prima lingua disponibile
    const $select = $('#long-desc-language-select');
    _longDescLangId = $select.length ? parseInt($select.val()) : null;

    _longDescEditor = await ClassicEditor.create(textarea, faqEditorConfig);

    // Precompila con il valore IT dal data attribute
    const itValue = textarea.dataset.it || '';
    _longDescEditor.setData(itValue);
    $('#long-desc-count').text(_longDescEditor.getData().replace(/<[^>]*>/g, '').length);

    _longDescEditor.model.document.on('change:data', () => {
        const len = _longDescEditor.getData().replace(/<[^>]*>/g, '').length;
        $('#long-desc-count').text(len);
        $('.btn-save-long-description').attr('data-mode', 'buttonSize-Medium buttonEmphasis-Medium buttonAppearance-Primary');
    });

    // Cambio lingua — carica via AJAX
    $(document).on('change', '#long-desc-language-select', function () {
        _longDescLangId = parseInt($(this).val());
        App.ajax({
            path: `/products/${window.PRODUCT_ID}/long-description`,
            method: 'get',
            data: { language_id: _longDescLangId },
        }).then((res) => {
            _longDescEditor.setData(res.long_description || '');
        }).catch(() => toastr.error('Errore nel caricamento della descrizione'));
    });

    // Salva
    $(document).on('click', '.btn-save-long-description', function () {
        const $btn = $(this);
        if (!_longDescEditor) return;

        setLoading($btn, true);
        App.ajax({
            path: `/products/${window.PRODUCT_ID}`,
            method: 'put',
            data: {
                section: 'long_description',
                language_id: _longDescLangId,
                long_description: _longDescEditor.getData(),
            },
        }).then(() => {
            toastr.success('Descrizione salvata con successo');
            $('.btn-save-long-description').attr('data-mode', 'buttonSize-Medium buttonEmphasis-Medium buttonAppearance-Disabled');
        }).catch(() => {
            toastr.error('Errore durante il salvataggio');
        }).finally(() => setLoading($btn, false));
    });
};

// ---------------------------------------------------------------------------
// Init
// ---------------------------------------------------------------------------
const init = () => {
    // Dirty tracking sui form delle card — attiva il btn-save-card quando un campo cambia
    $(document).on('input change', 'form:not(#form-customer-fields) :input:not([disabled])', function () {
        $(this).closest('.card-miticko').find('.btn-save-card').attr('data-mode', 'buttonSize-Medium buttonEmphasis-Medium buttonAppearance-Primary');
    });

    // Dirty tracking per Dati cliente
    $(document).on('change', '#form-customer-fields :input', function () {
        $('.btn-save-customer-fields').attr('data-mode', 'buttonSize-Medium buttonEmphasis-Medium buttonAppearance-Primary');
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
    initVariants();
    initVariantModal();
    initPriceVariations();
    initMediaGallery();
    initLongDescription();
};

$(function () {
    init();
});

// ─── Price Variations ────────────────────────────────────────────────────────

const renderPriceVariationRow = (v) => `
    <div class="d-flex align-items-center price-variation-item"
         data-id="${v.id}"
         data-date-from="${v.date_from_iso}"
         data-date-to="${v.date_to_iso}"
         data-direction="${v.direction}"
         data-value="${v.value}"
         data-unit="${v.unit}">
        <b>${v.date_from} → ${v.date_to}</b>
        <span>${v.direction_label} ${parseFloat(v.value).toFixed(2)} ${v.unit_label}</span>
        <button type="button" class="bt-miticko outlined Primary small btn-edit-price-variation ms-auto">
            <i class="fa-regular fa-pen icon"></i>
        </button>
        <button type="button" class="bt-miticko outlined danger small btn-delete-price-variation">
            <i class="fa-regular fa-trash-can icon"></i>
        </button>
    </div>`;

const initPriceVariations = () => {
    const $modal   = $('#modal-price-variation');
    let _fp        = null;

    const openModal = (variation = null) => {
        $('#pv-variation-id').val(variation ? variation.id : '');
        $('#modal-price-variation .modal-title').text(variation ? 'Modifica personalizzazione' : 'Modifica intero periodo');
        $('#btn-create-price-variation').text(variation ? 'Salva modifiche' : 'Crea personalizzazione');

        if (variation) {
            $('#pv-date-from').val(variation.dateFrom);
            $('#pv-date-to').val(variation.dateTo);
            const fmt = (iso) => {
                const d = new Date(iso);
                return d.toLocaleDateString('it-IT', { day: 'numeric', month: 'long', year: 'numeric' });
            };
            $('#pv-date-from-label').text(fmt(variation.dateFrom));
            $('#pv-date-to-label').text(fmt(variation.dateTo));
            $('#pv_direction').val(variation.direction);
            $('#pv_increment').val(variation.value);
            $('#pv_unit').val(variation.unit);
        } else {
            $('#pv-date-from').val('');
            $('#pv-date-to').val('');
            $('#pv-date-from-label').text('—');
            $('#pv-date-to-label').text('—');
            $('#pv_direction').val('increment');
            $('#pv_increment').val('');
            $('#pv_unit').val('euro');
        }

        $modal.modal('show');
    };

    $(document).on('click', '.btn-add-prices-editing', () => openModal());

    $(document).on('click', '.btn-edit-price-variation', function () {
        const $item = $(this).closest('.price-variation-item');
        openModal({
            id:        $item.data('id'),
            dateFrom:  $item.data('date-from'),
            dateTo:    $item.data('date-to'),
            direction: $item.data('direction'),
            value:     $item.data('value'),
            unit:      $item.data('unit'),
        });
    });

    $modal.on('shown.bs.modal', () => {
        if (_fp) { _fp.destroy(); _fp = null; }
        const defaultDates = [];
        const from = $('#pv-date-from').val();
        const to   = $('#pv-date-to').val();
        if (from) defaultDates.push(from);
        if (to)   defaultDates.push(to);

        _fp = flatpickr('#pv-flatpickr-input', {
            mode: 'range',
            locale: 'it',
            dateFormat: 'Y-m-d',
            defaultDate: defaultDates,
            onChange(dates) {
                if (dates.length === 2) {
                    const fmt = (d) => d.toLocaleDateString('it-IT', { day: 'numeric', month: 'long', year: 'numeric' });
                    $('#pv-date-from').val(dates[0].toISOString().slice(0,10));
                    $('#pv-date-to').val(dates[1].toISOString().slice(0,10));
                    $('#pv-date-from-label').text(fmt(dates[0]));
                    $('#pv-date-to-label').text(fmt(dates[1]));
                }
            },
        });
    });

    $(document).on('click', '#btn-pv-open-picker', () => {
        _fp?.open();
    });

    $(document).on('click', '#btn-create-price-variation', function () {
        const dateFrom  = $('#pv-date-from').val();
        const dateTo    = $('#pv-date-to').val();
        const value     = $('#pv_increment').val().trim();
        const direction = $('#pv_direction').val();
        const unit      = $('#pv_unit').val();

        if (!dateFrom || !dateTo) { toastr.warning('Seleziona un periodo'); return; }
        if (value === '') { toastr.warning('Inserisci un valore'); return; }

        const variationId = $('#pv-variation-id').val();
        const isEdit      = !!variationId;
        const $btn        = $(this).prop('disabled', true);

        App.ajax({
            path:   isEdit
                ? `/products/${window.PRODUCT_ID}/price-variations/${variationId}`
                : `/products/${window.PRODUCT_ID}/price-variations`,
            method: isEdit ? 'put' : 'post',
            data:   { date_from: dateFrom, date_to: dateTo, direction, value, unit },
        }).then((v) => {
            if (isEdit) {
                const $item = $(`.price-variation-item[data-id="${variationId}"]`);
                $item.replaceWith(renderPriceVariationRow(v));
                toastr.success('Personalizzazione aggiornata');
            } else {
                $('#price-variations-empty').remove();
                $('#price-variations-list').append(renderPriceVariationRow(v));
                toastr.success('Personalizzazione creata');
            }
            $modal.modal('hide');
        }).catch((err) => {
            toastr.error(err?.responseJSON?.message || 'Errore durante il salvataggio');
        }).finally(() => {
            $btn.prop('disabled', false);
        });
    });

    $(document).on('click', '.btn-delete-price-variation', function () {
        const $item = $(this).closest('.price-variation-item');
        const id    = $item.data('id');
        $(document).trigger('sweetConfirmTrigger', [{
            title: 'Elimina personalizzazione',
            text: 'Vuoi eliminare questa personalizzazione di prezzo?',
            callback: () => {
                App.ajax({
                    path: `/products/${window.PRODUCT_ID}/price-variations/${id}`,
                    method: 'delete',
                }).then(() => {
                    $item.remove();
                    toastr.success('Personalizzazione eliminata');
                }).catch(() => toastr.error('Errore durante l\'eliminazione'));
            },
        }]);
    });
};
