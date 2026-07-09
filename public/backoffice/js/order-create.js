import App from "./app.js";

const routes = window.orderCreateRoutes || {};
const monthsShort = ['gen','feb','mar','apr','mag','giu','lug','ago','set','ott','nov','dic'];

const state = {
    partnerId: null,
    partnerLabel: null,
    productId: null,
    productLabel: null,
    date: null,
    time: null,
    slotType: null,
    slotId: null,
    slotAvailability: null,
    variants: [],
    quantities: {},
    picker: null,
    availableDays: [],
    initialized: false,
    submitting: false,
};

// -----------------------------------------------------------------------------
// Utilities
// -----------------------------------------------------------------------------
const formatDateChip = (d) => {
    if (!d) return '';
    const dt = new Date(d + 'T00:00:00');
    return `${dt.getDate()} ${monthsShort[dt.getMonth()]} ${String(dt.getFullYear()).slice(-2)}`;
};

const money = (v) => `€ ${Number(v).toFixed(2).replace('.', ',')}`;

const setStepLocked = (stepId, locked) => {
    const el = document.getElementById(stepId);
    if (!el) return;
    el.classList.toggle('is-locked', locked);
};

const setBadgeDone = (n, done) => {
    document.querySelectorAll(`[data-role="badge-${n}"]`).forEach((el) => el.classList.toggle('done', !!done));
};

// Reset a cascata: azzera lo stato del passo indicato e di tutti i successivi.
// Il passo indicato NON deve essere ripopolato da qui: chi chiama sa cosa fare.
const resetStepsFrom = (step) => {
    const order = ['product', 'date', 'variants', 'customer', 'summary'];
    const idx = order.indexOf(step);
    const affected = new Set(order.slice(idx));

    if (affected.has('product')) {
        state.productId = null;
        state.productLabel = null;
        $('#reg-product').val('');
    }
    if (affected.has('date')) {
        state.date = state.time = state.slotType = state.slotId = null;
        state.slotAvailability = null;
        $('#reg-date,#reg-time,#reg-slot-type,#reg-slot-id').val('');
        if (state.picker) { state.picker.destroy(); state.picker = null; }
        $('#reg-slots').html('<div class="text-muted">Seleziona prima una data.</div>');
        setBadgeDone(2, false);
        setStepLocked('reg-step-date', true);
    }
    if (affected.has('variants')) {
        state.variants = [];
        state.quantities = {};
        $('#reg-variants').html('<div class="text-muted">Seleziona prima data e orario.</div>');
        setBadgeDone(3, false);
        setStepLocked('reg-step-variants', true);
    }
    if (affected.has('customer')) {
        setStepLocked('reg-step-customer', true);
    }
    if (affected.has('summary')) {
        setStepLocked('reg-step-summary', true);
    }
    updateSummary();
};

// -----------------------------------------------------------------------------
// STEP 1: Partner
// -----------------------------------------------------------------------------
const loadPartners = () => {
    $.get(routes.partners).done((res) => {
        const $sel = $('#reg-partner');
        const $wrap = $('#reg-partner-wrap');
        const $prodWrap = $('#reg-product-wrap');
        const partners = res.partners || [];

        $sel.empty().append('<option value="">Seleziona partner</option>').prop('disabled', false);
        partners.forEach((p) => {
            $sel.append(`<option value="${p.id}">${p.label}</option>`);
        });

        if (!partners.length) {
            toastr.error('Nessun partner disponibile per la registrazione');
            return;
        }

        // Se il ruolo forza un unico partner, nascondiamo del tutto la select
        // e mostriamo il campo prodotto a piena larghezza. Se invece la select
        // è opzionale ma c'è un solo partner attivo, la preselezioniamo ma
        // lasciamo il campo visibile.
        if (res.locked && partners.length === 1) {
            $wrap.addClass('d-none');
            $prodWrap.removeClass('col-md-6').addClass('col-md-12');
            $sel.val(partners[0].id);
            onPartnerChanged(partners[0].id, partners[0].label);
        } else if (partners.length === 1) {
            $sel.val(partners[0].id).prop('disabled', true);
            onPartnerChanged(partners[0].id, partners[0].label);
        }
    }).fail((xhr) => {
        console.error('loadPartners failed', xhr.status, xhr.responseText);
        toastr.error('Errore nel caricamento dei partner (HTTP ' + xhr.status + ')');
    });
};

