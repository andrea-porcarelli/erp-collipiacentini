@auth
    @if(in_array(Auth::user()->role, ['admin', 'partner']))
        <link rel="stylesheet" href="{{ asset('backoffice/css/ticket-scanner.css') }}?v={{ filemtime(public_path('backoffice/css/ticket-scanner.css')) }}">

        <button type="button" class="ticket-scanner-fab" id="ticket-scanner-fab" aria-label="Scansiona biglietto">
            <i class="fa-solid fa-qrcode" aria-hidden="true"></i>
        </button>

        <div class="ticket-scanner-overlay" id="ticket-scanner-overlay" role="dialog" aria-hidden="true" aria-labelledby="ticket-scanner-title">
            <div class="ticket-scanner-overlay-inner">
                <div class="ticket-scanner-overlay-header">
                    <div class="ticket-scanner-overlay-title" id="ticket-scanner-title">Scansiona biglietto</div>
                    <button type="button" class="ticket-scanner-overlay-close" data-role="close-scanner" aria-label="Chiudi scanner">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="ticket-scanner-reader-wrapper">
                    <div id="ticket-scanner-reader" class="ticket-scanner-reader"></div>
                    <div class="ticket-scanner-frame" aria-hidden="true"></div>
                </div>

                <div class="ticket-scanner-status" data-role="scanner-status">
                    Inquadra il QR code del biglietto
                </div>
                <div class="ticket-scanner-error" data-role="scanner-error" hidden></div>
            </div>
        </div>

        <div class="ticket-scanner-drawer" id="ticket-scanner-drawer" role="dialog" aria-hidden="true" aria-labelledby="ticket-scanner-drawer-title">
            <div class="ticket-scanner-drawer-panel">
                <div class="ticket-scanner-drawer-header">
                    <div class="ticket-scanner-drawer-title" id="ticket-scanner-drawer-title" data-role="drawer-title">Dettagli prenotazione</div>
                    <button type="button" class="ticket-scanner-drawer-close" data-role="close-drawer" aria-label="Chiudi">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="ticket-scanner-drawer-body" data-role="drawer-body">
                    <div class="ticket-scanner-loading">Caricamento…</div>
                </div>
                <div class="ticket-scanner-drawer-footer">
                    <button type="button" class="ts-btn-primary" data-role="save-changes">
                        Salva
                    </button>
                </div>
            </div>
        </div>

        <script src="{{ asset('backoffice/js/ticket-scanner.js') }}?v={{ filemtime(public_path('backoffice/js/ticket-scanner.js')) }}" defer></script>
    @endif
@endauth
