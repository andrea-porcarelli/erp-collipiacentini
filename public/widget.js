/**
 * Miticko booking widget — stand-alone, zero dependencies, Shadow DOM.
 *
 * Usage (su pagina partner WordPress):
 *   <div data-miticko-widget data-miticko-product-id="ERP-PROD-17"></div>
 *   <script src="https://widget.miticko.com/widget.js" async></script>
 *
 * Carica dinamicamente CSS e JS necessari. Isola completamente lo stile dalla pagina ospite
 * via Shadow DOM, quindi non entra in conflitto con il tema WordPress.
 */
(function () {
    'use strict';

    const MITICKO_BASE = (document.currentScript && new URL(document.currentScript.src).origin)
        || 'https://widget.miticko.com';
    const API_PUBLIC = MITICKO_BASE + '/api/v1/public';
    const API_WIDGET = MITICKO_BASE + '/api/v1/widget';

    const CSS_URLS = [
        MITICKO_BASE + '/backoffice/css/Miticko.css',
        MITICKO_BASE + '/backoffice/css/helpers.css',
        MITICKO_BASE + '/whitelabel/whitelabel.css',
        MITICKO_BASE + '/whitelabel/responsive.css',
        MITICKO_BASE + '/backoffice/vendor/flatpickr/flatpickr.min.css',
        MITICKO_BASE + '/whitelabel/widget-booking.css',
    ];

    const FLATPICKR_JS = [
        MITICKO_BASE + '/backoffice/vendor/flatpickr/flatpickr.min.js',
        MITICKO_BASE + '/backoffice/vendor/flatpickr/l10n/it.js',
    ];
    // Stripe.js senza Subresource Integrity: Stripe aggiorna /v3/ per security patch e NON pubblica hash SRI
    // (vedi https://docs.stripe.com/js/including). Qualunque hash fisso romperebbe il widget ad ogni loro update.
    const STRIPE_JS = 'https://js.stripe.com/v3/';

    // ---------------------------------------------------------------------------
    // Dependency loader (cached)
    // ---------------------------------------------------------------------------
    const scriptPromises = {};
    function loadScript(src) {
        if (scriptPromises[src]) return scriptPromises[src];
        return scriptPromises[src] = new Promise((resolve, reject) => {
            const s = document.createElement('script');
            s.src = src; s.async = true;
            s.onload = resolve;
            s.onerror = () => reject(new Error('Impossibile caricare ' + src));
            document.head.appendChild(s);
        });
    }
    async function loadFlatpickr() {
        if (window.flatpickr && window.flatpickr.l10ns && window.flatpickr.l10ns.it) return;
        for (const url of FLATPICKR_JS) await loadScript(url);
    }
    async function loadStripe() {
        if (window.Stripe) return;
        await loadScript(STRIPE_JS);
    }

    function injectCssLinks(shadowRoot) {
        const promises = CSS_URLS.map(url => new Promise((resolve) => {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = url;
            link.onload = resolve;
            link.onerror = resolve; // non bloccare se un CSS fallisce
            shadowRoot.appendChild(link);
        }));
        // Font Google: iniettati nel Shadow Root. TODO: scaricare localmente in public/fonts/
        // (regola progetto: niente CDN per asset esterni). Google Fonts non pubblica SRI quindi
        // il link rimane comunque vulnerabile a CDN compromise finché non li self-hostiamo.
        [
            'https://fonts.googleapis.com/css2?family=Work+Sans:ital,wght@0,100..900;1,100..900&display=swap',
            'https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap',
            'https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap',
        ].forEach(url => {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = url;
            shadowRoot.appendChild(link);
        });
        return Promise.all(promises);
    }

    // ---------------------------------------------------------------------------
    // SVG icons (evitiamo Font Awesome kit che non funziona in Shadow DOM)
    // ---------------------------------------------------------------------------
    const ICON = {
        calendar: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;vertical-align:middle"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
        clock: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;vertical-align:middle"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
        user: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;vertical-align:middle"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        id: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;vertical-align:middle"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M15 8h3M15 12h3M7 16h10"/></svg>',
        card: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;vertical-align:middle"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>',
        check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:48px;height:48px;color:#1a7f37"><circle cx="12" cy="12" r="10"/><polyline points="8 12 11 15 16 9"/></svg>',
    };

    const CUSTOM_STYLE = `
        :host { all: initial; display: block; font-family: 'Work Sans', system-ui, sans-serif; color: #0d0d0d; }
        * { box-sizing: border-box; }
        .mw-root { max-width: 520px; margin: 0 auto; padding: 8px; }
        .mw-error { padding: 12px 14px; background: #fdecea; color: #b32d2e; border-radius: 8px; font-size: 14px; margin-top: 12px; font-family: 'DM Sans', sans-serif; }
        .mw-empty { padding: 20px; text-align: center; color: #808080; font-family: 'DM Sans', sans-serif; font-size: 14px; }
        .mw-cta-wrap { margin-top: 16px; }
        .mw-field { display: flex; flex-direction: column; gap: 4px; margin-bottom: 12px; }
        .mw-field label { font-family: 'DM Sans', sans-serif; font-size: 13px; color: #808080; }
        .mw-field input { padding: 10px 12px; border: 1px solid #e6e6e6; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 14px; }
        .mw-field.required label::after { content: " *"; color: #b32d2e; }
        .mw-consent { display: flex; align-items: flex-start; gap: 8px; margin-bottom: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; }
        .mw-consent input { margin-top: 3px; }
        .mw-summary { padding: 12px 14px; background: #fafafa; border-radius: 8px; margin-bottom: 12px; font-family: 'DM Sans', sans-serif; font-size: 14px; }
        .mw-summary-row { display: flex; justify-content: space-between; padding: 2px 0; }
        .mw-summary-total { margin-top: 8px; padding-top: 8px; border-top: 1px solid #e6e6e6; font-weight: 600; }
        .mw-stripe-mount { padding: 12px; border: 1px solid #e6e6e6; border-radius: 8px; min-height: 80px; margin-bottom: 12px; }
        .mw-success { padding: 24px; text-align: center; font-family: 'DM Sans', sans-serif; }
        .mw-loading { padding: 40px; text-align: center; color: #808080; font-family: 'DM Sans', sans-serif; }
    `;

    function widgetSkeleton() {
        return `
            <div class="mw-root">
                <div class="button-progress" data-ref="progress">
                    <button type="button" data-step="date" data-mode="buttonSize-Small buttonEmphasis-MediumLow buttonAppearance-Primary" class="bt-miticko btn-date">${ICON.calendar} Data</button>
                    <div class="progress-connector"></div>
                    <button type="button" data-step="time" disabled data-mode="buttonSize-Small buttonEmphasis-High buttonAppearance-Disabled" class="bt-miticko btn-date disabled">${ICON.clock} Orario</button>
                    <div class="progress-connector"></div>
                    <button type="button" data-step="visitors" disabled data-mode="buttonSize-Small buttonEmphasis-High buttonAppearance-Disabled" class="bt-miticko btn-date disabled">${ICON.user} Visitatori</button>
                    <div class="progress-connector"></div>
                    <button type="button" data-step="customer" disabled data-mode="buttonSize-Small buttonEmphasis-High buttonAppearance-Disabled" class="bt-miticko btn-date disabled">${ICON.id} Dati</button>
                    <div class="progress-connector"></div>
                    <button type="button" data-step="payment" disabled data-mode="buttonSize-Small buttonEmphasis-High buttonAppearance-Disabled" class="bt-miticko btn-date disabled">${ICON.card} Pagamento</button>
                </div>

                <div class="mw-panel" data-panel="date">
                    <div id="calendar-container" class="w-100 mt-3"><div id="calendar" class="w-100"></div></div>
                </div>
                <div class="mw-panel" data-panel="time" style="display:none">
                    <div id="time-slots" class="time-slots-grid w-100 mt-3"></div>
                </div>
                <div class="mw-panel" data-panel="visitors" style="display:none">
                    <div id="ticket-selection" class="w-100 mt-3">
                        <div id="ticket-quantity"></div>
                        <div class="mw-cta-wrap">
                            <button type="button" data-ref="nextVisitors" disabled data-mode="buttonSize-Medium buttonEmphasis-High buttonAppearance-Disabled" class="bt-miticko disabled" style="width:100%">Continua</button>
                        </div>
                    </div>
                </div>
                <div class="mw-panel" data-panel="customer" style="display:none">
                    <div class="mt-3">
                        <div data-ref="customerSummary" class="mw-summary"></div>
                        <form data-ref="customerForm">
                            <div data-ref="customerFields"></div>
                            <div data-ref="consents"></div>
                            <div class="mw-cta-wrap">
                                <button type="submit" data-mode="buttonSize-Medium buttonEmphasis-High buttonAppearance-Primary" class="bt-miticko" style="width:100%">Vai al pagamento</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="mw-panel" data-panel="payment" style="display:none">
                    <div class="mt-3">
                        <div data-ref="paymentSummary" class="mw-summary"></div>
                        <div data-ref="stripeMount" class="mw-stripe-mount">Caricamento del modulo di pagamento…</div>
                        <div class="mw-cta-wrap">
                            <button type="button" data-ref="payCta" disabled data-mode="buttonSize-Medium buttonEmphasis-High buttonAppearance-Disabled" class="bt-miticko disabled" style="width:100%">Paga ora</button>
                        </div>
                    </div>
                </div>
                <div class="mw-panel" data-panel="success" style="display:none">
                    <div class="mw-success">
                        ${ICON.check}
                        <h2 style="font-family:'DM Sans',sans-serif">Prenotazione confermata!</h2>
                        <p>Ti abbiamo inviato un'email con il biglietto QR.<br>Ordine: <strong data-ref="orderNumber"></strong></p>
                    </div>
                </div>
                <div class="mw-error" data-ref="error" style="display:none"></div>
            </div>
        `;
    }

    // ---------------------------------------------------------------------------
    // Widget class
    // ---------------------------------------------------------------------------
    class MitickoWidget {
        constructor(container) {
            this.container = container;
            this.productId = container.getAttribute('data-miticko-product-id');
            if (!this.productId) throw new Error('data-miticko-product-id mancante');
            this.shadow = container.attachShadow({mode: 'open'});
            this.state = {
                step: 'date',
                date: null, time: null, slotType: null, slotId: null,
                availability: {}, product: null, variants: {},
                cartId: null, widgetToken: null,
                paymentIntentId: null, stripe: null, elements: null,
            };
        }

        async init() {
            this.shadow.innerHTML = '<div class="mw-loading" style="font-family:system-ui;padding:40px;text-align:center;color:#888">Caricamento widget Miticko…</div>';
            await injectCssLinks(this.shadow);

            // Custom style per il widget (compat con host)
            const style = document.createElement('style');
            style.textContent = CUSTOM_STYLE;
            this.shadow.appendChild(style);

            // Fetch prodotto per conoscere brand del partner
            try {
                this.state.product = await this.api(API_PUBLIC + '/products/' + encodeURIComponent(this.productId));
            } catch (e) {
                this.shadow.innerHTML = '<div class="mw-error">Impossibile caricare il prodotto: ' + e.message + '</div>';
                return;
            }

            const brand = (this.state.product.partner && this.state.product.partner.brand) || 'miticko';
            const brandCamel = brand.charAt(0).toUpperCase() + brand.slice(1);

            // Monta skeleton
            const wrapper = document.createElement('div');
            wrapper.innerHTML = widgetSkeleton();
            wrapper.firstElementChild.setAttribute('data-mode', brandCamel + ' Light Desktop White');
            // Rimuovi placeholder loading
            this.shadow.querySelectorAll('.mw-loading').forEach(el => el.remove());
            this.shadow.appendChild(wrapper.firstElementChild);

            // Refs
            this.refs = {
                progress: this.q('[data-ref="progress"]'),
                error: this.q('[data-ref="error"]'),
                calendar: this.q('#calendar'),
                timeSlots: this.q('#time-slots'),
                ticketQuantity: this.q('#ticket-quantity'),
                nextVisitors: this.q('[data-ref="nextVisitors"]'),
                customerSummary: this.q('[data-ref="customerSummary"]'),
                customerForm: this.q('[data-ref="customerForm"]'),
                customerFields: this.q('[data-ref="customerFields"]'),
                consents: this.q('[data-ref="consents"]'),
                paymentSummary: this.q('[data-ref="paymentSummary"]'),
                stripeMount: this.q('[data-ref="stripeMount"]'),
                payCta: this.q('[data-ref="payCta"]'),
                orderNumber: this.q('[data-ref="orderNumber"]'),
                panels: this.shadow.querySelectorAll('.mw-panel'),
            };

            this.bindEvents();
            await loadFlatpickr();
            await this.loadCalendar();
        }

        q(sel) { return this.shadow.querySelector(sel); }
        qAll(sel) { return this.shadow.querySelectorAll(sel); }

        bindEvents() {
            this.refs.progress.addEventListener('click', e => {
                const b = e.target.closest('[data-step]');
                if (!b || b.disabled) return;
                this.showStep(b.dataset.step);
            });
            this.refs.nextVisitors.addEventListener('click', () => this.onNextVisitors());
            this.refs.customerForm.addEventListener('submit', e => this.onCustomerSubmit(e));
            this.refs.payCta.addEventListener('click', () => this.onPay());
        }

        async api(url, opts) {
            opts = opts || {};
            opts.headers = Object.assign({'Accept': 'application/json', 'Content-Type': 'application/json'}, opts.headers || {});
            if (this.state.widgetToken && url.startsWith(API_WIDGET)) {
                opts.headers['X-Miticko-Widget-Token'] = this.state.widgetToken;
            }
            const res = await fetch(url, opts);
            const body = await res.json().catch(() => null);
            if (!res.ok) throw new Error((body && body.message) || ('HTTP ' + res.status));
            return body;
        }

        showError(msg) {
            this.refs.error.textContent = msg;
            this.refs.error.style.display = 'block';
        }
        clearError() { this.refs.error.style.display = 'none'; }

        showStep(name) {
            this.state.step = name;
            this.refs.panels.forEach(p => p.style.display = p.dataset.panel === name ? '' : 'none');
        }

        setStepButton(name, {enabled, done}) {
            const btn = this.q('[data-step="' + name + '"]');
            if (!btn) return;
            btn.disabled = !enabled;
            btn.classList.toggle('disabled', !enabled);
            const appearance = done ? 'Primary' : (enabled ? 'Primary' : 'Disabled');
            const emphasis = (enabled && !done) ? 'MediumLow' : 'High';
            btn.setAttribute('data-mode', 'buttonSize-Small buttonEmphasis-' + emphasis + ' buttonAppearance-' + appearance);
        }

        // STEP 1 ------------------------------------------------------------
        async loadCalendar() {
            try {
                const today = new Date();
                const end = new Date(today.getFullYear() + 1, today.getMonth(), today.getDate());
                const iso = d => d.toISOString().slice(0, 10);
                const response = await this.api(API_PUBLIC + '/products/' + encodeURIComponent(this.productId) +
                    '/availability?start_date=' + iso(today) + '&end_date=' + iso(end));
                this.state.availability = (response && response.availability) || {};
                const days = Object.keys(this.state.availability).filter(d => this.state.availability[d] && this.state.availability[d].available);
                window.flatpickr(this.refs.calendar, {
                    inline: true, locale: window.flatpickr.l10ns.it, dateFormat: 'Y-m-d',
                    minDate: 'today', static: true, disableMobile: true,
                    enable: days.length ? days : [],
                    onChange: (_, dateStr) => this.onDatePicked(dateStr),
                });
            } catch (e) { this.showError('Impossibile caricare le disponibilità: ' + e.message); }
        }

        onDatePicked(dateStr) {
            this.clearError();
            this.state.date = dateStr;
            const info = this.state.availability[dateStr] || {};
            this.renderTimes(info.time_slots || []);
            this.setStepButton('date', {enabled: true, done: true});
            this.setStepButton('time', {enabled: true, done: false});
            this.showStep('time');
        }

        // STEP 2 ------------------------------------------------------------
        renderTimes(times) {
            this.refs.timeSlots.innerHTML = '';
            if (!times.length) {
                this.refs.timeSlots.innerHTML = '<div class="mw-empty">Nessun orario disponibile.</div>';
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
                slot.addEventListener('click', () => {
                    this.refs.timeSlots.querySelectorAll('.time-slot').forEach(x => x.classList.remove('selected'));
                    slot.classList.add('selected');
                    this.state.time = t.start;
                    this.state.slotType = t.slot_type;
                    this.state.slotId = t.slot_id;
                    this.loadProductAndRenderVariants();
                });
                this.refs.timeSlots.appendChild(slot);
            });
        }

        // STEP 3 ------------------------------------------------------------
        async loadProductAndRenderVariants() {
            try {
                this.state.product = await this.api(API_PUBLIC + '/products/' + encodeURIComponent(this.productId) + '?date=' + encodeURIComponent(this.state.date));
            } catch (e) { this.showError('Impossibile caricare le varianti: ' + e.message); return; }

            this.refs.ticketQuantity.innerHTML = '';
            this.state.variants = {};
            if (!this.state.product.variants.length) {
                this.refs.ticketQuantity.innerHTML = '<div class="mw-empty">Nessuna variante configurata.</div>';
                return;
            }
            this.state.product.variants.forEach(v => {
                this.state.variants[v.id] = 0;
                const row = document.createElement('div');
                row.className = 'quantity-control';
                row.innerHTML =
                    '<div><div style="font-family:\'DM Sans\',sans-serif;font-weight:500">' + this.esc(v.label || 'Biglietto') + '</div>' +
                    '<div style="font-family:\'DM Sans\',sans-serif;color:#808080;font-size:14px">' + v.price.toFixed(2) + ' €</div></div>' +
                    '<div style="display:flex;align-items:center;gap:12px">' +
                    '<button type="button" data-act="dec" class="bt-miticko" data-mode="buttonSize-Small buttonEmphasis-MediumLow buttonAppearance-Neutral" style="min-width:36px">−</button>' +
                    '<span data-qty="' + v.id + '" style="min-width:24px;text-align:center;font-weight:600">0</span>' +
                    '<button type="button" data-act="inc" class="bt-miticko" data-mode="buttonSize-Small buttonEmphasis-MediumLow buttonAppearance-Neutral" style="min-width:36px">+</button>' +
                    '</div>';
                row.querySelector('[data-act="dec"]').addEventListener('click', () => this.changeQty(v.id, -1));
                row.querySelector('[data-act="inc"]').addEventListener('click', () => this.changeQty(v.id, +1));
                this.refs.ticketQuantity.appendChild(row);
            });
            this.setStepButton('time', {enabled: true, done: true});
            this.setStepButton('visitors', {enabled: true, done: false});
            this.showStep('visitors');
        }

        changeQty(id, delta) {
            const v = this.state.product.variants.find(x => x.id === id);
            const max = v && v.max_quantity ? v.max_quantity : 99;
            this.state.variants[id] = Math.max(0, Math.min(max, (this.state.variants[id] || 0) + delta));
            const span = this.q('[data-qty="' + id + '"]');
            if (span) span.textContent = this.state.variants[id];
            const total = Object.values(this.state.variants).reduce((a, b) => a + b, 0);
            const btn = this.refs.nextVisitors;
            btn.disabled = total === 0;
            btn.classList.toggle('disabled', total === 0);
            btn.setAttribute('data-mode', 'buttonSize-Medium buttonEmphasis-High buttonAppearance-' + (total ? 'Primary' : 'Disabled'));
        }

        async onNextVisitors() {
            this.clearError();
            // Ottieni widget token short-lived
            try {
                const tokenResp = await this.api(API_PUBLIC + '/widget/token', {method: 'POST', body: JSON.stringify({product_id: this.productId})});
                this.state.widgetToken = tokenResp.widget_token;
            } catch (e) { this.showError('Impossibile ottenere il token: ' + e.message); return; }

            const variants = Object.entries(this.state.variants)
                .filter(([, q]) => q > 0)
                .map(([id, quantity]) => ({variant_id: parseInt(id, 10), quantity}));
            try {
                const cart = await this.api(API_WIDGET + '/orders', {method: 'POST', body: JSON.stringify({
                    date: this.state.date, time: this.state.time,
                    slot_type: this.state.slotType, slot_id: this.state.slotId,
                    variants,
                })});
                this.state.cartId = cart.id;
                this.renderCustomerStep();
                this.setStepButton('visitors', {enabled: true, done: true});
                this.setStepButton('customer', {enabled: true, done: false});
                this.showStep('customer');
            } catch (e) { this.showError('Errore creazione prenotazione: ' + e.message); }
        }

        // STEP 4 ------------------------------------------------------------
        renderCustomerStep() {
            this.refs.customerSummary.innerHTML = this.summaryHtml();
            this.refs.customerFields.innerHTML = '';
            const requiredFields = new Set(['name', 'surname', 'email'].concat(
                (this.state.product.customer_fields || []).filter(f => f.required).map(f => f.key)
            ));
            const optionalFields = (this.state.product.customer_fields || []).filter(f => !f.required).map(f => f.key);
            const allFields = [
                {key: 'name', label: 'Nome'}, {key: 'surname', label: 'Cognome'},
                {key: 'email', label: 'Email', type: 'email'},
                {key: 'phone', label: 'Telefono'}, {key: 'fiscal_code', label: 'Codice fiscale'},
                {key: 'address', label: 'Indirizzo'}, {key: 'city', label: 'Città'}, {key: 'zip_code', label: 'CAP'},
            ];
            allFields.forEach(f => {
                const isReq = requiredFields.has(f.key);
                const isOpt = optionalFields.includes(f.key);
                if (!isReq && !isOpt) return;
                const div = document.createElement('div');
                div.className = 'mw-field' + (isReq ? ' required' : '');
                div.innerHTML = '<label>' + this.esc(f.label) + '</label><input type="' + (f.type || 'text') + '" name="' + f.key + '" ' + (isReq ? 'required' : '') + '>';
                this.refs.customerFields.appendChild(div);
            });

            this.refs.consents.innerHTML = '';
            (this.state.product.consents || []).forEach(c => {
                const label = document.createElement('label');
                label.className = 'mw-consent';
                label.innerHTML = '<input type="checkbox" data-consent="' + c.code + '" ' + (c.required ? 'required' : '') + '><span>' + this.esc(c.name) + (c.required ? ' *' : '') + '</span>';
                this.refs.consents.appendChild(label);
            });
            const hasPrivacy = (this.state.product.consents || []).some(c => (c.code || '').toLowerCase().includes('privacy'));
            if (!hasPrivacy) {
                const label = document.createElement('label');
                label.className = 'mw-consent';
                label.innerHTML = '<input type="checkbox" name="privacy_accepted" required><span>Accetto l\'informativa privacy *</span>';
                this.refs.consents.appendChild(label);
            }
        }

        async onCustomerSubmit(e) {
            e.preventDefault();
            this.clearError();
            const form = this.refs.customerForm;
            const data = Object.fromEntries(new FormData(form).entries());
            const consentsPayload = {};
            form.querySelectorAll('[data-consent]').forEach(cb => { consentsPayload[cb.dataset.consent] = cb.checked; });
            const privacyBox = form.querySelector('[name="privacy_accepted"]');
            data.privacy_accepted = privacyBox ? privacyBox.checked : consentsPayload['privacy'] === true;
            data.consents_payload = consentsPayload;
            try {
                await this.api(API_WIDGET + '/orders/' + this.state.cartId + '/customer', {method: 'POST', body: JSON.stringify(data)});
                this.setStepButton('customer', {enabled: true, done: true});
                this.setStepButton('payment', {enabled: true, done: false});
                await this.initStripe();
                this.showStep('payment');
            } catch (err) { this.showError('Errore salvataggio dati cliente: ' + err.message); }
        }

        // STEP 5 ------------------------------------------------------------
        async initStripe() {
            this.refs.paymentSummary.innerHTML = this.summaryHtml();
            let intent;
            try {
                intent = await this.api(API_WIDGET + '/orders/' + this.state.cartId + '/payment-intent', {method: 'POST', body: '{}'});
            } catch (e) { this.showError('Errore inizializzazione pagamento: ' + e.message); return; }

            this.state.paymentIntentId = intent.payment_intent_id;
            await loadStripe();
            if (!window.Stripe || !intent.publishable_key) {
                this.showError('Stripe non configurato correttamente.');
                return;
            }
            this.state.stripe = window.Stripe(intent.publishable_key);
            this.state.elements = this.state.stripe.elements({clientSecret: intent.client_secret, locale: 'it'});
            this.state.paymentElement = this.state.elements.create('payment');
            this.refs.stripeMount.innerHTML = '';
            this.state.paymentElement.mount(this.refs.stripeMount);

            this.refs.payCta.disabled = false;
            this.refs.payCta.classList.remove('disabled');
            this.refs.payCta.setAttribute('data-mode', 'buttonSize-Medium buttonEmphasis-High buttonAppearance-Primary');
        }

        async onPay() {
            this.clearError();
            const btn = this.refs.payCta;
            btn.disabled = true; btn.classList.add('disabled');
            const {error} = await this.state.stripe.confirmPayment({
                elements: this.state.elements,
                confirmParams: { return_url: window.location.href },
                redirect: 'if_required',
            });
            if (error) {
                this.showError(error.message || 'Pagamento non riuscito');
                btn.disabled = false; btn.classList.remove('disabled');
                return;
            }
            try {
                const result = await this.api(API_WIDGET + '/orders/' + this.state.cartId + '/confirm',
                    {method: 'POST', body: JSON.stringify({payment_intent_id: this.state.paymentIntentId})});
                this.refs.orderNumber.textContent = result.order_number;
                this.setStepButton('payment', {enabled: true, done: true});
                this.showStep('success');
            } catch (e) { this.showError('Pagamento effettuato ma conferma ordine fallita: ' + e.message); }
        }

        // Helpers -----------------------------------------------------------
        summaryHtml() {
            const lines = Object.entries(this.state.variants)
                .filter(([, q]) => q > 0)
                .map(([id, qty]) => {
                    const v = this.state.product.variants.find(x => x.id === parseInt(id, 10));
                    return '<div class="mw-summary-row"><span>' + this.esc(v.label || 'Biglietto') + ' × ' + qty + '</span><span>' + (v.price * qty).toFixed(2) + ' €</span></div>';
                }).join('');
            const total = Object.entries(this.state.variants).reduce((acc, [id, q]) => {
                const v = this.state.product.variants.find(x => x.id === parseInt(id, 10));
                return acc + (v ? v.price * q : 0);
            }, 0);
            return '<div><strong>' + this.esc(this.state.product.label) + '</strong></div>' +
                   '<div style="color:#808080;font-size:13px">' + this.state.date + ' alle ' + this.state.time.slice(0, 5) + '</div>' +
                   '<div style="margin-top:8px">' + lines + '</div>' +
                   '<div class="mw-summary-row mw-summary-total"><span>Totale</span><span>' + total.toFixed(2) + ' €</span></div>';
        }

        esc(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]);
        }
    }

    function init() {
        document.querySelectorAll('[data-miticko-widget]').forEach(c => {
            if (c.__mitickoMounted) return;
            c.__mitickoMounted = true;
            try { new MitickoWidget(c).init(); }
            catch (e) { console.error('[Miticko] widget init error:', e); }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
