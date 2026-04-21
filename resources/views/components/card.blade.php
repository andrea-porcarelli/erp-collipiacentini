@props([
    'title' => null,
    'sub_title' => null,
    'pre_title' => null,
    'size' => 'Medium',
    'mode' => 'White',
    'class' => null,
    'h1' => null,
    'leading' => null,
    'brelative' => null
])

<div class="card-miticko {{ $class }}" data-mode="cardSize-{{ $size }} cardAppearance-{{ $mode }}">
    @if(isset($title) or isset($sub_title) or isset($pre_title))
        <div class="card-header">
            @isset($pre_title)
                <small class="mb-0">
                    @isset($leading)
                        <i class="fa-regular {{ $leading }} icon"></i>
                    @endif
                        {!! html_entity_decode($pre_title, ENT_QUOTES | ENT_HTML5, 'UTF-8') !!}
                </small>
            @endisset
            @isset($title)
                @if(isset($h1))
                    <h1 class="mb-0">{{ $title }}</h1>
                @else
                    <h2 class="mb-0">{{ $title }}</h2>
                @endif
            @endisset
            @isset($sub_title)
                <p class="mt-spacing-m" style="max-width: 70%">{!! $sub_title !!}</p>
            @endisset
        </div>
    @endif

    <div class="card-body @isset($brelative) position-relative @endisset">
        {{ $slot }}
    </div>

    @if(isset($footer))
        <div class="card-footer">
            {{ $footer }}
        </div>
    @endif
</div>
