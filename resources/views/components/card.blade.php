@props(['title' => null, 'sub_title' => null, 'pre_title' => null, 'size' => 'medium', 'mode' => 'white', 'class' => null, 'h1' => null, 'leading' => null])

<div class="card-miticko {{ $class }}" data-mode="{{ $size }} {{ $mode }} light mode">
    @if(isset($title) or isset($sub_title) or isset($pre_title))
        <div class="card-header">
            @isset($pre_title)

                <small class="mb-0">
                    @isset($leading)
                        <i class="fa-regular {{ $leading }} icon"></i>
                    @endif
                        {{ $pre_title }}
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
                <p>{!! $sub_title !!}</p>
            @endisset
        </div>
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
