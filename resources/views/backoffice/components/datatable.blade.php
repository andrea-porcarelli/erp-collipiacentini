<div class="actions">
    @if (in_array('impersonate', $options) && Auth::user()->canImpersonate() && $item->canBeImpersonated())
        <a href="{{ route('impersonate', $item->id) }}" title="Impersona">
            <x-button label="Impersona" size="Small" leading="fa-user-secret"/>
        </a>
    @endif
    @if (in_array('edit', $options))
        <a href="{{ route( $route . '.show', $item->id) }}" title="Modifica">
            <x-icon-button label="" size="Small" icon="fa-pen" />
        </a>
    @endif
    @if (in_array('status', $options))
            <x-button
                label="{{ ($item->is_active) ? 'Attivo' : 'Disattivo' }}"
                status="{{ ($item->is_active) ? 'Success' : 'Error' }}"
                size="Small"
                class="btn-status"
                leading="{{ (!$item->is_active) ? 'fa-times' : 'fa-check' }}"
                :dataset="['route' => $route, 'id' => $item->id, 'is-active' => $item->is_active]"
            />
    @endif
    @if (in_array('preview', $options))
            <x-button
                class="btn-preview-order"
                size="Small"
                emphasis="Low"
                leading="fa-eye"
                :dataset="['order-id' => $item->id]"
            />
    @endif
    @if (in_array('detail', $options))
            <a href="{{ route( $route . '.show', $item->id) }}" title="Modifica">
            <x-button
                status="Primary"
                size="Small"
                emphasis="Medium"
                size="Small"
                leading="fa-chevron-right"
            />
            </a>
    @endif
</div>
