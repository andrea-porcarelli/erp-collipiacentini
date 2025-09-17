@props(['icon' => 'fa-solid fa-circle-info', 'alert' => 'default', 'message' => ''])

<div class="supporting-text {{ $alert }}">
    @if($icon)
        <i class="{{ $icon }}"></i>
    @endif
    {{ $message }}
</div>
