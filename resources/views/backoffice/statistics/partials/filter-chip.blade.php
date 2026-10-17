@php
    /** @var string $name */
    /** @var string $label */
    /** @var iterable $options */
    /** @var array $selected */
    $count = count($selected);
    $mode = $count > 0 ? 'chipAppearance-Active' : 'chipAppearance-Inactive';
    $menuId = 'chip-filter-'.$name;
@endphp
<div class="chip-filter" data-name="{{ $name }}">
    <span class="chip-miticko chip-filter-trigger" data-mode="{{ $mode }}">
        {{ $label }}@if($count > 0) <span class="chip-filter-count">({{ $count }})</span>@endif
        <i class="fa fa-chevron-down ms-1" style="font-size: 10px;"></i>
    </span>
    <div class="chip-filter-menu" id="{{ $menuId }}">
        @forelse($options as $opt)
            <label class="chip-filter-item">
                <input type="checkbox" value="{{ $opt['value'] }}" @checked(in_array($opt['value'], $selected, false))>
                <span>{{ $opt['label'] }}</span>
            </label>
        @empty
            <div class="text-muted small p-2">Nessuna opzione disponibile.</div>
        @endforelse
        <div class="chip-filter-actions">
            <button type="button" class="btn-link btn-clear">Azzera</button>
            <button type="button" class="btn-apply">Applica</button>
        </div>
    </div>
</div>