const onPartnerChanged = (id, label) => {
    // Reset da product in giù: manteniamo lo stato del partner.
    resetStepsFrom('product');
    state.partnerId = id ? Number(id) : null;
    state.partnerLabel = label;
    setBadgeDone(1, !!state.partnerId);
    if (!state.partnerId) {
        // Ripristina il select prodotto nel suo stato iniziale
        $('#reg-product').empty().append('<option value="">Prima seleziona un partner</option>').prop('disabled', true);
        updateSummary();
        return;
    }
    loadProducts(state.partnerId);
    updateSummary();
};

const loadProducts = (partnerId) => {
    const $sel = $('#reg-product');
    $sel.empty().append('<option value="">Caricamento…</option>').prop('disabled', true);
    $.get(routes.products, { partner_id: partnerId }).done((res) => {
        $sel.empty().append('<option value="">Seleziona prodotto</option>').prop('disabled', false);
        (res.products || []).forEach((p) => {
            $sel.append(`<option value="${p.id}">${p.label}</option>`);
        });
    }).fail((xhr) => {
        console.error('loadProducts failed', xhr.status, xhr.responseText);
        $sel.empty().append('<option value="">Errore nel caricamento</option>');
        toastr.error('Errore nel caricamento dei prodotti (HTTP ' + xhr.status + ')');
    });
};

// -----------------------------------------------------------------------------
// STEP 2: Data / Slot
// -----------------------------------------------------------------------------
const onProductChanged = (id, label) => {
    // Reset da date in giù: NON toccare productId/product select.
    resetStepsFrom('date');
    state.productId = id ? Number(id) : null;
    state.productLabel = label;
    if (!state.productId) {
        updateSummary();
        return;
    }
    setStepLocked('reg-step-date', false);
    loadAvailability(state.productId);
    updateSummary();
};

const loadAvailability = (productId) => {
    $('#reg-slots').html('<div class="text-muted">Caricamento date disponibili…</div>');
    $.get(routes.availabilityDays, { product_id: productId }).done((res) => {
        state.availableDays = res.days || [];
        initPicker(state.availableDays);
        if (!state.availableDays.length) {
            $('#reg-slots').html('<div class="text-muted">Nessuna disponibilità nei prossimi 12 mesi.</div>');
        } else {
            $('#reg-slots').html('<div class="text-muted">Seleziona prima una data.</div>');
        }
    }).fail(() => toastr.error('Errore nel caricamento delle disponibilità'));
};

const initPicker = (days) => {
    if (state.picker) state.picker.destroy();
    state.picker = flatpickr('#reg-calendar-container', {
        inline: true,
        locale: 'it',
        dateFormat: 'Y-m-d',
        minDate: 'today',
        disableMobile: true,
        monthSelectorType: 'static',
        enable: days,
        onChange: (dates, dateStr) => {
            if (!dateStr) return;
            onDateChanged(dateStr);
        },
        onReady: (_, __, instance) => {
            if (days.length) {
                const today = instance.formatDate(new Date(), 'Y-m-d');
                const jumpTo = days.filter((d) => d >= today).sort()[0];
                if (jumpTo) instance.jumpToDate(jumpTo);
            }
        },
    });
};

const onDateChanged = (dateStr) => {
    // Reset da variants in giù: mantieni product/date, azzera time.
    resetStepsFrom('variants');
    state.date = dateStr;
    state.time = state.slotType = state.slotId = null;
    state.slotAvailability = null;
    $('#reg-date').val(dateStr);
    $('#reg-time,#reg-slot-type,#reg-slot-id').val('');
    loadSlots(dateStr);
    updateSummary();
};

const loadSlots = (date) => {
    const $container = $('#reg-slots');
    $container.html('<div class="text-muted">Caricamento orari…</div>');
    $.get(routes.availabilitySlots, { product_id: state.productId, date }).done((res) => {
        const times = res.times || [];
        if (!times.length) {
            $container.html('<div class="text-muted">Nessun orario disponibile per questa data.</div>');
            return;
        }
        $container.empty();
        times.forEach((slot) => {
            const availLabel = slot.availability === null ? '∞' : slot.availability;
            const $el = $(`
                <div class="reg-slot ${slot.is_available ? '' : 'disabled'}">
                    <div class="reg-slot-time">${slot.time}</div>
                    <div class="reg-slot-avail"><i class="fa-regular fa-user"></i> ${availLabel}</div>
                </div>
            `);
            if (slot.is_available) {
                $el.on('click', () => onSlotSelected(slot, $container, $el));
            }
            $container.append($el);
        });
    }).fail(() => $container.html('<div class="text-muted">Errore nel caricamento degli orari.</div>'));
};

