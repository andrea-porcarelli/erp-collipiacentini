@props([
    'id' => null,
    'title' => null,
    'caption' => null,
    'primary' => null,
    'secondary' => null,
    'width' => null,
])
<div class="modal" tabindex="-1" id="{{ $id }}">
    <div class="modal-dialog" @isset($width) style="width: {{ $width }}" @endisset>
        <div class="modal-content modal-miticko">
            <div class="modal-header">
                @isset($title)
                    <h1 class="modal-title">{{ $title }}</h1>
                @endisset
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span class="fa-regular fa-times"></span>
                </button>
            </div>
            <div class="modal-body w-100">
                {{ $slot }}
            </div>
            <div class="modal-footer">
                @isset($secondary)
                    <x-button :label="$secondary" class="btn-cancel" type="default" emphasis="text-only" size="small"/>
                @endisset
                @isset($primary)
                    <x-button :label="$primary" class="btn-success" size="small" emphasis="light" size="small"/>
                @endisset
            </div>
        </div>
    </div>
</div>
