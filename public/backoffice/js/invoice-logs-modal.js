// Modale "Dettaglio invio fattura": elenca, per ciascuna Invoice associata all'ordine,
// lo storico di tutti i tentativi di invio verso il provider di fatturazione (payload
// JSON, status HTTP, response body, eventuale eccezione, utente autore).
(function () {
    const $modal = () => $('#modal-invoice-logs');

    const escapeHtml = (text) => String(text ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const jsonPretty = (v) => {
        if (v === null || v === undefined) return '';
        try {
            return JSON.stringify(v, null, 2);
        } catch (e) {
            return String(v);
        }
    };

    const recipientLabel = (type) => type === 'partner' ? 'Fattura al Partner' : type === 'customer' ? 'Fattura al Cliente' : (type || '—');

    const statusLabel = (status) => {
        if (!status) return { text: '—', ok: false };
        const n = parseInt(status, 10);
        return { text: `HTTP ${n}`, ok: n >= 200 && n < 300 };
    };

    const openModal = (orderId) => {
        $modal().find('#invoice-logs-loading').removeClass('d-none');
        $modal().find('#invoice-logs-content').addClass('d-none').empty();
        $modal().find('[data-slot="order-number"]').text('');
        $modal().modal('show');

        $.ajax({
            url: `/orders/${orderId}/invoices/logs`,
            method: 'GET',
            dataType: 'json',
        }).done((res) => {
            $modal().find('[data-slot="order-number"]').text('#' + (res.order_number || ''));
            render(res);
            $modal().find('#invoice-logs-loading').addClass('d-none');
            $modal().find('#invoice-logs-content').removeClass('d-none');
        }).fail(() => {
            if (typeof toastr !== 'undefined') toastr.error('Errore nel caricamento dei log di invio');
            $modal().modal('hide');
        });
    };

    const render = (data) => {
        const invoices = data.invoices || [];
        const $container = $modal().find('[data-slot="invoices-container"]').empty();

        if (invoices.length === 0) {
            $container.append('<div class="inv-log-empty">Nessuna fattura emessa per questo ordine.</div>');
            return;
        }

        invoices.forEach((inv) => {
            const emittedBy = inv.emitted_by ? escapeHtml(inv.emitted_by) : '—';
            const emittedAt = inv.emitted_at ? escapeHtml(inv.emitted_at) : '—';
            const number = inv.number ? escapeHtml(inv.number) : '—';
            const providerId = inv.provider_id ? escapeHtml(inv.provider_id) : '—';
            const status = escapeHtml((inv.status || '').toUpperCase());
            const providerName = escapeHtml(inv.provider || '—');

            const logsHtml = (inv.logs || []).map((log) => {
                const st = statusLabel(log.response_status);
                const hasPayload = log.request_payload !== null && log.request_payload !== undefined;
                const hasBody = log.response_body !== null && log.response_body !== undefined;
                const hasError = !!log.error_message;

                return `
                    <div class="inv-log-row">
                        <div class="inv-log-row-header">
                            <i class="fa-regular fa-chevron-right toggle-icon"></i>
                            <span class="inv-log-attempt">#${log.attempt}</span>
                            <span>${escapeHtml(log.created_at || '—')}</span>
                            <span class="text-secondary">· ${escapeHtml(log.user || 'sistema')}</span>
                            <span class="ms-auto inv-log-status ${st.ok ? 'ok' : 'ko'}">${st.text}</span>
                        </div>
                        <div class="inv-log-body">
                            ${hasError ? `<div class="inv-log-error"><b>Eccezione:</b> ${escapeHtml(log.error_message)}</div>` : ''}
                            ${hasPayload ? `
                                <div class="inv-log-section-title">Payload inviato (JSON)</div>
                                <pre>${escapeHtml(jsonPretty(log.request_payload))}</pre>
                            ` : ''}
                            ${hasBody ? `
                                <div class="inv-log-section-title">Risposta provider</div>
                                <pre>${escapeHtml(jsonPretty(log.response_body))}</pre>
                            ` : ''}
                        </div>
                    </div>
                `;
            }).join('') || '<div class="inv-log-empty">Nessun tentativo registrato.</div>';

            $container.append(`
                <div class="inv-log-invoice">
                    <h4>${recipientLabel(inv.recipient_type)} — ${escapeHtml(inv.recipient_name || '—')}</h4>
                    <div class="inv-log-meta">
                        <span><b>Stato:</b> ${status}</span>
                        <span><b>Provider:</b> ${providerName}</span>
                        <span><b>N° documento:</b> ${number}</span>
                        <span><b>ID provider:</b> ${providerId}</span>
                        <span><b>Emessa il:</b> ${emittedAt}</span>
                        <span><b>Da:</b> ${emittedBy}</span>
                    </div>
                    ${inv.provider_error ? `<div class="inv-log-error"><b>Ultimo errore:</b> ${escapeHtml(inv.provider_error)}</div>` : ''}
                    <div class="mt-2">${logsHtml}</div>
                </div>
            `);
        });
    };

    $(function () {
        $(document).on('click', '.btn-invoice-logs', function () {
            const orderId = $(this).data('order-id');
            if (orderId) openModal(orderId);
        });

        $(document).on('click', '#modal-invoice-logs .inv-log-row-header', function () {
            $(this).closest('.inv-log-row').toggleClass('open');
        });
    });

    // Esposto per compatibilità con altri script che vogliano aprirla a mano.
    window.openInvoiceLogsModal = openModal;
})();
