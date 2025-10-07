<div class="actions">
    @if (in_array('edit', $options))
        <a href="{{ route( ($path ?? $model) . '.show', $item->id) }}" title="Modifica">
            <button class="btn  btn-xs btn-info white">
                <span class="fa fa-edit"></span>
            </button>
        </a>
    @endif
    @if (in_array('remove', $options))
        <button
            class="btn btn-xs btn-danger btn-remove"
            title="Elimina"
            data-model="{{ $model }}"
            data-id="{{ $item->id }}"
        >
            <span class="fa fa-trash"></span>
        </button>
    @endif
    @if (in_array('remove-custom', $options))
        <button
            class="btn btn-xs btn-danger btn-remove-custom"
            title="Elimina"
            data-model="{{ $model }}"
            data-confirm="Sei sicuro di voler eliminare l'esercizio e tutti gli elementi collegati (risposte, soluzioni e tentativi degli studenti) ?"
            data-id="{{ $item->id }}"
        >
            <span class="fa fa-trash"></span>
        </button>
    @endif
    @if (in_array('status', $options))
        <button
            class="btn btn-xs {{ (!$item->is_active) ? 'btn-danger' : 'btn-success' }} btn-status"
            title="{{ (!$item->is_active) ? 'Attiva' : 'Disattiva' }}"
            data-model="{{ $route ?? ($path ?? $model) }}"
            data-id="{{ $item->id }}"
        >
            <span class="fa {{ (!$item->is_active) ? 'fa-times' : 'fa-check' }}"></span>
        </button>
    @endif
    @if (in_array('premium', $options))
        <button
            class="btn btn-xs {{ (!$item->is_premium) ? 'btn-danger' : 'btn-success' }} btn-premium"
            title="{{ (!$item->is_premium) ? 'Rendi Premium' : 'Rimuovi da Premium' }}"
            data-model="{{ $route ?? ($path ?? $model) }}"
            data-id="{{ $item->id }}"
        >
            <i class="fas fa-certificate"></i>
        </button>
    @endif
    @if (in_array('ics', $options))
        <a href="{{ route('listings.icals', $item->id) }}" target="_blank">
        <button
            class="btn btn-xs btn-warning"
            title="Scarica ICal"
        >
            <i class="fa fa-calendar"></i>
        </button>
        </a>
    @endif
    @if (in_array('details', $options))

        <a href="{{ route($model . '.show', $item->id) }}" title="Modifica">
            <button class="btn btn-xs btn-success white btn-details" title="Dettagli">
                <span class="fa fa-edit"></span> Dettagli
            </button>
        </a>
    @endif
    @if (in_array('order', $options))
        <button
            class="btn btn-xs btn-info white sort-row"
            title="Ordina"
            data-id="{{ $item->id }}"
        >
            <i class="fas fa-sort"></i>
        </button>
    @endif
    @if (in_array('impersonate', $options) && $item->id !== Auth::id() && $role === 'admin' && $item->is_active)
        <a href="{{ route('impersonate', $item->id) }}">
            <button
                class="btn btn-xs"
                title="Impersonifica utente"
                data-id="{{ $item->id }}"
            >
                <i class="fas fa-user-secret"></i>
            </button>
        </a>
    @endif
    @if (in_array('execute', $options))
        <button
            class="btn btn-primary btn-sm btn-execute-exercise"
            title="Esegui esercizio"
            data-id="{{ $item->id }}"
        >
            <i class="fas fa-pen-alt"></i> Esegui
        </button>
    @endif
</div>
