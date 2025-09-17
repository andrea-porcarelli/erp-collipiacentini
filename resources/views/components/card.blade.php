@props(['title' => null, 'class' => ''])

<div {{ $attributes->merge(['class' => 'card '.$class]) }}>
    @if($title)
        <div class="card-header"><h3 class="mb-0">{{ $title }}</h3></div>
    @endif

    <div class="card-body">
        {{ $slot }}
    </div>

    @if(isset($footer))
        <div class="card-footer">
            {{ $footer }}
        </div>
    @endif
</div>
