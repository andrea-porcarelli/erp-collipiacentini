@php
    $isPreview = $isPreview ?? false;
    $time      = substr($slot->time, 0, 5);
@endphp
<div class="special-slot-item @if($isPreview) special-slot-item--preview @endif"
     @if($isPreview)
         data-preview="1"
         data-time="{{ $time }}"
         data-availability-id="{{ $slot->id }}"
     @else
         data-id="{{ $slot->id }}"
     @endif>
    <div class="special-slot-header">
        <span class="fw-medium" style="min-width:52px">{{ $time }}</span>
        @if($isPreview)
            <span class="text-secondary small fst-italic special-slot-preview-label">dal template settimanale</span>
        @endif
        <span class="special-slot-header-tools">
            <span class="text-secondary small flex-grow-1">Prenotazioni attive: 0</span>
            <button type="button" class="bt-miticko btn-special-slot-delete" data-mode="small primary bt-m-text-only"><i class="fa-regular fa-trash-can icon"></i></button>
            <button type="button" class="bt-miticko btn-special-slot-toggle" data-mode="small primary"><i class="fa-regular fa-chevron-down icon"></i></button>
        </span>
    </div>
    <div class="special-slot-body d-none p0" data-loaded="0">
        <hr style="color:#E6E6E6" class="mb-spacing-l">
        <div class="ssv-list"><p class="text-secondary small ssv-empty">Caricamento...</p></div>
        <div class="d-flex align-items-center justify-content-between mb-2">
            <x-button status="Secondary" emphasis="MediumLow" label="Aggiungi variante" trailing="fa-plus" class="btn-ssv-open-modal" />
        </div>
    </div>
</div>
