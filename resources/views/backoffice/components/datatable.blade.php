<div class="actions">
    @if (in_array('impersonate', $options) && Auth::user()->canImpersonate() && $item->canBeImpersonated())
        <a href="{{ route('impersonate', $item->id) }}" title="Impersona">
            <x-button label="Impersona" size="small" leading="fa-user-secret" emphasis="outlined" status="warning"/>
        </a>
    @endif
    @if (in_array('edit', $options))
        <a href="{{ route( $route . '.show', $item->id) }}" title="Modifica">
            <x-button label="Modifica" size="small" leading="fa-edit" emphasis="outlined"/>
        </a>
    @endif
    @if (in_array('status', $options))
            <x-button
                label="{{ ($item->is_active) ? 'Attivo' : 'Disattivo' }}"
                status="{{ ($item->is_active) ? 'success' : 'error' }}"
                size="small"
                class="btn-status"
                emphasis="outlined"
                leading="{{ (!$item->is_active) ? 'fa-times' : 'fa-check' }}"
                :dataset="['route' => $route, 'id' => $item->id, 'is-active' => $item->is_active]"
            />
    @endif
</div>
