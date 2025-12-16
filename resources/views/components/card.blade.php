@props(['title' => null, 'sub_title' => null, 'size' => 'medium', 'mode' => 'white', 'class' => null])

<div class="card-miticko {{ $class }}" data-mode="{{ $size }} {{ $mode }} light mode">
    @if(isset($title) or isset($sub_title))
        <div class="card-header">
            @isset($title)
            <h2 class="mb-0">{{ $title }}</h2>
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
