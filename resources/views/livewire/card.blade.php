<div class="card">
    <div class="card-body">
        @isset($title)
            <div class="card-title @if($title_center) text-center @endif ">
                {{ $title }}
            </div>
        @endisset
        {!! $body !!}
    </div>
</div>
