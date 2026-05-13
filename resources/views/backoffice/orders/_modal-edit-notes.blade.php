<x-modal id="modal-edit-notes" title="Modifica note interne" primary="Salva" secondary="Annulla">
    <form id="form-edit-notes">
        <x-textarea
            label="Note interne"
            name="internal_note"
            :value="$order->internal_note"
            rows="6"
            maxlength="2000"
        />
    </form>
</x-modal>
