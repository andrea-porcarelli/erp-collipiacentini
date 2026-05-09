<x-modal id="modal-edit-notes" title="Modifica note" primary="Salva" secondary="Annulla">
    <form id="form-edit-notes">
        <x-textarea
            label="Note cliente"
            name="customer_note"
            :value="$order->customer_note"
            rows="4"
            maxlength="2000"
        />
        <div class="mt-spacing-l">
            <x-textarea
                label="Note interne"
                name="internal_note"
                :value="$order->internal_note"
                rows="4"
                maxlength="2000"
            />
        </div>
    </form>
</x-modal>
