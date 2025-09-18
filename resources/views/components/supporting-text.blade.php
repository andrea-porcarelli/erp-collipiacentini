@props([
    'icon' => null,
    'alert' => 'default',
    'message' => ''
])

<div class="supporting-text {{ $alert }}">
    @isset($icon)
        <i class="{{ $icon }}"></i>
    @endisset
    {{ $message }}
</div>
