@props([
    'leading' => null,
    'trailing' => null,
    'state' => 'Resting',
    'size' => 'Medium',
    'class' => '',
    'disabled' => null,
    'id' => null,
    'options' => [],
])

<div class="dropdown-field" data-mode="dropdownSize-{{ $size }} dropdownAppearance-{{ $state }}">
    <div class="dropdown-field-container">
    @isset($leading)
        <i class="fa-regular {{ $leading }} icon"></i>
    @endisset
        <select
            class="dropdown-miticko {{ $class }}"
            @isset($id) id="{{ $id }}" @endisset
            @if($disabled) disabled @endif
            @if(!empty($dataset))
                @foreach($dataset as $attribute => $value)
                    data-{{ $attribute }}="{{ $value }}"
                @endforeach
            @endif
            @if(!empty($aria))
                @foreach($aria as $attribute => $value)
                    aria-{{ $attribute }}="{{ $value }}"
              @endforeach
            @endif
        >
            <option value="">Scegli</option>
            @foreach($options as $option)
                <option @if(isset($default) && $default == $option['id']) selected @endif value="{{ $option['id'] }}">{{ $option['label'] }}</option>
            @endforeach
        </select>

        @isset($trailing)
            <i class="fa-regular {{ $trailing }} icon"></i>
        @endisset
        @isset($message)
            <x-supporting-text :message="$message" :icon="$icon"/>
        @endisset
    </div>
</div>