const onSlotSelected = (slot, $container, $el) => {
    $container.find('.reg-slot').removeClass('selected');
    $el.addClass('selected');
    state.time = slot.time;
    state.slotType = slot.slot_type;
    state.slotId = slot.slot_id;
    state.slotAvailability = slot.availability;
    $('#reg-time').val(slot.time);
    $('#reg-slot-type').val(slot.slot_type || '');
    $('#reg-slot-id').val(slot.slot_id || '');
    setBadgeDone(2, true);
    setStepLocked('reg-step-variants', false);
    loadVariants();
    updateSummary();
};

// -----------------------------------------------------------------------------
// STEP 3: Varianti / quantità
// -----------------------------------------------------------------------------
const loadVariants = () => {
    const $container = $('#reg-variants');
    $container.html('<div class="text-muted">Caricamento varianti…</div>');
    state.variants = [];
    state.quantities = {};

    $.get(routes.variants, {
        product_id: state.productId,
        date: state.date,
        time: state.time,
    }).done((res) => {
        state.variants = res.variants || [];
        if (!state.variants.length) {
            $container.html('<div class="text-muted">Nessuna variante disponibile.</div>');
            return;
        }
        $container.empty();
        state.variants.forEach((v) => {
            state.quantities[v.id] = 0;
            const $row = $(`
                <div class="reg-variant-row" data-variant="${v.id}">
                    <div class="reg-variant-label">${v.label}</div>
                    <div class="reg-variant-price">${money(v.price)}</div>
                    <div class="reg-qty-control">
                        <button type="button" class="reg-qty-btn" data-role="dec">−</button>
                        <input type="number" class="reg-qty-input" data-role="qty" value="0" min="0" />
                        <button type="button" class="reg-qty-btn" data-role="inc">+</button>
                    </div>
                </div>
            `);
            $container.append($row);
        });
        updateSummary();
    }).fail(() => $container.html('<div class="text-muted">Errore nel caricamento varianti.</div>'));
};

const totalQuantity = () => Object.values(state.quantities).reduce((a, b) => a + Number(b || 0), 0);

const setQty = (variantId, qty) => {
    qty = Math.max(0, Math.floor(Number(qty) || 0));
    const other = totalQuantity() - Number(state.quantities[variantId] || 0);
    if (state.slotAvailability !== null && qty + other > state.slotAvailability) {
        qty = Math.max(0, state.slotAvailability - other);
        toastr.warning(`Disponibilità dello slot: ${state.slotAvailability} biglietti`);
    }
    state.quantities[variantId] = qty;
    $(`.reg-variant-row[data-variant="${variantId}"] [data-role="qty"]`).val(qty);

    const total = totalQuantity();
    const hasVariants = total > 0;
    setBadgeDone(3, hasVariants);
    setStepLocked('reg-step-customer', !hasVariants);
    setStepLocked('reg-step-summary', !hasVariants);
    updateSummary();
};

// -----------------------------------------------------------------------------
// STEP 4: Cliente
// -----------------------------------------------------------------------------
const searchCustomers = App.debounce(() => {
    const q = $('#reg_customer_search').val();
    if (!q || q.length < 2) {
        $('#reg-customer-results').addClass('d-none').empty();
        return;
    }
    $.get(routes.customers, { q, partner_id: state.partnerId }).done((res) => {
        renderCustomerResults(res.customers || []);
    });
}, 300);

const renderCustomerResults = (customers) => {
    const $box = $('#reg-customer-results');
    $box.empty();
    if (!customers.length) {
        $box.html('<div class="reg-customer-result-item text-muted">Nessun cliente trovato</div>').removeClass('d-none');
        return;
    }
    customers.forEach((c) => {
        const label = `${c.name || ''} ${c.surname || ''}`.trim();
        const $el = $(`
            <div class="reg-customer-result-item">
                <div class="name">${label}</div>
                <div class="meta">${c.email || ''}${c.phone ? ' · ' + c.phone : ''}</div>
            </div>
        `);
        $el.on('click', () => selectCustomer(c));
        $box.append($el);
    });
    $box.removeClass('d-none');
};

