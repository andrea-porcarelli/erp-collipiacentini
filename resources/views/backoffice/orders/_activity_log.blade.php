@php
    $groupLabels = [
        'cart'    => 'Carrello',
        'order'   => 'Ordine',
        'edits'   => 'Modifiche',
        'comms'   => 'Comunicazioni',
        'checkin' => 'Check-in',
        'other'   => 'Altro',
    ];
    $grouped = $logs->groupBy(fn ($l) => $l->event_group);
@endphp

<x-card title="Storico attività">
    @if($logs->isEmpty())
        <div class="text-secondary">Nessuna attività registrata per questo ordine.</div>
    @else
        <ul class="nav nav-tabs order-activity-tabs mb-spacing-l" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#order-activity-all" type="button" role="tab">
                    Tutto <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $logs->count() }}</span>
                </button>
            </li>
            @foreach($groupLabels as $key => $label)
                @php($groupLogs = $grouped->get($key, collect()))
                @if($groupLogs->isNotEmpty())
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#order-activity-{{ $key }}" type="button" role="tab">
                            {{ $label }} <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $groupLogs->count() }}</span>
                        </button>
                    </li>
                @endif
            @endforeach
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="order-activity-all" role="tabpanel">
                @include('backoffice.orders._activity_log_timeline', ['entries' => $logs])
            </div>
            @foreach($groupLabels as $key => $label)
                @php($groupLogs = $grouped->get($key, collect()))
                @if($groupLogs->isNotEmpty())
                    <div class="tab-pane fade" id="order-activity-{{ $key }}" role="tabpanel">
                        @include('backoffice.orders._activity_log_timeline', ['entries' => $groupLogs])
                    </div>
                @endif
            @endforeach
        </div>
    @endif
</x-card>

@push('styles')
    <style>
        .order-activity-tabs { border-bottom: 1px solid #E5E7EB; }
        .order-activity-tabs .nav-link {
            color: #6B7280;
            border: 0;
            border-bottom: 2px solid transparent;
            padding: .5rem .85rem;
            font-weight: 600;
        }
        .order-activity-tabs .nav-link.active {
            color: var(--brand-secondary-brand, #3948D3);
            background: transparent;
            border-bottom-color: var(--brand-secondary-brand, #3948D3);
        }

        .order-activity-timeline { list-style: none; padding: 0; margin: 0; position: relative; }
        .order-activity-timeline::before {
            content: '';
            position: absolute;
            left: 19px; top: 8px; bottom: 8px;
            width: 2px; background: #E5E7EB;
        }
        .order-activity-item { position: relative; padding-left: 52px; padding-bottom: 1rem; }
        .order-activity-item:last-child { padding-bottom: 0; }
        .order-activity-icon {
            position: absolute; left: 0; top: 0;
            width: 40px; height: 40px; border-radius: 50%;
            background: #EAEEFA; color: #3948D3;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 16px;
        }
        .order-activity-title { font-weight: 600; color: #111827; }
        .order-activity-desc { color: #374151; margin-top: 2px; }
        .order-activity-meta { color: #6B7280; font-size: .85rem; margin-top: 4px; }
        .order-activity-meta i { margin-right: 4px; }
        .order-activity-details {
            margin-top: 8px; padding: 8px 10px;
            background: #F9FAFB; border-radius: 6px;
            font-size: .8rem; color: #4B5563;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            white-space: pre-wrap; word-break: break-word;
        }
        .order-activity-toggle { font-size: .85rem; }
        .order-activity-batch {
            border-left: 3px solid #C7D2FE; padding-left: 10px; margin-left: -10px;
        }
    </style>
@endpush
