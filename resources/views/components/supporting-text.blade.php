@props([
    'icon' => null,
    'appearance' => 'Default',
    'message' => '',
    'id' => null,
    'extra_class' => null,
])
<div data-mode="SupptextAppearance-{{ $appearance }}">
    <div class="supporting-text {{ $extra_class }}" @if($id) id="{{ $id }}" @endif>
        @isset($icon)
            <i class="{{ $icon }}"></i>
        @endisset
        {{ $message }}
    </div>
</div>
