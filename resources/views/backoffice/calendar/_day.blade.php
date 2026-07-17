@if($data->isEmpty())
    <div class="calendar-empty-state">
        <i class="fa fa-calendar-xmark"></i>
        <p class="mb-0">Nessun prodotto attivo per questa data.</p>
    </div>
@else
    <div class="calendar-accordion">
        @if($groupBy === 'product')
            @foreach($data as $entry)
                @php
                    $product = $entry['product'];
                    $slots = $entry['slots'];
                    $accordionId = 'accordion-product-'.$product->id;
                @endphp
                <div class="calendar-accordion-item {{ $loop->first ? 'is-open' : '' }}">
                    <button type="button" class="calendar-accordion-header js-accordion-toggle"
                            aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                            aria-controls="{{ $accordionId }}">
                        <span class="title">{{ $product->label }}</span>
                        <i class="fa fa-chevron-down chevron"></i>
                    </button>
                    <div id="{{ $accordionId }}" class="calendar-accordion-body" @if(! $loop->first) hidden @endif>
                        <div class="calendar-slot-grid">
                            @foreach($slots as $slot)
                                @include('backoffice.calendar._slot_card', [
                                    'product' => $product,
                                    'slot'    => $slot,
                                    'date'    => $date,
                                ])
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            @foreach($data as $entry)
                @php
                    $time = $entry['time'];
                    $products = $entry['products'];
                    $accordionId = 'accordion-slot-'.str_replace(':', '', $time);
                @endphp
                <div class="calendar-accordion-item {{ $loop->first ? 'is-open' : '' }}">
                    <button type="button" class="calendar-accordion-header js-accordion-toggle"
                            aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                            aria-controls="{{ $accordionId }}">
                        <span class="title">{{ $time }}</span>
                        <i class="fa fa-chevron-down chevron"></i>
                    </button>
                    <div id="{{ $accordionId }}" class="calendar-accordion-body" @if(! $loop->first) hidden @endif>
                        <div class="calendar-slot-grid">
                            @foreach($products as $entryProduct)
                                @php
                                    $product = $entryProduct['product'];
                                    $slot = [
                                        'time'         => $time,
                                        'availability' => $entryProduct['availability'],
                                        'booked'       => $entryProduct['booked'],
                                        'orders_count' => $entryProduct['orders_count'],
                                        'capacity'     => $entryProduct['capacity'],
                                    ];
                                @endphp
                                @include('backoffice.calendar._slot_card', [
                                    'product'   => $product,
                                    'slot'      => $slot,
                                    'date'      => $date,
                                    'showLabel' => true,
                                ])
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
@endif
