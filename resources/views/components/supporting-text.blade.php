@props([
    'icon' => null,
    'alert' => 'default',
    'message' => '',
    'id' => null,
    'extra_class' => null,
])

<div class="supporting-text {{ $alert }} {{ $extra_class }}" @if($id) id="{{ $id }}" @endif>
    @isset($icon)
        <i class="{{ $icon }}"></i>
    @endisset
    {{ $message }}
</div>
