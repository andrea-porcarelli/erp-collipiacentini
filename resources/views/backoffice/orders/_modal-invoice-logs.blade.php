{{-- Modale "Dettaglio invio al provider di fatturazione" (Fatture in Cloud).
     Mostra, per ciascuna Invoice dell'ordine, l'elenco cronologico dei tentativi:
     payload JSON spedito, status HTTP, body di risposta, eventuale eccezione, utente. --}}
<style>
    #modal-invoice-logs .modal-body { padding: 16px 24px; max-height: calc(100vh - 180px); overflow-y: auto; }
    #modal-invoice-logs .inv-log-invoice { border: 1px solid #F0F1F5; border-radius: 10px; padding: 14px; margin-bottom: 14px; background: #FAFAFA; }
    #modal-invoice-logs .inv-log-invoice h4 { font-size: 14px; font-weight: 700; margin: 0 0 8px 0; }
    #modal-invoice-logs .inv-log-meta { font-size: 12px; color: #666; margin-bottom: 10px; display: flex; flex-wrap: wrap; gap: 10px 20px; }
    #modal-invoice-logs .inv-log-meta b { color: #0D0D0D; }
    #modal-invoice-logs .inv-log-row { border-top: 1px solid #E7E9F0; padding: 10px 0; }
    #modal-invoice-logs .inv-log-row:first-of-type { border-top: none; }
    #modal-invoice-logs .inv-log-row-header { display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 13px; }
    #modal-invoice-logs .inv-log-row-header .toggle-icon { transition: transform .15s; }
    #modal-invoice-logs .inv-log-row.open .inv-log-row-header .toggle-icon { transform: rotate(90deg); }
    #modal-invoice-logs .inv-log-attempt { font-weight: 700; min-width: 40px; }
    #modal-invoice-logs .inv-log-status { font-weight: 700; }
    #modal-invoice-logs .inv-log-status.ok { color: #1F7D4D; }
    #modal-invoice-logs .inv-log-status.ko { color: #D93025; }
    #modal-invoice-logs .inv-log-body { display: none; margin-top: 10px; }
    #modal-invoice-logs .inv-log-row.open .inv-log-body { display: block; }
    #modal-invoice-logs .inv-log-section-title { font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #999; margin: 10px 0 4px; }
    #modal-invoice-logs pre { background: #0D0D0D; color: #E7E9F0; padding: 10px 12px; border-radius: 6px; font-size: 11px; line-height: 1.4; max-height: 320px; overflow: auto; margin: 0; }
    #modal-invoice-logs .inv-log-error { background: #FFF3F2; border: 1px solid #FFD4CF; color: #B22617; padding: 8px 12px; border-radius: 6px; font-size: 12px; margin-top: 6px; }
    #modal-invoice-logs .inv-log-empty { color: #666; font-size: 13px; font-style: italic; padding: 10px; }
</style>

<div class="modal fade" tabindex="-1" id="modal-invoice-logs">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-lg-down modal-lg">
        <div class="modal-content modal-miticko">
            <div class="modal-header">
                <h1 class="modal-title">Dettaglio invio fattura <span data-slot="order-number" class="text-secondary"></span></h1>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span class="fa-regular fa-times"></span>
                </button>
            </div>
            <div class="modal-body w-100">
                <div id="invoice-logs-loading" class="text-center py-4 w-100">
                    <i class="fa-regular fa-spinner fa-spin"></i>
                    <div class="small text-secondary mt-2">Caricamento log...</div>
                </div>
                <div id="invoice-logs-content" class="d-none" data-slot="invoices-container"></div>
            </div>
            <div class="modal-footer">
                <x-button label="chiudi" class="btn-cancel" status="Neutral" emphasis="Low" size="Medium" :dataset="['bs-dismiss' => 'modal']" />
            </div>
        </div>
    </div>
</div>
