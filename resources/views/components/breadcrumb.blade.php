@props([
    'first' => null,
    'second' => null
])
<div class="breadcrumb">
    @isset($first)
        <span class="anchor">
            <a  href="{{ route($first[1]) }}">
                {{ $first[0] }}
            </a>
        </span>
    @endisset
    @isset($second)
        <span>/</span>
        @isset($second[1])
        <a  href="{{ route($second[1]) }}">
        @endisset
            {{ $second[0] }}
        @isset($second[1])
        </a>
        @endisset
    @endisset
</div>
