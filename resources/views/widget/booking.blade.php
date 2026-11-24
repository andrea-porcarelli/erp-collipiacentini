@php
    $partner = $product->partner;
    $brand = $partner?->css_style ?: 'Miticko';
    $mode  = ucfirst($partner?->default_layout_mode ?: 'light');
@endphp
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Prenotazione — {{ $product->label }}</title>

    <link href="{{ asset('backoffice/css/Miticko.css') }}" rel="stylesheet">
    <link href="{{ asset('backoffice/css/helpers.css') }}" rel="stylesheet">
    <link href="{{ asset('whitelabel/whitelabel.css') }}" rel="stylesheet">
    <link href="{{ asset('whitelabel/responsive.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('backoffice/vendor/flatpickr/flatpickr.min.css') }}">
    <link href="{{ asset('whitelabel/widget-booking.css') }}" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Work+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/0708d92bf1.js" crossorigin="anonymous"></script>

    {{-- Stripe.js: PCI DSS impone di caricarlo dal dominio ufficiale, non si ospita mai in locale. --}}
    <script src="https://js.stripe.com/v3/"></script>

    <style>
        html, body { margin: 0; padding: 0; background: transparent; }
        body { padding: 16px; }
        .mw-root { max-width: 520px; margin: 0 auto; }
        .mw-error {
            padding: 12px 14px; background: #fdecea; color: #b32d2e;
            border-radius: var(--border-radius-s, 8px); font-size: 14px; margin-top: 12px;
            font-family: var(--font-font-2, "DM Sans"), sans-serif;
        }
        .mw-empty {
            padding: 20px; text-align: center; color: var(--text-secondary, #808080);
            font-family: var(--font-font-2, "DM Sans"), sans-serif; font-size: 14px;
        }
        .mw-cta-wrap { margin-top: 16px; }
        .mw-field { display: flex; flex-direction: column; gap: 4px; margin-bottom: 12px; }
        .mw-field label {
            font-family: var(--font-font-2, "DM Sans"), sans-serif;
            font-size: 13px; color: var(--text-secondary, #808080);
        }
        .mw-field input, .mw-field textarea {
            padding: 10px 12px; border: 1px solid var(--border-default, #E6E6E6);
            border-radius: var(--border-radius-s, 8px);
            font-family: var(--font-font-2, "DM Sans"), sans-serif; font-size: 14px;
        }
        .mw-field.required label::after { content: " *"; color: var(--error-main, #b32d2e); }
        .mw-consent {
            display: flex; align-items: flex-start; gap: 8px; margin-bottom: 8px;
            font-family: var(--font-font-2, "DM Sans"), sans-serif; font-size: 13px;
        }
        .mw-consent input { margin-top: 3px; }
        .mw-summary {
            padding: 12px 14px; background: var(--background-global-paper2, #FAFAFA);
            border-radius: var(--border-radius-s, 8px); margin-bottom: 12px;
            font-family: var(--font-font-2, "DM Sans"), sans-serif; font-size: 14px;
        }
        .mw-summary-row { display: flex; justify-content: space-between; padding: 2px 0; }
        .mw-summary-total {
            margin-top: 8px; padding-top: 8px; border-top: 1px solid var(--border-default, #E6E6E6);
            font-weight: 600;
        }
        .mw-stripe-mount {
            padding: 12px; border: 1px solid var(--border-default, #E6E6E6);
            border-radius: var(--border-radius-s, 8px); min-height: 80px; margin-bottom: 12px;
        }
        .mw-success {
            padding: 24px; text-align: center;
            font-family: var(--font-font-2, "DM Sans"), sans-serif;
        }
        .mw-success h2 {
            font-family: var(--font-font-1, "DM Sans"), sans-serif;
            color: var(--text-main, #0D0D0D);
        }
    </style>
</head>
<body data-mode="{{ $brand }} {{ $mode }} Desktop White">

<div class="mw-root"
     data-product-id="{{ $product->id }}"
     data-product-code="{{ $product->product_code }}">

    <div class="button-progress" id="mw-progress">
        <button type="button" data-step="date"
                data-mode="buttonSize-Small buttonEmphasis-MediumLow buttonAppearance-Primary"
                class="bt-miticko btn-date">
            <i class="fa-regular fa-calendar icon"></i> Data
        </button>
        <div class="progress-connector"></div>
        <button type="button" data-step="time" disabled
                data-mode="buttonSize-Small buttonEmphasis-High buttonAppearance-Disabled"
                class="bt-miticko btn-date disabled">
            <i class="fa-regular fa-clock-three icon"></i> Orario
        </button>
        <div class="progress-connector"></div>
        <button type="button" data-step="visitors" disabled
                data-mode="buttonSize-Small buttonEmphasis-High buttonAppearance-Disabled"
                class="bt-miticko btn-date disabled">
            <i class="fa-regular fa-user icon"></i> Visitatori
        </button>
        <div class="progress-connector"></div>
        <button type="button" data-step="customer" disabled
                data-mode="buttonSize-Small buttonEmphasis-High buttonAppearance-Disabled"
                class="bt-miticko btn-date disabled">
            <i class="fa-regular fa-id-card icon"></i> Dati
        </button>
        <div class="progress-connector"></div>
        <button type="button" data-step="payment" disabled
                data-mode="buttonSize-Small buttonEmphasis-High buttonAppearance-Disabled"
                class="bt-miticko btn-date disabled">
            <i class="fa-regular fa-credit-card icon"></i> Pagamento
        </button>
    </div>

    <div class="mw-panel" data-panel="date">
        <div id="calendar-container" class="w-100 mt-3">
            <div id="calendar" class="w-100"></div>
        </div>
    </div>

    <div class="mw-panel" data-panel="time" style="display:none">
        <div id="time-slots" class="time-slots-grid w-100 mt-3"></div>
    </div>

    <div class="mw-panel" data-panel="visitors" style="display:none">
        <div id="ticket-selection" class="w-100 mt-3">
            <div id="ticket-quantity"></div>
            <div class="mw-cta-wrap">
                <button type="button" id="mw-next-visitors" disabled
                        data-mode="buttonSize-Medium buttonEmphasis-High buttonAppearance-Disabled"
                        class="bt-miticko disabled" style="width:100%">
                    Continua
                </button>
            </div>
        </div>
    </div>

    <div class="mw-panel" data-panel="customer" style="display:none">
        <div class="mt-3">
            <div id="mw-customer-summary" class="mw-summary"></div>
            <form id="mw-customer-form">
                <div id="mw-customer-fields"></div>
                <div id="mw-consents"></div>
                <div class="mw-cta-wrap">
                    <button type="submit" id="mw-next-customer"
                            data-mode="buttonSize-Medium buttonEmphasis-High buttonAppearance-Primary"
                            class="bt-miticko" style="width:100%">
                        Vai al pagamento
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="mw-panel" data-panel="payment" style="display:none">
        <div class="mt-3">
            <div id="mw-payment-summary" class="mw-summary"></div>
            <div id="mw-stripe-mount" class="mw-stripe-mount">Caricamento del modulo di pagamento…</div>
            <div class="mw-cta-wrap">
                <button type="button" id="mw-pay-cta" disabled
                        data-mode="buttonSize-Medium buttonEmphasis-High buttonAppearance-Disabled"
                        class="bt-miticko disabled" style="width:100%">
                    Paga ora
                </button>
            </div>
        </div>
    </div>

    <div class="mw-panel" data-panel="success" style="display:none">
        <div class="mw-success">
            <i class="fa-regular fa-circle-check" style="font-size:48px;color:var(--success-main,#1a7f37)"></i>
            <h2>Prenotazione confermata!</h2>
            <p>Ti abbiamo inviato un'email con il biglietto QR.<br>
               Ordine: <strong id="mw-order-number"></strong></p>
        </div>
    </div>

    <div class="mw-error" id="mw-error" style="display:none"></div>
</div>

<script src="{{ asset('backoffice/vendor/flatpickr/flatpickr.min.js') }}"></script>
<script src="{{ asset('backoffice/vendor/flatpickr/l10n/it.js') }}"></script>
<script>
(function () {
    'use strict';

    const API_PUBLIC = @json(url('/api/v1/public'));
    const API_WIDGET = @json(url('/api/v1/widget'));
    const PRODUCT_ID = @json('ERP-PROD-'.$product->id);
    const WIDGET_TOKEN = @json($widgetToken);

    const progressEl = document.getElementById('mw-progress');
    const errBox = document.getElementById('mw-error');
    const panels = document.querySelectorAll('.mw-panel');

    const state = {
        step: 'date',
        date: null, time: null, slotType: null, slotId: null,
        availability: {},
        product: null,
        variants: {},     // {variant_id: quantity}
        cartId: null,
        paymentIntentId: null,
        stripe: null, elements: null, paymentElement: null,
    };

    function postHeight() {
        if (window.parent) window.parent.postMessage({source: 'miticko-widget', type: 'resize', height: document.documentElement.scrollHeight}, '*');
    }

    function showError(msg) {
        errBox.textContent = msg;
        errBox.style.display = 'block';
        postHeight();
    }
    function clearError() { errBox.style.display = 'none'; }

    function setStepButton(name, {enabled, done}) {
        const btn = progressEl.querySelector('[data-step="' + name + '"]');
        if (!btn) return;
        btn.disabled = !enabled;
        btn.classList.toggle('disabled', !enabled);
        const appearance = done ? 'Primary' : (enabled ? 'Primary' : 'Disabled');
        const emphasis = (enabled && !done) ? 'MediumLow' : 'High';
        btn.setAttribute('data-mode', 'buttonSize-Small buttonEmphasis-' + emphasis + ' buttonAppearance-' + appearance);
    }

    function showStep(name) {
        state.step = name;
        panels.forEach(p => p.style.display = p.dataset.panel === name ? '' : 'none');
        postHeight();
    }

    async function api(path, opts, prefix) {
        opts = opts || {};
        opts.headers = Object.assign({'Accept': 'application/json', 'Content-Type': 'application/json'}, opts.headers || {});
        if (prefix === 'widget') opts.headers['X-Miticko-Widget-Token'] = WIDGET_TOKEN;
        const base = prefix === 'widget' ? API_WIDGET : API_PUBLIC;
        const res = await fetch(base + path, opts);
        const body = await res.json().catch(() => null);
        if (!res.ok) throw new Error((body && body.message) || ('HTTP ' + res.status));
        return body;
    }

    // ---- STEP 1: calendario ----
    async function loadCalendar() {
        try {
            const today = new Date();
            const end = new Date(today.getFullYear() + 1, today.getMonth(), today.getDate());
            const iso = d => d.toISOString().slice(0, 10);
            const response = await api('/products/' + encodeURIComponent(PRODUCT_ID) +
                '/availability?start_date=' + iso(today) + '&end_date=' + iso(end));
            state.availability = (response && response.availability) || {};
            const availableDays = Object.keys(state.availability).filter(d => state.availability[d] && state.availability[d].available);
            flatpickr('#calendar', {
                inline: true, locale: window.flatpickr.l10ns.it, dateFormat: 'Y-m-d',
                minDate: 'today', static: true, disableMobile: true,
                enable: availableDays.length ? availableDays : [],
                onChange(_, dateStr) { onDatePicked(dateStr); }
            });
            postHeight();
        } catch (e) { showError('Impossibile caricare le disponibilità: ' + e.message); }
    }

    function onDatePicked(dateStr) {
        clearError();
        state.date = dateStr;
        const info = state.availability[dateStr] || {};
        renderTimes(info.time_slots || []);
        setStepButton('date', {enabled: true, done: true});
        setStepButton('time', {enabled: true, done: false});
        showStep('time');
    }

    // ---- STEP 2: orari ----
    function renderTimes(times) {
        const el = document.getElementById('time-slots');
        el.innerHTML = '';
        if (!times.length) {
            el.innerHTML = '<div class="mw-empty">Nessun orario disponibile.</div>';
            return;
        }
        times.forEach(t => {
            const slot = document.createElement('div');
            slot.className = 'time-slot';
            const hh = document.createElement('div');
            hh.className = 'time'; hh.textContent = (t.start || '').slice(0, 5);
            slot.appendChild(hh);
            if (t.available_slots !== null && typeof t.available_slots !== 'undefined') {
                const av = document.createElement('div');
                av.className = 'availability';
                av.textContent = t.available_slots + ' posti disponibili';
                slot.appendChild(av);
            }
            slot.onclick = () => {
                el.querySelectorAll('.time-slot').forEach(x => x.classList.remove('selected'));
                slot.classList.add('selected');
                state.time = t.start;
                state.slotType = t.slot_type;
                state.slotId = t.slot_id;
                loadProductAndRenderVariants();
            };
            el.appendChild(slot);
        });
    }

    // ---- STEP 3: visitatori con varianti vere ----
    async function loadProductAndRenderVariants() {
        try {
            state.product = await api('/products/' + encodeURIComponent(PRODUCT_ID) + '?date=' + encodeURIComponent(state.date));
        } catch (e) { showError('Impossibile caricare le varianti: ' + e.message); return; }

        const box = document.getElementById('ticket-quantity');
        box.innerHTML = '';
        state.variants = {};
        if (!state.product.variants.length) {
            box.innerHTML = '<div class="mw-empty">Nessuna variante configurata per questo prodotto.</div>';
            return;
        }
        state.product.variants.forEach(v => {
            state.variants[v.id] = 0;
            const row = document.createElement('div');
            row.className = 'quantity-control';
            const label = document.createElement('div');
            label.innerHTML =
                '<div style="font-family:var(--font-font-2,DM Sans),sans-serif;font-weight:500">' + escapeHtml(v.label || 'Biglietto') + '</div>' +
                '<div style="font-family:var(--font-font-2,DM Sans),sans-serif;color:var(--text-secondary,#808080);font-size:14px">' + v.price.toFixed(2) + ' €</div>';
            const ctrl = document.createElement('div');
            ctrl.style.cssText = 'display:flex;align-items:center;gap:12px';
            ctrl.innerHTML =
                '<button type="button" data-act="dec" class="bt-miticko" data-mode="buttonSize-Small buttonEmphasis-MediumLow buttonAppearance-Neutral" style="min-width:36px">−</button>' +
                '<span data-qty="' + v.id + '" style="min-width:24px;text-align:center;font-weight:600">0</span>' +
                '<button type="button" data-act="inc" class="bt-miticko" data-mode="buttonSize-Small buttonEmphasis-MediumLow buttonAppearance-Neutral" style="min-width:36px">+</button>';
            ctrl.querySelector('[data-act="dec"]').onclick = () => changeQty(v.id, -1);
            ctrl.querySelector('[data-act="inc"]').onclick = () => changeQty(v.id, +1);
            row.appendChild(label); row.appendChild(ctrl);
            box.appendChild(row);
        });
        setStepButton('time', {enabled: true, done: true});
        setStepButton('visitors', {enabled: true, done: false});
        showStep('visitors');
    }

    function changeQty(id, delta) {
        const v = state.product.variants.find(x => x.id === id);
        const max = v && v.max_quantity ? v.max_quantity : 99;
        state.variants[id] = Math.max(0, Math.min(max, (state.variants[id] || 0) + delta));
        const span = document.querySelector('[data-qty="' + id + '"]');
        if (span) span.textContent = state.variants[id];
        const total = Object.values(state.variants).reduce((a, b) => a + b, 0);
        const btn = document.getElementById('mw-next-visitors');
        btn.disabled = total === 0;
        btn.classList.toggle('disabled', total === 0);
        btn.setAttribute('data-mode', 'buttonSize-Medium buttonEmphasis-High buttonAppearance-' + (total ? 'Primary' : 'Disabled'));
    }

    document.getElementById('mw-next-visitors').onclick = async () => {
        clearError();
        const variants = Object.entries(state.variants)
            .filter(([, q]) => q > 0)
            .map(([id, quantity]) => ({variant_id: parseInt(id, 10), quantity}));
        try {
            const cart = await api('/orders', {method: 'POST', body: JSON.stringify({
                date: state.date, time: state.time,
                slot_type: state.slotType, slot_id: state.slotId,
                variants,
            })}, 'widget');
            state.cartId = cart.id;
            renderCustomerStep();
            setStepButton('visitors', {enabled: true, done: true});
            setStepButton('customer', {enabled: true, done: false});
            showStep('customer');
        } catch (e) { showError('Errore creazione prenotazione: ' + e.message); }
    };

    // ---- STEP 4: dati cliente + consensi ----
    function renderCustomerStep() {
        const sum = document.getElementById('mw-customer-summary');
        sum.innerHTML = summaryHtml();

        const fieldsBox = document.getElementById('mw-customer-fields');
        fieldsBox.innerHTML = '';
        const requiredFields = new Set(['name', 'surname', 'email'].concat(
            (state.product.customer_fields || []).filter(f => f.required).map(f => f.key)
        ));
        const optionalFields = (state.product.customer_fields || []).filter(f => !f.required).map(f => f.key);

        const allFields = [
            {key: 'name', label: 'Nome'}, {key: 'surname', label: 'Cognome'},
            {key: 'email', label: 'Email', type: 'email'},
            {key: 'phone', label: 'Telefono'}, {key: 'fiscal_code', label: 'Codice fiscale'},
            {key: 'address', label: 'Indirizzo'}, {key: 'city', label: 'Città'},
            {key: 'zip_code', label: 'CAP'},
        ];
        allFields.forEach(f => {
            const isReq = requiredFields.has(f.key);
            const isOpt = optionalFields.includes(f.key);
            if (!isReq && !isOpt) return;
            const div = document.createElement('div');
            div.className = 'mw-field' + (isReq ? ' required' : '');
            div.innerHTML = '<label>' + escapeHtml(f.label) + '</label>' +
                '<input type="' + (f.type || 'text') + '" name="' + f.key + '" ' + (isReq ? 'required' : '') + '>';
            fieldsBox.appendChild(div);
        });

        const consentsBox = document.getElementById('mw-consents');
        consentsBox.innerHTML = '';
        (state.product.consents || []).forEach(c => {
            const label = document.createElement('label');
            label.className = 'mw-consent';
            label.innerHTML = '<input type="checkbox" data-consent="' + c.code + '" ' + (c.required ? 'required' : '') + '>' +
                '<span>' + escapeHtml(c.name) + (c.required ? ' *' : '') + '</span>';
            consentsBox.appendChild(label);
        });
        // Privacy di default se non c'è un consenso con code=privacy.
        const hasPrivacy = (state.product.consents || []).some(c => (c.code || '').toLowerCase().includes('privacy'));
        if (!hasPrivacy) {
            const label = document.createElement('label');
            label.className = 'mw-consent';
            label.innerHTML = '<input type="checkbox" name="privacy_accepted" required>' +
                '<span>Accetto l\'informativa privacy *</span>';
            consentsBox.appendChild(label);
        }
    }

    document.getElementById('mw-customer-form').onsubmit = async (e) => {
        e.preventDefault();
        clearError();
        const form = e.target;
        const data = Object.fromEntries(new FormData(form).entries());
        const consentsPayload = {};
        form.querySelectorAll('[data-consent]').forEach(cb => { consentsPayload[cb.dataset.consent] = cb.checked; });
        const privacyBox = form.querySelector('[name="privacy_accepted"]');
        data.privacy_accepted = privacyBox ? privacyBox.checked : consentsPayload['privacy'] === true;
        data.consents_payload = consentsPayload;

        try {
            await api('/orders/' + state.cartId + '/customer', {method: 'POST', body: JSON.stringify(data)}, 'widget');
            setStepButton('customer', {enabled: true, done: true});
            setStepButton('payment', {enabled: true, done: false});
            await initStripe();
            showStep('payment');
        } catch (err) { showError('Errore salvataggio dati cliente: ' + err.message); }
    };

    // ---- STEP 5: pagamento Stripe ----
    async function initStripe() {
        document.getElementById('mw-payment-summary').innerHTML = summaryHtml();

        let intent;
        try {
            intent = await api('/orders/' + state.cartId + '/payment-intent', {method: 'POST', body: '{}'}, 'widget');
        } catch (e) { showError('Errore inizializzazione pagamento: ' + e.message); return; }

        state.paymentIntentId = intent.payment_intent_id;
        if (!window.Stripe || !intent.publishable_key) {
            showError('Stripe non configurato correttamente.');
            return;
        }
        state.stripe = window.Stripe(intent.publishable_key);
        state.elements = state.stripe.elements({clientSecret: intent.client_secret, locale: 'it'});
        state.paymentElement = state.elements.create('payment');
        const mount = document.getElementById('mw-stripe-mount');
        mount.innerHTML = '';
        state.paymentElement.mount(mount);

        const payBtn = document.getElementById('mw-pay-cta');
        payBtn.disabled = false;
        payBtn.classList.remove('disabled');
        payBtn.setAttribute('data-mode', 'buttonSize-Medium buttonEmphasis-High buttonAppearance-Primary');
        postHeight();
    }

    document.getElementById('mw-pay-cta').onclick = async () => {
        clearError();
        const btn = document.getElementById('mw-pay-cta');
        btn.disabled = true; btn.classList.add('disabled');
        const {error} = await state.stripe.confirmPayment({
            elements: state.elements,
            confirmParams: { return_url: window.location.href },
            redirect: 'if_required',
        });
        if (error) {
            showError(error.message || 'Pagamento non riuscito');
            btn.disabled = false; btn.classList.remove('disabled');
            return;
        }
        try {
            const result = await api('/orders/' + state.cartId + '/confirm',
                {method: 'POST', body: JSON.stringify({payment_intent_id: state.paymentIntentId})}, 'widget');
            document.getElementById('mw-order-number').textContent = result.order_number;
            setStepButton('payment', {enabled: true, done: true});
            showStep('success');
        } catch (e) { showError('Pagamento effettuato ma conferma ordine fallita: ' + e.message); }
    };

    // ---- helpers ----
    function summaryHtml() {
        const lines = Object.entries(state.variants)
            .filter(([, q]) => q > 0)
            .map(([id, qty]) => {
                const v = state.product.variants.find(x => x.id === parseInt(id, 10));
                return '<div class="mw-summary-row"><span>' + escapeHtml(v.label || 'Biglietto') + ' × ' + qty + '</span>' +
                       '<span>' + (v.price * qty).toFixed(2) + ' €</span></div>';
            }).join('');
        const total = Object.entries(state.variants).reduce((acc, [id, q]) => {
            const v = state.product.variants.find(x => x.id === parseInt(id, 10));
            return acc + (v ? v.price * q : 0);
        }, 0);
        return '<div><strong>' + escapeHtml(state.product.label) + '</strong></div>' +
               '<div style="color:var(--text-secondary,#808080);font-size:13px">' + state.date + ' alle ' + state.time.slice(0, 5) + '</div>' +
               '<div class="mt-2">' + lines + '</div>' +
               '<div class="mw-summary-row mw-summary-total"><span>Totale</span><span>' + total.toFixed(2) + ' €</span></div>';
    }

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]);
    }

    // navigazione back
    progressEl.addEventListener('click', e => {
        const b = e.target.closest('[data-step]');
        if (!b || b.disabled) return;
        showStep(b.dataset.step);
    });

    document.addEventListener('DOMContentLoaded', () => {
        loadCalendar();
        window.addEventListener('resize', postHeight);
    });
})();
</script>
</body>
</html>
