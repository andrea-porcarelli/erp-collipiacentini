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
        close: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;vertical-align:middle"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
        edit: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;vertical-align:middle"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>',
        trash: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;vertical-align:middle"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/></svg>',
        ticket: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;vertical-align:middle"><path d="M2 9a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4V9z"/><line x1="13" y1="5" x2="13" y2="19"/></svg>',
    };

    const CUSTOM_STYLE = `
        :host { all: initial; display: block; width: 100%; font-family: 'Work Sans', system-ui, sans-serif; color: var(--text-main, #0d0d0d); }
        * { box-sizing: border-box; }
        .mw-root { width: 100%; max-width: 520px; margin: 0 auto; padding: 8px; color: var(--text-main, #0d0d0d); background: var(--background-global-default, transparent); }

        /* Progress dei 3 step: tre tracce 1fr uguali + connettori 8px. I bottoni devono
           riempire la cella (width:100% + justify-self:stretch) altrimenti si dimensionano
           al contenuto e "Data" cambia larghezza ad ogni step perché il data-mode cambia
           border-width (MediumLow=1px, High=0px). min-width:0 impedisce al contenuto di
           far crescere la cella oltre la quota 1fr. */
        .mw-root .button-progress {
            display: grid !important;
            grid-template-columns: 1fr 8px 1fr 8px 1fr !important;
            align-items: center !important;
            gap: 0 !important;
            width: 100% !important;
        }
        .mw-root .button-progress > button,
        .mw-root .button-progress > a {
            width: 100% !important;
            min-width: 0 !important;
            justify-self: stretch !important;
            margin: 0 !important;
        }
        .mw-root .button-progress > .progress-connector {
            width: 8px !important;
            height: 1px !important;
            justify-self: stretch !important;
        }
        .mw-error {
            padding: 12px 14px;
            background: var(--colors-brand-error-light-mode-brandlight, #fdecea);
            color: var(--colors-brand-error-light-mode-brandtext, #b32d2e);
            border-radius: 8px; font-size: 14px; margin-top: 12px; font-family: 'DM Sans', sans-serif;
        }
        .mw-empty { padding: 20px; text-align: center; color: var(--text-secondary, #808080); font-family: 'DM Sans', sans-serif; font-size: 14px; }
        .mw-cta-wrap { margin-top: 16px; }
        .mw-field { display: flex; flex-direction: column; gap: 4px; margin-bottom: 12px; }
        .mw-field label { font-family: 'DM Sans', sans-serif; font-size: 13px; color: var(--text-secondary, #808080); }
        .mw-field input {
            padding: 10px 12px;
            border: var(--text-field-empty-borderwidth, 1px) solid var(--text-field-empty-border, #e6e6e6);
            background: var(--text-field-empty-background, #ffffff);
            color: var(--text-main, #0d0d0d);
            border-radius: var(--text-field-borderradius, 8px);
            font-family: 'DM Sans', sans-serif; font-size: 14px;
        }
        .mw-field input:focus {
            outline: none;
            border-color: var(--text-field-focus-border, var(--brand-primary-brand, #2A3493));
            background: var(--text-field-focus-background, var(--text-field-empty-background, #ffffff));
        }
        .mw-field.required label::after { content: " *"; color: var(--error, #b32d2e); }
        .mw-consent { display: flex; align-items: flex-start; gap: 8px; margin-bottom: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; color: var(--text-secondary, #666); }
        .mw-consent input { margin-top: 3px; }
        .mw-summary { padding: 12px 14px; background: var(--background-global-paper2, #fafafa); border-radius: 8px; margin-bottom: 12px; font-family: 'DM Sans', sans-serif; font-size: 14px; color: var(--text-main, #0d0d0d); }
        .mw-summary-row { display: flex; justify-content: space-between; padding: 2px 0; }
        .mw-summary-total { margin-top: 8px; padding-top: 8px; border-top: 1px solid var(--border-default, #e6e6e6); font-weight: 600; }
        .mw-stripe-mount { padding: 12px; border: 1px solid var(--border-default, #e6e6e6); border-radius: 8px; min-height: 80px; margin-bottom: 12px; background: var(--background-global-paper1, #fff); }
        /* Lo slot contiene il mount Stripe nel light DOM (Stripe non supporta Shadow DOM). */
        ::slotted(.mw-stripe-mount) {
            display: block; padding: 12px; border: 1px solid var(--border-default, #e6e6e6);
            border-radius: 8px; min-height: 80px; margin-bottom: 12px;
            background: var(--background-global-paper1, #fff);
        }
        .mw-success { padding: 24px; text-align: center; font-family: 'DM Sans', sans-serif; color: var(--text-main, #0d0d0d); }
        .mw-loading { padding: 40px; text-align: center; color: var(--text-secondary, #808080); font-family: 'DM Sans', sans-serif; }

        /* ---- Calendario (flatpickr inline): si adatta alla larghezza della card ---- */
        #calendar-container { display: block; }
        #calendar { width: 100%; }
        .flatpickr-calendar.inline {
            width: 100% !important;
            max-width: 100%;
            box-shadow: none;
            border: 1px solid var(--border-default, #e6e6e6);
            border-radius: 10px;
        }
        .flatpickr-calendar.inline .flatpickr-days,
        .flatpickr-calendar.inline .dayContainer {
            width: 100% !important;
            min-width: 0 !important;
            max-width: 100% !important;
        }
        .flatpickr-calendar.inline .dayContainer {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
        }
        .flatpickr-calendar.inline .flatpickr-day {
            max-width: none;
            width: auto;
            height: 40px;
            line-height: 38px;
            border-radius: 8px;
        }
        @media (max-width: 420px) {
            .flatpickr-calendar.inline .flatpickr-day { height: 44px; line-height: 42px; }
        }

        /* ---- Griglia orari: colonne 2 su mobile, 3-4 su desktop.
               auto-fit (non auto-fill) così con pochi slot le tracce vuote collassano
               e l'elemento si espande al 100% della larghezza disponibile. ---- */
        .time-slots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(96px, 1fr));
            gap: 8px;
        }
        .time-slots-grid .time-slot {
            padding: 10px 8px;
            border: 1px solid var(--border-default, #e6e6e6);
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            background: var(--background-global-paper1, #fff);
        }
        .time-slots-grid .time-slot.selected {
            border-color: var(--brand-primary-brand, #2A3493);
            background: var(--brand-primary-brandlight, rgba(42,52,147,0.06));
        }
        .time-slots-grid .time { font-weight: 600; font-size: 15px; }
        .time-slots-grid .availability { font-size: 11px; color: var(--text-secondary, #666); margin-top: 2px; }

        /* ---- Riepilogo nello step Visitatori (prima del Continua) ---- */
        .mw-visitors-total {
            display: flex; justify-content: space-between; align-items: center;
            padding: 12px 14px;
            background: var(--background-global-paper2, #fafafa);
            border: 1px solid var(--border-default, #e6e6e6);
            border-radius: 8px; margin-top: 12px; font-family: 'DM Sans', sans-serif;
            font-size: 14px;
        }
        .mw-visitors-total.mw-empty-total { color: var(--text-secondary, #808080); justify-content: center; }
        .mw-visitors-total-tickets { color: var(--text-secondary, #666); }
        .mw-visitors-total-amount { font-weight: 600; font-size: 16px; color: var(--text-main, #0d0d0d); }

        /* ---- Modale checkout (Riepilogo · Dati · Pagamento) ----
           Usa <dialog>: showModal() porta l'elemento nel top-layer del browser,
           che scavalca ogni z-index/stacking context (admin bar WP, menu fissi, ecc.).
           NON serve z-index manuale. */
        dialog.mw-modal-overlay {
            border: 0; padding: 40px 16px; background: transparent;
            max-width: 100vw; max-height: 100vh; width: 100%; height: 100%;
            margin: 0; inset: 0;
            overflow-y: auto; font-family: 'DM Sans', sans-serif;
            color: var(--text-main, #0d0d0d);
        }
        dialog.mw-modal-overlay[open] {
            display: flex; align-items: flex-start; justify-content: center;
        }
        dialog.mw-modal-overlay::backdrop {
            background: rgba(0,0,0,.55);
        }
        .mw-modal {
            background: var(--background-global-paper1, #fff);
            color: var(--text-main, #0d0d0d);
            border-radius: 12px; width: 100%; max-width: 640px;
            box-shadow: 0 20px 60px rgba(0,0,0,.3); padding: 20px 24px 24px;
            position: relative;
        }
        .mw-modal-close {
            position: absolute; top: 12px; right: 12px; background: none; border: 0;
            cursor: pointer; color: var(--text-secondary, #666); padding: 6px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
        }
        .mw-modal-close:hover { background: var(--background-global-paper2, #f1f1f1); color: var(--text-main, #000); }
        .mw-modal-steps {
            display: flex; justify-content: center; align-items: center; gap: 0;
            padding: 4px 0 16px;
        }
        .mw-modal-steps .progress-connector {
            width: 8px; height: 1px; background: var(--border-default, #e6e6e6);
        }
        .mw-modal-panel { padding-top: 4px; }

        .mw-cart-card {
            border: 1px solid var(--border-default, #e6e6e6);
            background: var(--background-global-paper1, transparent);
            border-radius: 10px; padding: 18px;
            display: flex; flex-direction: column; gap: 16px;
        }
        .mw-cart-header { border-bottom: 1px solid var(--border-default, #e6e6e6); padding-bottom: 12px; }
        .mw-cart-header h3 { margin: 0 0 4px; font-size: 18px; font-weight: 600; color: var(--text-main, #0d0d0d); }
        .mw-cart-header p { margin: 0; font-size: 13px; color: var(--text-secondary, #666); }

        .mw-cart-details { display: flex; gap: 16px; }
        .mw-cart-image {
            flex-shrink: 0; width: 96px; height: 96px; border-radius: 8px;
            overflow: hidden;
            background: var(--background-global-paper2, #f1f1f1);
            color: var(--text-disabled, #999);
            display: flex;
            align-items: center; justify-content: center; font-size: 24px;
        }
        .mw-cart-image img { width: 100%; height: 100%; object-fit: cover; }
        .mw-cart-info { flex: 1; display: flex; flex-direction: column; gap: 6px; }
        .mw-cart-info h4 { margin: 0; font-size: 15px; font-weight: 600; color: var(--text-main, #0d0d0d); }
        .mw-cart-meta { display: flex; flex-direction: column; gap: 4px; color: var(--text-main, #333); font-size: 13px; }
        .mw-cart-meta-item { display: flex; align-items: center; gap: 6px; }

        .mw-cart-tickets { padding: 12px 0; }
        .mw-ticket-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 6px 0; font-size: 14px; color: var(--text-secondary, #666);
        }
        .mw-ticket-row.mw-ticket-subtotal {
            padding-top: 10px; font-weight: 600; color: var(--text-main, #333);
        }

        .mw-cart-actions-inline { display: flex; justify-content: flex-end; gap: 10px; }
        .mw-cart-total {
            display: flex; justify-content: space-between; align-items: center;
            padding-top: 12px; border-top: 1px solid var(--border-default, #e6e6e6); margin-top: 4px;
        }
        .mw-cart-total-label, .mw-cart-total-amount {
            font-size: 22px; font-weight: 600; color: var(--text-main, #0d0d0d);
        }

        .mw-modal-actions {
            display: flex; justify-content: space-between; align-items: center;
            gap: 12px; margin-top: 20px;
        }
        .mw-modal-actions .bt-miticko { flex: 1; }

        .mw-pay-summary {
            display: flex; justify-content: space-between; align-items: center;
            padding: 14px 16px;
            background: var(--background-global-paper2, #fafafa);
            border-radius: 8px; margin-bottom: 16px;
        }
        .mw-pay-summary-label { font-size: 14px; color: var(--text-secondary, #666); }
        .mw-pay-summary-amount { font-size: 20px; font-weight: 700; color: var(--text-main, #0d0d0d); }

        @media (max-width: 520px) {
            .mw-modal { padding: 16px 16px 20px; }
            .mw-cart-details { flex-direction: column; align-items: center; text-align: center; }
            .mw-cart-image { width: 100%; height: 160px; }
            .mw-cart-meta { align-items: center; }
            .mw-modal-actions { flex-direction: column; }
            .mw-modal-actions .bt-miticko { width: 100%; }
        }
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
                        <div class="mw-visitors-total mw-empty-total" data-ref="visitorsTotal">
                            <span>Seleziona la quantità di biglietti</span>
                        </div>
                        <div class="mw-cta-wrap">
                            <button type="button" data-ref="nextVisitors" disabled data-mode="buttonSize-Medium buttonEmphasis-High buttonAppearance-Disabled" class="bt-miticko disabled" style="width:100%">Continua</button>
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

            <dialog class="mw-modal-overlay" data-ref="modalOverlay">
                <div class="mw-modal" data-ref="modal">
                    <button type="button" class="mw-modal-close" data-ref="modalClose" aria-label="Chiudi">${ICON.close}</button>

                    <div class="mw-modal-steps" data-ref="modalProgress">
                        <button type="button" data-modal-step="summary" data-mode="buttonSize-Small buttonEmphasis-MediumLow buttonAppearance-Primary" class="bt-miticko btn-date">Riepilogo</button>
                        <div class="progress-connector"></div>
                        <button type="button" data-modal-step="customer" disabled data-mode="buttonSize-Small buttonEmphasis-High buttonAppearance-Disabled" class="bt-miticko btn-date disabled">Dati</button>
                        <div class="progress-connector" data-ref="modalConnectorPayment"></div>
                        <button type="button" data-modal-step="payment" disabled data-mode="buttonSize-Small buttonEmphasis-High buttonAppearance-Disabled" class="bt-miticko btn-date disabled" data-ref="modalBtnPaymentStep">Pagamento</button>
                    </div>

                    <div class="mw-modal-panel" data-modal-panel="summary">
                        <div class="mw-cart-card">
                            <div class="mw-cart-header">
                                <h3>Riepilogo prodotti</h3>
                                <p>verifica i biglietti e procedi</p>
                            </div>
                            <div class="mw-cart-details">
                                <div class="mw-cart-image" data-ref="modalImage"></div>
                                <div class="mw-cart-info">
                                    <h4 data-ref="modalProductName"></h4>
                                    <div class="mw-cart-meta" data-ref="modalMeta"></div>
                                </div>
                            </div>
                            <div class="mw-cart-tickets" data-ref="modalTickets"></div>
                            <div class="mw-cart-actions-inline">
                                <button type="button" data-ref="modalBtnEdit" class="bt-miticko" data-mode="buttonSize-Small buttonEmphasis-Low buttonAppearance-Primary">${ICON.edit} Modifica</button>
                                <button type="button" data-ref="modalBtnRemove" class="bt-miticko" data-mode="buttonSize-Small buttonEmphasis-Low buttonAppearance-Primary">${ICON.trash} Rimuovi</button>
                            </div>
                            <div class="mw-cart-total">
                                <span class="mw-cart-total-label">Totale</span>
                                <span class="mw-cart-total-amount" data-ref="modalCartTotal"></span>
                            </div>
                        </div>
                        <div class="mw-modal-actions">
                            <button type="button" data-ref="modalBtnBackWidget" class="bt-miticko" data-mode="buttonSize-Medium buttonEmphasis-Low buttonAppearance-Primary">Torna al prodotto</button>
                            <button type="button" data-ref="modalBtnToCustomer" class="bt-miticko" data-mode="buttonSize-Medium buttonEmphasis-High buttonAppearance-Primary">Conferma biglietti</button>
                        </div>
                    </div>

                    <div class="mw-modal-panel" data-modal-panel="customer" style="display:none">
                        <form data-ref="modalCustomerForm">
                            <div class="mw-cart-card">
                                <div class="mw-cart-header">
                                    <h3>Inserisci i tuoi dati</h3>
                                    <p>i dati richiesti sono solo quelli necessari per questo prodotto</p>
                                </div>
                                <div data-ref="modalCustomerFields"></div>
                                <div data-ref="modalConsents"></div>
                            </div>
                            <div class="mw-modal-actions">
                                <button type="button" data-ref="modalBtnBackSummary" class="bt-miticko" data-mode="buttonSize-Medium buttonEmphasis-Low buttonAppearance-Primary">indietro</button>
                                <button type="submit" data-ref="modalBtnToPayment" class="bt-miticko" data-mode="buttonSize-Medium buttonEmphasis-High buttonAppearance-Primary">Conferma dati</button>
                            </div>
                        </form>
                    </div>

                    <div class="mw-modal-panel" data-modal-panel="payment" style="display:none">
                        <div class="mw-cart-card">
                            <div class="mw-cart-header">
                                <h3>Completa il pagamento</h3>
                                <p>inserisci i dati della carta per completare l'acquisto</p>
                            </div>
                            <div class="mw-pay-summary">
                                <span class="mw-pay-summary-label">Totale da pagare</span>
                                <span class="mw-pay-summary-amount" data-ref="modalPayTotal"></span>
                            </div>
                            <slot name="stripe-mount"></slot>
                        </div>
                        <div class="mw-modal-actions">
                            <button type="button" data-ref="modalBtnBackCustomer" class="bt-miticko" data-mode="buttonSize-Medium buttonEmphasis-Low buttonAppearance-Primary">Indietro</button>
                            <button type="button" data-ref="modalPayCta" disabled class="bt-miticko disabled" data-mode="buttonSize-Medium buttonEmphasis-High buttonAppearance-Disabled">Paga ora</button>
                        </div>
                    </div>

                    <div class="mw-modal-panel" data-modal-panel="success" style="display:none">
                        <div class="mw-success">
                            ${ICON.check}
                            <h2 style="font-family:'DM Sans',sans-serif">Prenotazione confermata!</h2>
                            <p>Ti abbiamo inviato un'email con il biglietto QR.<br>Ordine: <strong data-ref="modalOrderNumber"></strong></p>
                            <div style="margin-top:16px">
                                <button type="button" data-ref="modalBtnCloseSuccess" class="bt-miticko" data-mode="buttonSize-Medium buttonEmphasis-High buttonAppearance-Primary">Chiudi</button>
                            </div>
                        </div>
                    </div>

                    <div class="mw-error" data-ref="modalError" style="display:none"></div>
                </div>
            </dialog>
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
                modalStep: null,
                date: null, time: null, slotType: null, slotId: null,
                availability: {}, product: null, variants: {},
                cartId: null, widgetToken: null,
                paymentIntentId: null, stripe: null, elements: null,
                isFree: false,
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

            const partner = this.state.product.partner || {};
            // Whitelist: il design system accetta solo questi brand (vedi Partner::CSS_STYLES).
            // Qualunque valore fuori lista (incluso un payload API manipolato) cade su Miticko,
            // evitando di iniettare stringhe arbitrarie nell'attributo data-mode.
            const ALLOWED_BRANDS = ['Miticko', 'Veleia', 'Vigoleno', 'Tocqueville'];
            const brandCamel = ALLOWED_BRANDS.includes(partner.css_style) ? partner.css_style : 'Miticko';
            const layoutMode = (partner.default_layout_mode === 'dark') ? 'Dark' : 'Light';

            // Monta skeleton
            const wrapper = document.createElement('div');
            wrapper.innerHTML = widgetSkeleton();
            // Il data-mode guida i CSS variables del design system (brand + light/dark del partner).
            // Va messo SIA sul .mw-root SIA sulla modale: la modale è un sibling, non ne
            // eredita l'attributo, quindi senza questo form e bottoni dentro la modale
            // restano sullo stile default invece di prendere il brand del partner.
            const brandMode = brandCamel + ' ' + layoutMode + ' Desktop White';
            const rootNode = wrapper.querySelector('.mw-root');
            const modalNode = wrapper.querySelector('.mw-modal-overlay');
            if (rootNode) rootNode.setAttribute('data-mode', brandMode);
            if (modalNode) modalNode.setAttribute('data-mode', brandMode);
            // Rimuovi placeholder loading
            this.shadow.querySelectorAll('.mw-loading').forEach(el => el.remove());
            // Appende TUTTI i nodi radice (widget + modale checkout), non solo il primo.
            while (wrapper.firstElementChild) {
                this.shadow.appendChild(wrapper.firstElementChild);
            }
            // Il mount Stripe vive nel light DOM (fuori dal shadow) via <slot>: perché i
            // CSS variables del brand lo raggiungano, serve il data-mode anche lì.
            // Verrà aggiunto in init() dopo la creazione di this.stripeMount.
            this.brandMode = brandMode;

            // Refs
            this.refs = {
                progress: this.q('[data-ref="progress"]'),
                error: this.q('[data-ref="error"]'),
                calendar: this.q('#calendar'),
                timeSlots: this.q('#time-slots'),
                ticketQuantity: this.q('#ticket-quantity'),
                visitorsTotal: this.q('[data-ref="visitorsTotal"]'),
                nextVisitors: this.q('[data-ref="nextVisitors"]'),
                orderNumber: this.q('[data-ref="orderNumber"]'),
                panels: this.shadow.querySelectorAll('.mw-panel'),
                // Modale
                modalOverlay: this.q('[data-ref="modalOverlay"]'),
                modalClose: this.q('[data-ref="modalClose"]'),
                modalProgress: this.q('[data-ref="modalProgress"]'),
                modalPanels: this.shadow.querySelectorAll('.mw-modal-panel'),
                modalError: this.q('[data-ref="modalError"]'),
                // Summary step
                modalImage: this.q('[data-ref="modalImage"]'),
                modalProductName: this.q('[data-ref="modalProductName"]'),
                modalMeta: this.q('[data-ref="modalMeta"]'),
                modalTickets: this.q('[data-ref="modalTickets"]'),
                modalCartTotal: this.q('[data-ref="modalCartTotal"]'),
                modalBtnEdit: this.q('[data-ref="modalBtnEdit"]'),
                modalBtnRemove: this.q('[data-ref="modalBtnRemove"]'),
                modalBtnBackWidget: this.q('[data-ref="modalBtnBackWidget"]'),
                modalBtnToCustomer: this.q('[data-ref="modalBtnToCustomer"]'),
                // Customer step
                modalCustomerForm: this.q('[data-ref="modalCustomerForm"]'),
                modalCustomerFields: this.q('[data-ref="modalCustomerFields"]'),
                modalConsents: this.q('[data-ref="modalConsents"]'),
                modalBtnBackSummary: this.q('[data-ref="modalBtnBackSummary"]'),
                modalBtnToPayment: this.q('[data-ref="modalBtnToPayment"]'),
                // Payment step
                modalPayTotal: this.q('[data-ref="modalPayTotal"]'),
                modalPayCta: this.q('[data-ref="modalPayCta"]'),
                modalBtnBackCustomer: this.q('[data-ref="modalBtnBackCustomer"]'),
                modalConnectorPayment: this.q('[data-ref="modalConnectorPayment"]'),
                modalBtnPaymentStep: this.q('[data-ref="modalBtnPaymentStep"]'),
                // Success step
                modalOrderNumber: this.q('[data-ref="modalOrderNumber"]'),
                modalBtnCloseSuccess: this.q('[data-ref="modalBtnCloseSuccess"]'),
            };
            // Stripe mount vive nel light DOM (via <slot>): Stripe Elements non funziona dentro Shadow DOM.
            this.stripeMount = document.createElement('div');
            this.stripeMount.setAttribute('slot', 'stripe-mount');
            this.stripeMount.setAttribute('data-mode', this.brandMode);
            this.stripeMount.className = 'mw-stripe-mount';
            this.stripeMount.textContent = 'Caricamento del modulo di pagamento…';
            this.container.appendChild(this.stripeMount);

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

            // Modale
            this.refs.modalClose.addEventListener('click', () => this.closeModal());
            this.refs.modalOverlay.addEventListener('click', e => {
                if (e.target === this.refs.modalOverlay) this.closeModal();
            });
            this.refs.modalProgress.addEventListener('click', e => {
                const b = e.target.closest('[data-modal-step]');
                if (!b || b.disabled) return;
                this.modalShowStep(b.dataset.modalStep);
            });
            this.refs.modalBtnBackWidget.addEventListener('click', () => this.closeModal());
            this.refs.modalBtnEdit.addEventListener('click', () => this.closeModal());
            this.refs.modalBtnRemove.addEventListener('click', () => this.onModalRemove());
            this.refs.modalBtnToCustomer.addEventListener('click', () => this.onModalToCustomer());
            this.refs.modalBtnBackSummary.addEventListener('click', () => this.modalShowStep('summary'));
            this.refs.modalCustomerForm.addEventListener('submit', e => this.onCustomerSubmit(e));
            this.refs.modalBtnBackCustomer.addEventListener('click', () => this.modalShowStep('customer'));
            this.refs.modalPayCta.addEventListener('click', () => this.onPay());
            this.refs.modalBtnCloseSuccess.addEventListener('click', () => this.closeModal());
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
            this.renderVisitorsTotal();
        }

        renderVisitorsTotal() {
            const el = this.refs.visitorsTotal;
            if (!el) return;
            const tickets = Object.values(this.state.variants).reduce((a, b) => a + b, 0);
            const total = Object.entries(this.state.variants).reduce((acc, [id, q]) => {
                const v = this.state.product.variants.find(x => x.id === parseInt(id, 10));
                return acc + (v ? v.price * q : 0);
            }, 0);
            if (tickets === 0) {
                el.className = 'mw-visitors-total mw-empty-total';
                el.innerHTML = '<span>Seleziona la quantità di biglietti</span>';
                return;
            }
            const label = tickets === 1 ? '1 biglietto' : (tickets + ' biglietti');
            el.className = 'mw-visitors-total';
            el.innerHTML =
                '<span class="mw-visitors-total-tickets">' + ICON.ticket + ' ' + label + '</span>' +
                '<span class="mw-visitors-total-amount">' + total.toFixed(2) + ' €</span>';
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
                this.state.isFree = this.computeTotal() <= 0;
                this.setStepButton('visitors', {enabled: true, done: true});
                this.openModal();
            } catch (e) { this.showError('Errore creazione prenotazione: ' + e.message); }
        }

        computeTotal() {
            return Object.entries(this.state.variants).reduce((acc, [id, q]) => {
                const v = this.state.product.variants.find(x => x.id === parseInt(id, 10));
                return acc + (v ? v.price * q : 0);
            }, 0);
        }

        // STEP 4 (dentro la modale) ----------------------------------------
        renderCustomerStep() {
            const fieldsContainer = this.refs.modalCustomerFields;
            fieldsContainer.textContent = '';
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
                const label = document.createElement('label');
                label.textContent = f.label;
                const input = document.createElement('input');
                input.type = f.type || 'text';
                input.name = f.key;
                if (isReq) input.required = true;
                div.appendChild(label);
                div.appendChild(input);
                fieldsContainer.appendChild(div);
            });

            const consentsContainer = this.refs.modalConsents;
            consentsContainer.textContent = '';
            (this.state.product.consents || []).forEach(c => {
                const label = document.createElement('label');
                label.className = 'mw-consent';
                const input = document.createElement('input');
                input.type = 'checkbox';
                input.dataset.consent = c.code || '';
                if (c.required) input.required = true;
                const span = document.createElement('span');
                span.textContent = (c.name || '') + (c.required ? ' *' : '');
                label.appendChild(input);
                label.appendChild(span);
                consentsContainer.appendChild(label);
            });
            const hasPrivacy = (this.state.product.consents || []).some(c => (c.code || '').toLowerCase().includes('privacy'));
            if (!hasPrivacy) {
                const label = document.createElement('label');
                label.className = 'mw-consent';
                const input = document.createElement('input');
                input.type = 'checkbox';
                input.name = 'privacy_accepted';
                input.required = true;
                const span = document.createElement('span');
                span.textContent = 'Accetto l\'informativa privacy *';
                label.appendChild(input);
                label.appendChild(span);
                consentsContainer.appendChild(label);
            }
        }

        async onCustomerSubmit(e) {
            e.preventDefault();
            this.clearModalError();
            const form = this.refs.modalCustomerForm;
            const data = Object.fromEntries(new FormData(form).entries());
            const consentsPayload = {};
            form.querySelectorAll('[data-consent]').forEach(cb => { consentsPayload[cb.dataset.consent] = cb.checked; });
            const privacyBox = form.querySelector('[name="privacy_accepted"]');
            data.privacy_accepted = privacyBox ? privacyBox.checked : consentsPayload['privacy'] === true;
            data.consents_payload = consentsPayload;
            try {
                await this.api(API_WIDGET + '/orders/' + this.state.cartId + '/customer', {method: 'POST', body: JSON.stringify(data)});
                this.setModalStepButton('customer', {enabled: true, done: true});
                if (this.state.isFree) {
                    // Nessun pagamento: va direttamente alla conferma (API confirm senza PaymentIntent)
                    try {
                        const result = await this.api(API_WIDGET + '/orders/' + this.state.cartId + '/confirm', {method: 'POST', body: '{}'});
                        this.refs.modalOrderNumber.textContent = result.order_number || '';
                        this.modalShowStep('success');
                    } catch (err) {
                        this.showModalError('Errore conferma prenotazione: ' + err.message);
                    }
                    return;
                }
                this.setModalStepButton('payment', {enabled: true, done: false});
                await this.initStripe();
                this.modalShowStep('payment');
            } catch (err) { this.showModalError('Errore salvataggio dati cliente: ' + err.message); }
        }

        // STEP 5 (dentro la modale) ----------------------------------------
        async initStripe() {
            this.refs.modalPayTotal.textContent = this.computeTotal().toFixed(2) + ' €';
            let intent;
            try {
                intent = await this.api(API_WIDGET + '/orders/' + this.state.cartId + '/payment-intent', {method: 'POST', body: '{}'});
            } catch (e) { this.showModalError('Errore inizializzazione pagamento: ' + e.message); return; }

            this.state.paymentIntentId = intent.payment_intent_id;
            await loadStripe();
            if (!window.Stripe || !intent.publishable_key) {
                this.showModalError('Stripe non configurato correttamente.');
                return;
            }
            this.state.stripe = window.Stripe(intent.publishable_key);
            this.state.elements = this.state.stripe.elements({clientSecret: intent.client_secret, locale: 'it'});
            this.state.paymentElement = this.state.elements.create('payment');
            this.stripeMount.textContent = '';
            this.state.paymentElement.mount(this.stripeMount);

            this.refs.modalPayCta.disabled = false;
            this.refs.modalPayCta.classList.remove('disabled');
            this.refs.modalPayCta.setAttribute('data-mode', 'buttonSize-Medium buttonEmphasis-High buttonAppearance-Primary');
            this.refs.modalPayCta.textContent = 'Paga ' + this.computeTotal().toFixed(2) + ' €';
        }

        async onPay() {
            this.clearModalError();
            const btn = this.refs.modalPayCta;
            btn.disabled = true; btn.classList.add('disabled');
            const {error} = await this.state.stripe.confirmPayment({
                elements: this.state.elements,
                confirmParams: { return_url: window.location.href },
                redirect: 'if_required',
            });
            if (error) {
                this.showModalError(error.message || 'Pagamento non riuscito');
                btn.disabled = false; btn.classList.remove('disabled');
                return;
            }
            try {
                const result = await this.api(API_WIDGET + '/orders/' + this.state.cartId + '/confirm',
                    {method: 'POST', body: JSON.stringify({payment_intent_id: this.state.paymentIntentId})});
                this.refs.modalOrderNumber.textContent = result.order_number || '';
                this.setModalStepButton('payment', {enabled: true, done: true});
                this.modalShowStep('success');
            } catch (e) { this.showModalError('Pagamento effettuato ma conferma ordine fallita: ' + e.message); }
        }

        // Helpers -----------------------------------------------------------
        esc(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]);
        }

        // Modale ------------------------------------------------------------
        openModal() {
            this.renderModalSummary();
            this.modalShowStep('summary');
            // <dialog>.showModal() promuove l'elemento al top-layer del browser, che
            // scavalca ogni stacking context (admin bar WP, header fissati, ecc.).
            const d = this.refs.modalOverlay;
            if (typeof d.showModal === 'function' && !d.open) {
                d.showModal();
            } else {
                // Fallback per browser datati (nessun supporto <dialog>).
                d.setAttribute('open', '');
                d.style.display = 'flex';
            }
            document.body.style.overflow = 'hidden';
        }

        closeModal() {
            const d = this.refs.modalOverlay;
            if (typeof d.close === 'function' && d.open) {
                d.close();
            } else {
                d.removeAttribute('open');
                d.style.display = 'none';
            }
            document.body.style.overflow = '';
        }

        modalShowStep(name) {
            this.state.modalStep = name;
            this.refs.modalPanels.forEach(p => p.style.display = p.dataset.modalPanel === name ? '' : 'none');
            // Gestione pulsanti progress modale (summary → customer → payment)
            if (name === 'customer') {
                this.setModalStepButton('summary', {enabled: true, done: true});
                this.setModalStepButton('customer', {enabled: true, done: false});
            } else if (name === 'payment') {
                this.setModalStepButton('customer', {enabled: true, done: true});
                this.setModalStepButton('payment', {enabled: true, done: false});
            } else if (name === 'success') {
                this.setModalStepButton('payment', {enabled: true, done: true});
            }
        }

        setModalStepButton(name, {enabled, done}) {
            const btn = this.q('[data-modal-step="' + name + '"]');
            if (!btn) return;
            btn.disabled = !enabled;
            btn.classList.toggle('disabled', !enabled);
            const appearance = done ? 'Primary' : (enabled ? 'Primary' : 'Disabled');
            const emphasis = (enabled && !done) ? 'MediumLow' : 'High';
            btn.setAttribute('data-mode', 'buttonSize-Small buttonEmphasis-' + emphasis + ' buttonAppearance-' + appearance);
        }

        showModalError(msg) {
            this.refs.modalError.textContent = msg;
            this.refs.modalError.style.display = 'block';
        }
        clearModalError() {
            this.refs.modalError.textContent = '';
            this.refs.modalError.style.display = 'none';
        }

        renderModalSummary() {
            const p = this.state.product;

            // Immagine prodotto (prima disponibile)
            this.refs.modalImage.textContent = '';
            const firstImage = p.images && p.images.length ? p.images[0] : null;
            if (firstImage && firstImage.url) {
                const img = document.createElement('img');
                img.src = firstImage.url;
                img.alt = firstImage.alt || p.label || '';
                this.refs.modalImage.appendChild(img);
            } else {
                this.refs.modalImage.textContent = '🎫';
            }

            this.refs.modalProductName.textContent = p.label || p.name || '';

            // Meta (data, ora, categoria)
            const meta = this.refs.modalMeta;
            meta.textContent = '';
            const metaRows = [];
            if (this.state.date) metaRows.push({ icon: ICON.calendar, text: this.formatDate(this.state.date) });
            if (this.state.time) metaRows.push({ icon: ICON.clock, text: (this.state.time || '').slice(0, 5) });
            if (p.category && p.category.name) metaRows.push({ icon: ICON.ticket, text: p.category.name });
            metaRows.forEach(r => {
                const row = document.createElement('div');
                row.className = 'mw-cart-meta-item';
                const ic = document.createElement('span');
                ic.innerHTML = r.icon; // ICON.* è una costante SVG del nostro codice
                const sp = document.createElement('span');
                sp.textContent = r.text;
                row.appendChild(ic);
                row.appendChild(sp);
                meta.appendChild(row);
            });

            // Righe biglietti
            const tickets = this.refs.modalTickets;
            tickets.textContent = '';
            let total = 0;
            Object.entries(this.state.variants).forEach(([id, qty]) => {
                if (qty <= 0) return;
                const v = p.variants.find(x => x.id === parseInt(id, 10));
                if (!v) return;
                const subtotal = v.price * qty;
                total += subtotal;
                const row = document.createElement('div');
                row.className = 'mw-ticket-row';
                const type = document.createElement('span');
                type.textContent = qty + ' ' + (v.label || 'Biglietto') + ' × ' + v.price.toFixed(2) + ' €';
                const price = document.createElement('span');
                price.textContent = subtotal.toFixed(2) + ' €';
                row.appendChild(type);
                row.appendChild(price);
                tickets.appendChild(row);
            });
            const sub = document.createElement('div');
            sub.className = 'mw-ticket-row mw-ticket-subtotal';
            const subLabel = document.createElement('span');
            subLabel.textContent = 'Subtotale';
            const subPrice = document.createElement('span');
            subPrice.textContent = total.toFixed(2) + ' €';
            sub.appendChild(subLabel);
            sub.appendChild(subPrice);
            tickets.appendChild(sub);

            this.refs.modalCartTotal.textContent = total.toFixed(2) + ' €';
        }

        formatDate(iso) {
            if (!iso) return '';
            const d = new Date(iso + 'T00:00:00');
            if (isNaN(d.getTime())) return iso;
            return d.toLocaleDateString('it-IT', { weekday: 'short', day: 'numeric', month: 'long', year: 'numeric' });
        }

        onModalRemove() {
            if (!confirm('Vuoi davvero rimuovere i biglietti selezionati?')) return;
            // Reset stato lato widget; il cart creato lato server scadrà da solo.
            Object.keys(this.state.variants).forEach(k => { this.state.variants[k] = 0; });
            this.qAll('[data-qty]').forEach(sp => sp.textContent = '0');
            this.state.cartId = null;
            this.state.widgetToken = null;
            this.renderVisitorsTotal();
            // Reset CTA Continua
            const btn = this.refs.nextVisitors;
            btn.disabled = true;
            btn.classList.add('disabled');
            btn.setAttribute('data-mode', 'buttonSize-Medium buttonEmphasis-High buttonAppearance-Disabled');
            this.setStepButton('visitors', {enabled: true, done: false});
            this.closeModal();
        }

        async onModalToCustomer() {
            this.clearModalError();
            this.renderCustomerStep();
            this.modalShowStep('customer');
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
