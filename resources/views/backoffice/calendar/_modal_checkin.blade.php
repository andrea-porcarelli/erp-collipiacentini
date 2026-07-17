<div class="modal" tabindex="-1" id="modal-order-checkin">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-miticko">
            <div class="modal-header">
                <h1 class="modal-title js-checkin-title">Ordine</h1>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span class="fa-regular fa-times"></span>
                </button>
            </div>
            <div class="modal-body w-100">
                <div id="calendar-checkin-body">
                    <div class="calendar-loading">Caricamento…</div>
                </div>
            </div>
            <div class="modal-footer">
                <x-button label="Annulla" class="btn-cancel" emphasis="Low" :dataset="['bs-dismiss' => 'modal']" />
                <x-button label="Salva" class="btn-success js-checkin-save" emphasis="High" />
            </div>
        </div>
    </div>
</div>
