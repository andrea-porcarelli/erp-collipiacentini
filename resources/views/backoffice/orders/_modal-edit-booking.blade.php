@php($firstOp = $order->orderProducts->first())
@php($currentDate = $firstOp?->booking_date ? \Carbon\Carbon::parse($firstOp->booking_date)->format('Y-m-d') : '')
@php($currentTime = $firstOp?->booking_time ? substr($firstOp->booking_time, 0, 5) : '')
<x-modal id="modal-edit-booking" title="Modifica data/ora" primary="Salva" secondary="annulla">
    <form id="form-edit-booking" autocomplete="off">
        <input type="hidden" name="booking_date" id="booking-date-input" value="{{ $currentDate }}">
        <input type="hidden" name="booking_time" id="booking-time-input" value="{{ $currentTime }}">
        <input type="hidden" name="slot_type" id="booking-slot-type-input" value="{{ $firstOp?->slot_type ?? '' }}">
        <input type="hidden" name="slot_id" id="booking-slot-id-input" value="{{ $firstOp?->slot_id ?? '' }}">

        <div class="booking-edit">
            <div class="booking-chips">
                <button type="button" class="booking-chip booking-chip-date" data-role="booking-chip-date">
                    <i class="fa-regular fa-calendar"></i>
                    <span class="booking-chip-label" data-role="chip-date-label">
                        @if($currentDate)
                            {{ \Carbon\Carbon::parse($currentDate)->translatedFormat('j M y') }}
                        @else
                            Seleziona data
                        @endif
                    </span>
                </button>
                <button type="button" class="booking-chip booking-chip-time" data-role="booking-chip-time" disabled>
                    <i class="fa-regular fa-clock"></i>
                    <span class="booking-chip-label" data-role="chip-time-label">
                        {{ $currentTime ?: 'Orario' }}
                    </span>
                </button>
            </div>

            <div class="booking-step booking-step-calendar" data-step="calendar">
                <div id="booking-calendar" class="booking-calendar-inline"></div>
            </div>

            <div class="booking-step booking-step-slots d-none" data-step="slots">
                <div id="booking-time-slots" class="booking-time-slots"></div>
            </div>
        </div>
    </form>
</x-modal>
