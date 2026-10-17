// Modale wizard "Imposta fattura" (2 step) usata da invoices/index e orders/show.
// Carica preview dall'endpoint `orders/{id}/invoice/preview`, orchestra default/custom,
// invia su `orders/{id}/invoice` e mostra feedback con APP_BANNER (banner top-page).
(function () {
    const $modal = () => $('#modal-set-invoice');
    let currentOrderId = null;
    let currentOrderNumber = null;
    let preview = null;
    let currentStep = 1;

    const fmt = (v) => (v === null || v === undefined || isNaN(v)) ? '—' : parseFloat(v).toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';

    const openModal = (orderId, orderNumber) => {
        currentOrderId = orderId;
        currentOrderNumber = orderNumber || '';
        currentStep = 1;
        $modal().find('#invoice-wizard-loading').removeClass('d-none');
        $modal().find('#invoice-wizard-content').addClass('d-none');
        setStep(1);
        $modal().modal('show');
        fetchPreview();
    };

    const fetchPreview = () => {
        $.ajax({
            url: `/orders/${currentOrderId}/invoice/preview`,
            method: 'GET',
            dataType: 'json',
        }).done((res) => {
            preview = res;
            renderStep1();
            renderStep2();
            applyMode('default');
            $modal().find('#invoice-wizard-loading').addClass('d-none');
            $modal().find('#invoice-wizard-content').removeClass('d-none');
        }).fail(() => {
            if (typeof toastr !== 'undefined') toastr.error('Errore nel calcolo delle voci fattura');
            $modal().modal('hide');
        });
    };

    const renderStep1 = () => {
        if (!preview) return;
        const partner = preview.partner || {};
        const customer = preview.customer || {};
        const recipient = partner.recipient || customer.recipient || {};
        const allLines = [...(partner.lines || []), ...(customer.lines || [])];

        $modal().find('[data-field="partner_name"]').text(recipient.name || '—');
        $modal().find('[data-field="customer_name"]').text(customer.recipient?.name || '—');
        const products = preview.order_meta?.products || [];
        const productHtml = products.length === 0 ? '—' : products
            .map((p) => `<a href="/products/${p.id}" target="_blank" rel="noopener" class="invoice-open-order-link">${escapeHtml(p.label)}</a>`)
            .join(', ');
        $modal().find('[data-field="product_label"]').html(productHtml);
        $modal().find('[data-field="order_number"]').text('#' + (currentOrderNumber || ''));
        $modal().find('[data-field="payment_method"]').text(preview.order_meta?.payment_method || '—');

        // Step 1 mostra le VARIANTI acquistate dell'ordine (OrderProductItem), non le voci fatturabili.
        const orderItems = preview.order_meta?.items || [];
        const $items = $modal().find('[data-slot="order-items"]').empty();
        orderItems.forEach((it) => {
            const vat = (it.vat_rate === null || it.vat_rate === undefined)
                ? '—'
                : (parseFloat(it.vat_rate) === 0 ? 'Esente' : `${parseFloat(it.vat_rate)}%`);
            $items.append(`<tr>
                <td>${escapeHtml(it.label)}</td>
                <td class="text-end">${it.quantity}</td>
                <td class="text-end">${fmt(it.unit_price)}</td>
                <td class="text-end">${vat}</td>
                <td class="text-end">${fmt(it.subtotal)}</td>
            </tr>`);
        });

        const subtotal = orderItems.reduce((s, it) => s + (parseFloat(it.subtotal) || 0), 0);
        const orderTotal = parseFloat(preview.order_meta?.order_total ?? subtotal);
        $modal().find('[data-slot="order-subtotal"]').text(fmt(subtotal));
        $modal().find('[data-slot="order-total"]').text(fmt(orderTotal));

        // Default: imposta checkbox come da default_flags.
        // ticket_base è un flag del CUSTOMER (biglietto emesso nella fattura cliente).
        const defaults = {
            ticket_base: !!customer.default_flags?.ticket_base,
            miticko_commission: !!partner.default_flags?.miticko_commission,
            bank_commission: !!partner.default_flags?.bank_commission,
            presale: !!customer.default_flags?.presale,
        };
        Object.entries(defaults).forEach(([flag, val]) => {
            $modal().find(`.invoice-flag[data-flag="${flag}"]`).prop('checked', val);
        });

        // Se nessun flag → forzatura Personalizza; "Fattura" = Non necessaria
        const anyFlag = Object.values(defaults).some(Boolean);
        $modal().find('select[name="invoice_required"]').val(anyFlag ? '1' : '0');
    };

    const renderStep2 = () => {
        if (!preview) return;
        const customer = preview.customer || {};
        const partner = preview.partner || {};
        const meta = preview.order_meta || {};

        // Tabella principale: le varianti dell'ordine (come dettaglio ordine).
        const orderItems = meta.items || [];
        const $items = $modal().find('[data-slot="invoice-items"]').empty();
        orderItems.forEach((it) => {
            $items.append(`<tr>
                <td class="fw-semibold">${escapeHtml(it.label)}</td>
                <td class="text-end">${it.quantity}</td>
                <td class="text-end">${fmt(it.unit_price)}</td>
                <td class="text-end">${fmt(it.subtotal)}</td>
            </tr>`);
        });

        // Subtotale e totale = order amount (come dettaglio ordine: le commissioni non si sommano).
        const orderTotal = parseFloat(meta.order_total ?? 0);
        $modal().find('[data-slot="invoice-subtotal"]').text(fmt(orderTotal));
        $modal().find('[data-slot="invoice-total"]').text(fmt(orderTotal));

        // DI CUI: righe condizionali in base ai flag selezionati nello Step 1.
        const flagChecked = (f) => $modal().find(`.invoice-flag[data-flag="${f}"]`).is(':checked');

        const showPayment = flagChecked('bank_commission');
        $modal().find('[data-slot="row-commission-payment"]').toggle(showPayment);
        if (showPayment) {
            $modal().find('[data-slot="rate-payment"]').text(String(meta.commission_payment_rate ?? 0).replace('.', ','));
            $modal().find('[data-slot="amount-payment"]').text(fmt(meta.commission_payment_amount));
        }

        const showMiticko = flagChecked('miticko_commission');
        $modal().find('[data-slot="row-commission-miticko"]').toggle(showMiticko);
        if (showMiticko) {
            $modal().find('[data-slot="rate-miticko"]').text(String(meta.commission_miticko_rate ?? 0).replace('.', ','));
            $modal().find('[data-slot="amount-miticko"]').text(fmt(meta.commission_miticko_amount));
        }

        const presaleTotal = (customer.lines || []).filter((l) => l.code === 'presale')
            .reduce((s, l) => s + (parseFloat(l.total) || 0), 0);
        const showPresale = flagChecked('presale') && presaleTotal > 0;
        $modal().find('[data-slot="row-presale"]').toggle(showPresale);
        if (showPresale) {
            $modal().find('[data-slot="amount-presale"]').text(fmt(presaleTotal));
        }

        const cust = customer.recipient || {};
        const custHtml = [
            cust.name ? `<div><i class="fa-regular fa-user me-1"></i>${escapeHtml(cust.name)}</div>` : '',
            cust.address ? `<div><i class="fa-regular fa-location-dot me-1"></i>${escapeHtml([cust.address, cust.postal_code, cust.city, cust.province].filter(Boolean).join(', '))}</div>` : '',
            cust.tax_code ? `<div><i class="fa-regular fa-id-card me-1"></i>${escapeHtml(cust.tax_code)}</div>` : '',
            cust.sdi_code ? `<div><i class="fa-regular fa-building me-1"></i>${escapeHtml(cust.sdi_code)}</div>` : '',
            cust.country ? `<div><i class="fa-regular fa-globe me-1"></i>${escapeHtml(cust.country)}</div>` : '',
        ].filter(Boolean).join('');
        $modal().find('[data-slot="customer-summary"]').html(custHtml || '—');
        $modal().find('[data-slot="payment-method"]').text(preview.order_meta?.payment_method || '—');
        $modal().find('[data-slot="order-link"]').attr('href', `/orders/${currentOrderId}`);

        // Descrizione precompilata con #order_number (x-textarea usa id=name)
        const desc = '#' + (currentOrderNumber || '');
        $modal().find('#invoice_description').val(desc).trigger('input');
    };

    // Preset data-mode per bottone attivo/inattivo coerente col design system.
    const MODE_ACTIVE = 'buttonSize-Small buttonEmphasis-MediumLow buttonAppearance-Secondary';
    const MODE_INACTIVE = 'buttonSize-Small buttonEmphasis-Low  buttonAppearance-Neutral';

    const applyMode = (mode) => {
        $modal().find('.invoice-mode-btn').each(function () {
            const isActive = $(this).data('invoice-mode') === mode;
            $(this).attr('data-mode', isActive ? MODE_ACTIVE : MODE_INACTIVE);
        });

        // In modalità "default" l'intera sezione campi è readonly con visuale greyed-out.
        const isDefault = mode === 'default';
        $modal().find('.invoice-settings-fields').toggleClass('is-locked', isDefault);
        $modal().find('.invoice-flag').prop('disabled', isDefault);
        $modal().find('select[name="invoice_required"]').prop('disabled', isDefault);
    };

    const applyTicketBaseRule = () => {
        const $ticket = $modal().find('.invoice-flag[data-flag="ticket_base"]');
        const locked = $ticket.is(':checked');
        $modal().find('.invoice-flag[data-flag="miticko_commission"], .invoice-flag[data-flag="bank_commission"]')
            .each(function () {
                if (locked) $(this).prop('checked', true);
                $(this).prop('disabled', locked || isDefaultMode());
            });
    };

    const isDefaultMode = () => {
        const mode = $modal().find('.invoice-mode-btn[data-invoice-mode="default"]').attr('data-mode') || '';
        return mode.includes('buttonAppearance-Secondary');
    };

    // Preset data-mode per tab wizard.
    //  - "Imposta fattura" alterna MediumLow (attivo) / Medium (inattivo), sempre Primary.
    //  - "Riepilogo" resta Disabled all'apertura modale; diventa MediumLow Primary solo quando lo step è il 2.
    const TAB1_ACTIVE = 'buttonSize-Small buttonEmphasis-MediumLow  buttonAppearance-Primary';
    const TAB1_INACTIVE = 'buttonSize-Small buttonEmphasis-Medium  buttonAppearance-Primary';
    const TAB2_ACTIVE = 'buttonSize-Small buttonEmphasis-MediumLow  buttonAppearance-Primary';
    const TAB2_DISABLED = 'buttonSize-Small buttonEmphasis-High  buttonAppearance-Disabled';

    const setStep = (n) => {
        currentStep = n;
        $modal().find('.invoice-wizard-tab[data-step="1"]').attr('data-mode', n === 1 ? TAB1_ACTIVE : TAB1_INACTIVE);
        $modal().find('.invoice-wizard-tab[data-step="2"]').attr('data-mode', n === 2 ? TAB2_ACTIVE : TAB2_DISABLED);
        $modal().find('.invoice-wizard-pane').addClass('d-none');
        $modal().find(`.invoice-wizard-pane[data-step="${n}"]`).removeClass('d-none');
        $modal().find('.btn-wizard-next').toggleClass('d-none', n !== 1);
        $modal().find('.btn-wizard-submit').toggleClass('d-none', n !== 2);
    };

    const buildPayload = () => {
        const flagsPartner = {
            miticko_commission: $modal().find('.invoice-flag[data-flag="miticko_commission"]').is(':checked') ? 1 : 0,
            bank_commission: $modal().find('.invoice-flag[data-flag="bank_commission"]').is(':checked') ? 1 : 0,
        };
        const flagsCustomer = {
            presale: $modal().find('.invoice-flag[data-flag="presale"]').is(':checked') ? 1 : 0,
            ticket_base: $modal().find('.invoice-flag[data-flag="ticket_base"]').is(':checked') ? 1 : 0,
        };
        const recipients = [];
        if (Object.values(flagsPartner).some(Boolean)) recipients.push('partner');
        if (Object.values(flagsCustomer).some(Boolean)) recipients.push('customer');
        return {
            recipients,
            flags: { partner: flagsPartner, customer: flagsCustomer },
            mode: isDefaultMode() ? 'default' : 'custom',
            description: $modal().find('#invoice_description').val() || '',
        };
    };

    const submit = () => {
        const payload = buildPayload();
        if (payload.recipients.length === 0) {
            if (typeof toastr !== 'undefined') toastr.warning('Seleziona almeno una voce da fatturare');
            return;
        }
        const $btn = $modal().find('.btn-wizard-submit');
        $btn.prop('disabled', true);
        $.ajax({
            url: `/orders/${currentOrderId}/invoice`,
            method: 'POST',
            data: payload,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            dataType: 'json',
        }).done((res) => {
            $modal().modal('hide');
            if (res.success) {
                banner('success', 'Dati inviati al tool di fatturazione');
            } else {
                banner('error', `Fattura ordine #${currentOrderNumber} non inviata, riprova`);
            }
            if (typeof App !== 'undefined' && App.reloadTable) App.reloadTable();
        }).fail(() => {
            banner('error', `Fattura ordine #${currentOrderNumber} non inviata, riprova`);
        }).always(() => $btn.prop('disabled', false));
    };

    const banner = (type, message) => {
        if (window.APP_BANNER) window.APP_BANNER(type, message);
        else if (typeof toastr !== 'undefined') toastr[type === 'success' ? 'success' : 'error'](message);
    };

    const escapeHtml = (text) => String(text ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    // ─── Handlers ──────────────────────────────────────────────────────────
    $(function () {
        $(document).on('click', '.btn-set-invoice', function () {
            const orderId = $(this).data('order-id');
            const $row = $(this).closest('tr');
            const orderNumber = ($row.find('td').first().text() || '').replace(/^#/, '').trim();
            openModal(orderId, orderNumber);
        });

        $(document).on('click', '.invoice-mode-btn', function () {
            applyMode($(this).data('invoice-mode'));
            applyTicketBaseRule();
        });

        $(document).on('change', '#modal-set-invoice select[name="invoice_required"]', function () {
            const val = $(this).val();
            $modal().find('.invoice-flag').prop('disabled', val === '0' || isDefaultMode());
            if (val === '0') $modal().find('.invoice-flag').prop('checked', false);
        });

        $(document).on('change', '.invoice-flag[data-flag="ticket_base"]', applyTicketBaseRule);

        $(document).on('click', '.btn-wizard-next', function () {
            // Ricarica il preview coi flag correnti (Step 2 deve mostrare le righe effettive da fatturare).
            const payload = buildPayload();
            const $btn = $(this);
            $btn.prop('disabled', true);
            $.ajax({
                url: `/orders/${currentOrderId}/invoice/preview`,
                method: 'GET',
                data: { flags: payload.flags },
                dataType: 'json',
            }).done((res) => {
                preview = res;
                renderStep2();
                setStep(2);
            }).fail(() => {
                if (typeof toastr !== 'undefined') toastr.error('Errore nel ricalcolo delle voci fattura');
            }).always(() => $btn.prop('disabled', false));
        });

        $(document).on('click', '.invoice-wizard-tab', function () {
            const target = parseInt($(this).data('step'), 10);
            if (target === 2) {
                renderStep2();
            }
            setStep(target);
        });

        $(document).on('click', '.btn-wizard-submit', submit);
        // x-textarea gestisce già il contatore caratteri via oninput inline.
    });

    // Esposto per orders/show (bottone Imposta fattura nel dettaglio ordine)
    window.openInvoiceWizard = openModal;
})();