const selectCustomer = (c) => {
    $('#reg-customer-id').val(c.id);
    $('input[name="customer[name]"]').val(c.name || '');
    $('input[name="customer[surname]"]').val(c.surname || '');
    $('input[name="customer[email]"]').val(c.email || '');
    $('input[name="customer[phone]"]').val(c.phone || '');
    $('input[name="customer[address]"]').val(c.address || '');
    $('input[name="customer[city]"]').val(c.city || '');
    $('input[name="customer[zip_code]"]').val(c.zip_code || '');
    $('input[name="customer[fiscal_code]"]').val(c.fiscal_code || '');
    $('#reg-customer-results').addClass('d-none').empty();
    $('#reg_customer_search').val('');
    const label = `${c.name || ''} ${c.surname || ''}`.trim() + ` (${c.email || ''})`;
    $('#reg-customer-selected-label').text(label);
    $('#reg-customer-selected').removeClass('d-none');
};

// -----------------------------------------------------------------------------
// STEP 5: Riepilogo
// -----------------------------------------------------------------------------
const computeTotal = () => {
    return state.variants.reduce((sum, v) => {
        const qty = Number(state.quantities[v.id] || 0);
        return sum + qty * Number(v.price || 0);
    }, 0);
};

const updateSummary = () => {
    const $box = $('#reg-summary');
    if (!state.partnerId || !state.productId) {
        $box.html('<div class="text-muted">Compila i passaggi precedenti.</div>');
        return;
    }
    const lines = [];
    lines.push(`<div class="reg-summary-row"><span>Partner</span><span>${state.partnerLabel || '-'}</span></div>`);
    lines.push(`<div class="reg-summary-row"><span>Prodotto</span><span>${state.productLabel || '-'}</span></div>`);
    if (state.date) lines.push(`<div class="reg-summary-row"><span>Data</span><span>${formatDateChip(state.date)}</span></div>`);
    if (state.time) lines.push(`<div class="reg-summary-row"><span>Orario</span><span>${state.time}</span></div>`);
    state.variants.forEach((v) => {
        const qty = Number(state.quantities[v.id] || 0);
        if (qty > 0) {
            lines.push(`<div class="reg-summary-row"><span>${qty} × ${v.label}</span><span>${money(qty * v.price)}</span></div>`);
        }
    });
    const total = computeTotal();
    lines.push(`<div class="reg-summary-row total"><span>Totale</span><span>${money(total)}</span></div>`);
    $box.html(lines.join(''));
};

// -----------------------------------------------------------------------------
// Submit
// -----------------------------------------------------------------------------
const submitOrder = () => {
    if (state.submitting) return;

    if (!state.partnerId) { toastr.error('Seleziona un partner'); return; }
    if (!state.productId) { toastr.error('Seleziona un prodotto'); return; }
    if (!state.date || !state.time) { toastr.error('Seleziona data e orario'); return; }

    const items = state.variants
        .map((v) => ({ variant_id: v.id, quantity: Number(state.quantities[v.id] || 0) }))
        .filter((i) => i.quantity > 0);
    if (!items.length) { toastr.error('Aggiungi almeno un biglietto'); return; }

    const customer = {
        id:           $('#reg-customer-id').val() || null,
        name:         $('input[name="customer[name]"]').val(),
        surname:      $('input[name="customer[surname]"]').val(),
        email:        $('input[name="customer[email]"]').val(),
        phone:        $('input[name="customer[phone]"]').val(),
        address:      $('input[name="customer[address]"]').val(),
        city:         $('input[name="customer[city]"]').val(),
        zip_code:     $('input[name="customer[zip_code]"]').val(),
        fiscal_code:  $('input[name="customer[fiscal_code]"]').val(),
    };
    if (!customer.name || !customer.surname || !customer.email) {
        toastr.error('Nome, cognome ed email del cliente sono obbligatori'); return;
    }

    const orderStatus = $('input[name="order_status"]:checked').val() || 'paid';
    const sendEmail = $('#reg-send-email').is(':checked');

    const payload = {
        partner_id: state.partnerId,
        product_id: state.productId,
        date: state.date,
        time: state.time,
        items,
        customer,
        order_status: orderStatus,
        send_email: sendEmail ? 1 : 0,
    };

    state.submitting = true;
    const $btn = $('#reg-submit');
    $btn.prop('disabled', true);

    App.ajax({ path: routes.store, method: 'POST', data: payload }).then((res) => {
        toastr.success('Ordine registrato con successo');
        if (orderStatus === 'pending' && res.order_id) {
            const linkUrl = routes.paymentLinkTpl.replace('{order}', res.order_id);
            App.ajax({ path: linkUrl, method: 'POST' }).then((linkRes) => {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-register-order')).hide();
                $('#reg-payment-link-url').val(linkRes.url || '');
                const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('reg-payment-link-modal'));
                modal.show();
                $('#reg-payment-link-modal .btn-success').off('click').on('click', () => {
                    location.href = res.redirect_url;
                });
            }).catch(() => {
                toastr.warning('Ordine creato ma link di pagamento non disponibile');
                setTimeout(() => location.href = res.redirect_url, 1000);
            });
        } else {
            setTimeout(() => location.href = res.redirect_url, 800);
        }
    }).catch((xhr) => {
        state.submitting = false;
        $btn.prop('disabled', false);
        const msg = xhr?.responseJSON?.message
            || xhr?.responseJSON?.response
            || (xhr?.responseJSON?.errors ? Object.values(xhr.responseJSON.errors).flat().join(', ') : null)
            || 'Errore durante la registrazione dell\'ordine';
        toastr.error(msg);
    });
};

