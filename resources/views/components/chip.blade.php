 @props([
    'appearance' => 'Active',
    'label' => null,
    'dataset' => []
])
<div
    class="chip-miticko"
    data-mode="chipAppearance-{{ $appearance }}"
    @if(!empty($dataset))
        @foreach($dataset as $attribute => $value)
            data-{{ $attribute }}="{{ $value }}"
      @endforeach
    @endif
>
    {{ $label }}
</div>
