@foreach($slots as $slot)
    <div class="slot-item" data-slot-id="{{ $slot->id }}">
        <div class="slot-item-header">
            <div class="d-flex align-items-center gap-2">
                <span class="slot-time-label">{{ substr($slot->time, 0, 5) }}</span>
                <span class="slot-badge-modified">MODIFICATO</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn-slot-action btn-slot-delete" title="Elimina">
                    <i class="fa-regular fa-trash-can"></i>
                </button>
                <button type="button" class="btn-slot-action btn-slot-toggle" title="Espandi">
                    <i class="fa-regular fa-chevron-down"></i>
                </button>
            </div>
        </div>
        <div class="slot-item-body">
            @livewire('slot-variants', ['slot' => $slot], key('sv-'.$slot->id))
        </div>
    </div>
@endforeach