// -----------------------------------------------------------------------------
// Reset completo (chiusura modale)
// -----------------------------------------------------------------------------
const resetAll = () => {
    resetStepsFrom('product');
    state.partnerId = null;
    state.partnerLabel = null;
    $('#reg-partner').val('');
    setBadgeDone(1, false);
    $('#reg-partner-wrap').removeClass('d-none');
    $('#reg-product-wrap').removeClass('col-md-12').addClass('col-md-6');
    $('#reg-product').empty().append('<option value="">Prima seleziona un partner</option>').prop('disabled', true);

    $('#reg-customer-id').val('');
    $('#reg_customer_search').val('');
    $('#reg-customer-results').addClass('d-none').empty();
    $('#reg-customer-selected').addClass('d-none');
    $('input[name^="customer["]').val('');

    $('input[name="order_status"][value="paid"]').prop('checked', true);
    $('#reg-send-email').prop('checked', true);
    state.submitting = false;
    $('#reg-submit').prop('disabled', false);
    updateSummary();
};

// -----------------------------------------------------------------------------
// Event bindings — attaccati una sola volta, il modal è nel DOM al load.
// -----------------------------------------------------------------------------
$(document).on('change', '#reg-partner', function () {
    onPartnerChanged(this.value, $(this).find('option:selected').text());
});
$(document).on('change', '#reg-product', function () {
    onProductChanged(this.value, $(this).find('option:selected').text());
});
$(document).on('click', '#reg-variants [data-role="inc"]', function () {
    const variantId = Number($(this).closest('.reg-variant-row').data('variant'));
    setQty(variantId, Number(state.quantities[variantId] || 0) + 1);
});
$(document).on('click', '#reg-variants [data-role="dec"]', function () {
    const variantId = Number($(this).closest('.reg-variant-row').data('variant'));
    setQty(variantId, Number(state.quantities[variantId] || 0) - 1);
});
$(document).on('input', '#reg-variants [data-role="qty"]', function () {
    const variantId = Number($(this).closest('.reg-variant-row').data('variant'));
    setQty(variantId, this.value);
});
$(document).on('input', '#reg_customer_search', searchCustomers);
$(document).on('click', '#reg-customer-deselect', () => {
    $('#reg-customer-id').val('');
    $('#reg-customer-selected').addClass('d-none');
});
$(document).on('click', '#reg-submit', submitOrder);
$(document).on('click', '#reg-payment-link-copy', function () {
    const $inp = $('#reg-payment-link-url');
    $inp.select();
    try {
        document.execCommand('copy');
        toastr.success('Link copiato negli appunti');
    } catch (_) {
        navigator.clipboard?.writeText($inp.val() || '');
    }
});

// Init lazy alla prima apertura + reset alla chiusura.
$(function () {
    const modalEl = document.getElementById('modal-register-order');
    if (!modalEl) return;
    $(modalEl).on('shown.bs.modal', () => {
        if (state.initialized) return;
        state.initialized = true;
        loadPartners();
        updateSummary();
    });
    $(modalEl).on('hidden.bs.modal', resetAll);
});
