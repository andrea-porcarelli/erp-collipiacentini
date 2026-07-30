(function () {
    'use strict';

    var SCANNER_LIB_URL = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
    var READER_ID = 'ticket-scanner-reader';

    var libLoadingPromise = null;
    var scannerInstance = null;
    var cameraActive = false;
    var scannerPaused = false;
    var scanConsumed = false;
    var currentOrderId = null;
    var snackbarTimer = null;
    var $ = window.jQuery;

    function $overlay() { return $('#ticket-scanner-overlay'); }
    function $drawer() { return $('#ticket-scanner-drawer'); }
    function $drawerBody() { return $drawer().find('[data-role="drawer-body"]'); }
    function $scannerStatus() { return $overlay().find('[data-role="scanner-status"]'); }
    function $scannerError() { return $overlay().find('[data-role="scanner-error"]'); }
    function $snackbar() { return $('#ticket-scanner-snackbar'); }

    function showSnackbar(msg, type) {
        var $s = $snackbar();
        if (!$s.length) return;
        $s.attr('data-type', type || 'success').text(msg).addClass('is-visible');
        if (snackbarTimer) clearTimeout(snackbarTimer);
        snackbarTimer = setTimeout(function () { $s.removeClass('is-visible'); }, 2500);
    }

    function loadLib() {
        if (window.Html5Qrcode) return Promise.resolve();
        if (libLoadingPromise) return libLoadingPromise;
        libLoadingPromise = new Promise(function (resolve, reject) {
            var s = document.createElement('script');
            s.src = SCANNER_LIB_URL;
            s.async = true;
            s.onload = function () { resolve(); };
            s.onerror = function () {
                libLoadingPromise = null;
                reject(new Error('Impossibile caricare lo scanner QR.'));
            };
            document.head.appendChild(s);
        });
        return libLoadingPromise;
    }

    function openScanner() {
        var overlay = $overlay()[0];
        if (!overlay) return;
        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.classList.add('ticket-scanner-open');
        $scannerError().hide().text('');
        $scannerStatus().text('Inquadra il QR code del biglietto');

        if (scannerInstance && cameraActive && scannerPaused) {
            try {
                scannerInstance.resume();
                scannerPaused = false;
                scanConsumed = false;
                return;
            } catch (e) {
                scannerPaused = false;
            }
        }

        loadLib()
            .then(startScan)
            .catch(function (err) {
                showScannerError(err && err.message ? err.message : 'Errore caricamento scanner');
            });
    }

    function startScan() {
        if (cameraActive) return;
        if (!window.Html5Qrcode) {
            showScannerError('Libreria scanner non disponibile');
            return;
        }
        var readerEl = document.getElementById(READER_ID);
        if (!readerEl) return;
        readerEl.innerHTML = '';

        try {
            scannerInstance = new window.Html5Qrcode(READER_ID, { verbose: false });
        } catch (err) {
            showScannerError(err && err.message ? err.message : 'Errore inizializzazione scanner');
            return;
        }

        var config = {
            fps: 10,
            qrbox: function (w, h) {
                var minEdge = Math.min(w, h);
                var size = Math.floor(minEdge * 0.7);
                return { width: size, height: size };
            },
            aspectRatio: 1.0,
            // 0 = Html5QrcodeSupportedFormats.QR_CODE: evita cicli sprecati su altri formati
            formatsToSupport: [0],
            // Usa BarcodeDetector nativo (Chrome/Edge/Android) quando disponibile:
            // molto più veloce e affidabile del fallback ZXing pure-JS incluso nella libreria.
            experimentalFeatures: { useBarCodeDetectorIfSupported: true },
        };

        scanConsumed = false;

        scannerInstance.start(
            { facingMode: 'environment' },
            config,
            onScanSuccess,
            function () { /* silenzia errori frame */ }
        )
        .then(function () { cameraActive = true; })
        .catch(function (err) {
            // Fallback senza facingMode (alcuni browser desktop)
            scannerInstance.start(true, config, onScanSuccess, function () {})
                .then(function () { cameraActive = true; })
                .catch(function (err2) {
                    showScannerError((err2 && err2.message) || (err && err.message) || 'Impossibile accedere alla fotocamera');
                });
        });
    }

    function stopScan() {
        if (!scannerInstance) {
            cameraActive = false;
            scannerPaused = false;
            return Promise.resolve();
        }
        var inst = scannerInstance;
        var wasActive = cameraActive;
        scannerInstance = null;
        cameraActive = false;
        scannerPaused = false;
        if (!wasActive) {
            try { inst.clear(); } catch (e) {}
            return Promise.resolve();
        }
        return inst.stop()
            .then(function () { try { inst.clear(); } catch (e) {} })
            .catch(function () {});
    }

    function pauseScan() {
        if (!scannerInstance || !cameraActive || scannerPaused) return;
        try {
            scannerInstance.pause(false);
            scannerPaused = true;
        } catch (e) {
            stopScan();
        }
    }

    function closeScanner() {
        var overlay = $overlay()[0];
        if (overlay) {
            overlay.classList.remove('is-open');
            overlay.setAttribute('aria-hidden', 'true');
        }
        document.body.classList.remove('ticket-scanner-open');
        pauseScan();
    }

    function showScannerError(msg) {
        $scannerError().text(msg).show();
    }

    function extractCode(raw) {
        if (raw == null) return null;
        var trimmed = String(raw).trim();
        if (!trimmed) return null;
        if (/^https?:\/\//i.test(trimmed)) {
            var parts = trimmed.split(/[\/?#]/).filter(Boolean);
            return parts.pop() || trimmed;
        }
        return trimmed;
    }

    function onScanSuccess(decodedText) {
        if (scanConsumed) return;
        var code = extractCode(decodedText);
        if (!code) return;
        scanConsumed = true;
        $scannerStatus().text('Codice rilevato, caricamento dati…');
        pauseScan();
        fetchTicket(code);
    }

    function fetchTicket(code) {
        $.ajax({
            url: '/tickets/scan/' + encodeURIComponent(code),
            method: 'GET',
            dataType: 'json',
        })
        .done(function (response) {
            closeScanner();
            renderDrawer(response);
        })
        .fail(function (xhr) {
            var msg = 'Biglietto non trovato';
            if (xhr && xhr.responseJSON) {
                msg = xhr.responseJSON.response || xhr.responseJSON.message || msg;
            }
            showScannerError(msg);
            setTimeout(function () {
                if ($overlay().hasClass('is-open')) {
                    $scannerStatus().text('Inquadra il QR code del biglietto');
                    $scannerError().hide();
                    scanConsumed = false;
                    if (scannerInstance && scannerPaused) {
                        try {
                            scannerInstance.resume();
                            scannerPaused = false;
                        } catch (e) {
                            startScan();
                        }
                    } else {
                        startScan();
                    }
                }
            }, 1800);
        });
    }

    function renderDrawer(payload) {
        var html = (payload && payload.response) || '';
        var drawer = $drawer()[0];
        if (!drawer) return;

        $drawerBody().html(html);
        $drawerBody().scrollTop(0);
        currentOrderId = (payload && payload.order_id) || null;
        drawer.classList.add('is-open');
        drawer.setAttribute('aria-hidden', 'false');
        document.body.classList.add('ticket-scanner-drawer-open');

        flashScannedRow(payload && payload.scanned_id);
        bindDrawerEvents();
        updateCheckinCounter();
    }

    function flashScannedRow(participantId) {
        if (!participantId) return;
        var $row = $drawerBody().find('[data-participant-id="' + participantId + '"]');
        if (!$row.length) return;
        $row.addClass('ts-ticket-row-flash');
        setTimeout(function () { $row.removeClass('ts-ticket-row-flash'); }, 1800);
    }

    function closeDrawer() {
        var drawer = $drawer()[0];
        if (drawer) {
            drawer.classList.remove('is-open');
            drawer.setAttribute('aria-hidden', 'true');
        }
        document.body.classList.remove('ticket-scanner-drawer-open');
        currentOrderId = null;
    }

    function bindDrawerEvents() {
        var $body = $drawerBody();

        $body.off('change.ts', '[data-role="status-select"]')
            .on('change.ts', '[data-role="status-select"]', function () {
                var $sel = $(this);
                updateStatusClass($sel, $sel.val());
                updateCheckinCounter();
            });

        $body.off('click.ts', '[data-role="all-arrived"]')
            .on('click.ts', '[data-role="all-arrived"]', function () {
                $body.find('[data-role="status-select"]').each(function () {
                    var $sel = $(this);
                    $sel.val('checked_in');
                    updateStatusClass($sel, 'checked_in');
                });
                updateCheckinCounter();
            });
    }

    function updateStatusClass($sel, newStatus) {
        var classes = ['ts-status-booked', 'ts-status-checked_in', 'ts-status-no_show', 'ts-status-cancelled'];
        for (var i = 0; i < classes.length; i++) {
            $sel.removeClass(classes[i]);
        }
        $sel.addClass('ts-status-' + newStatus);
    }

    function updateCheckinCounter() {
        var $body = $drawerBody();
        var $selects = $body.find('[data-role="status-select"]');
        var total = $selects.length;
        var checked = $selects.filter(function () { return $(this).val() === 'checked_in'; }).length;
        $body.find('[data-role="checkin-count"]').text(checked);
        $body.find('[data-role="checkin-total"]').text(total);
    }

    function saveAllChanges($btn) {
        var $body = $drawerBody();
        var $selects = $body.find('[data-role="status-select"]');
        var changes = [];
        $selects.each(function () {
            var $sel = $(this);
            var original = $sel.attr('data-original');
            var current = $sel.val();
            if (current !== original) {
                var $row = $sel.closest('[data-participant-id]');
                changes.push({ id: parseInt($row.attr('data-participant-id'), 10), status: current });
            }
        });

        if (!changes.length) {
            showSnackbar('Nessuna modifica da salvare', 'info');
            return;
        }

        if ($btn) $btn.prop('disabled', true);

        $.ajax({
            url: '/tickets/batch-status',
            method: 'PUT',
            dataType: 'json',
            data: { participants: changes },
        })
        .done(function () {
            $selects.each(function () {
                $(this).attr('data-original', $(this).val());
            });
            showSnackbar('Modifiche salvate', 'success');
        })
        .fail(function (xhr) {
            var msg = 'Errore durante il salvataggio';
            if (xhr && xhr.responseJSON) {
                msg = xhr.responseJSON.response || xhr.responseJSON.message || msg;
            }
            showSnackbar(msg, 'error');
        })
        .always(function () {
            if ($btn) $btn.prop('disabled', false);
        });
    }

    function attachGlobalHandlers() {
        $(document)
            .on('click', '#ticket-scanner-fab', function () { openScanner(); })
            .on('click', '[data-role="close-scanner"]', function () { closeScanner(); })
            .on('click', '[data-role="close-drawer"]', function () { closeDrawer(); })
            .on('click', '[data-role="save-changes"]', function () { saveAllChanges($(this)); });
    }

    if (!$) return;

    if (document.readyState !== 'loading') {
        attachGlobalHandlers();
    } else {
        document.addEventListener('DOMContentLoaded', attachGlobalHandlers);
    }
})();
