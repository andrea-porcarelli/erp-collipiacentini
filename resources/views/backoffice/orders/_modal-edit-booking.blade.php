@php($firstOp = $order->orderProducts->first())
<x-modal id="modal-edit-booking" title="Modifica data/orario" primary="Salva" secondary="Annulla">
    <form id="form-edit-booking">
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <x-input
                    label="Data visita"
                    name="booking_date"
                    type="date"
                    :value="$firstOp?->booking_date ? \Carbon\Carbon::parse($firstOp->booking_date)->format('Y-m-d') : ''"
                    required
                />
            </div>
            <div class="col-12 col-md-6">
                <x-input
                    label="Orario"
                    name="booking_time"
                    type="time"
                    :value="$firstOp?->booking_time ? substr($firstOp->booking_time, 0, 5) : ''"
                    required
                />
            </div>
        </div>
    </form>
</x-modal>
