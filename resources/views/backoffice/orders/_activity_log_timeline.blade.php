@php
    // Raggruppa entry consecutive con stesso batch_uuid (singola "operazione").
    $clusters = [];
    foreach ($entries as $entry) {
        $key = $entry->batch_uuid ?: 'single-' . $entry->id;
        $clusters[$key][] = $entry;
    }
@endphp

<ul class="order-activity-timeline">
    @foreach($clusters as $clusterKey => $clusterEntries)
        @php($isBatch = count($clusterEntries) > 1)
        @foreach($clusterEntries as $idx => $log)
            <li class="order-activity-item {{ $isBatch ? 'order-activity-batch' : '' }}">
                <span class="order-activity-icon">
                    <i class="fa-solid {{ $log->event_icon }}"></i>
                </span>
                <div class="order-activity-title">{{ $log->event_label }}</div>
                <div class="order-activity-desc">{{ $log->description }}</div>
                <div class="order-activity-meta">
                    <i class="fa-regular fa-user"></i>
                    {{ $log->causer_name ?? 'Sistema' }}
                    <span class="mx-2">·</span>
                    <i class="fa-regular fa-clock"></i>
                    {{ $log->created_at->translatedFormat('j M Y') }} alle {{ $log->created_at->format('H:i:s') }}
                </div>
                @if(!empty($log->properties))
                    @php($logKey = 'order-activity-details-' . $log->id)
                    <a href="#" class="order-activity-toggle"
                       data-bs-toggle="collapse" data-bs-target="#{{ $logKey }}"
                       aria-expanded="false" onclick="event.preventDefault()">
                        <i class="fa-solid fa-chevron-down"></i> Dettagli
                    </a>
                    <div id="{{ $logKey }}" class="collapse">
                        <pre class="order-activity-details">{{ json_encode($log->properties, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) }}</pre>
                    </div>
                @endif
            </li>
        @endforeach
    @endforeach
</ul>
