@props(['model', 'fieldTypes'])

@php
    // Mappa id => is_required per i campi già attivi sul prodotto
    $activeFields = $model->customerFields()->with('fieldType')->get()
        ->keyBy('customer_field_type_id');
@endphp

<form id="form-customer-fields">
    <div class="row g-3">
        <div class="col-12">
            <div class="customer-field-item d-flex align-items-center gap-3">
                <div class="d-flex align-items-center gap-2" style="min-width: 200px">
                    <input type="checkbox" class="form-check-input customer-field-enabled mt-0" checked disabled/>
                    <label class="form-check-label mb-0">
                        Nome (obbligatorio)
                    </label>
                </div>
                <div class="d-flex align-items-center gap-2 customer-field-required-wrap">
                    <input type="checkbox" class="form-check-input customer-field-required mt-0" checked disabled/>
                    <label class="form-check-label mb-0 text-secondary small" >
                        Obbligatorio
                    </label>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="customer-field-item d-flex align-items-center gap-3">
                <div class="d-flex align-items-center gap-2" style="min-width: 200px">
                    <input type="checkbox" class="form-check-input customer-field-enabled mt-0" checked disabled/>
                    <label class="form-check-label mb-0">
                        Cognome (obbligatorio)
                    </label>
                </div>
                <div class="d-flex align-items-center gap-2 customer-field-required-wrap">
                    <input type="checkbox" class="form-check-input customer-field-required mt-0" checked disabled/>
                    <label class="form-check-label mb-0 text-secondary small" >
                        Obbligatorio
                    </label>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="customer-field-item d-flex align-items-center gap-3">
                <div class="d-flex align-items-center gap-2" style="min-width: 200px">
                    <input type="checkbox" class="form-check-input customer-field-enabled mt-0" checked disabled/>
                    <label class="form-check-label mb-0">
                        Email (obbligatorio)
                    </label>
                </div>
                <div class="d-flex align-items-center gap-2 customer-field-required-wrap">
                    <input type="checkbox" class="form-check-input customer-field-required mt-0" checked disabled/>
                    <label class="form-check-label mb-0 text-secondary small" >
                        Obbligatorio
                    </label>
                </div>
            </div>
        </div>
        @foreach($fieldTypes as $type)
            @php
                $active = $activeFields->has($type->id);
                $required = $active && $activeFields[$type->id]->is_required;
            @endphp
            <div class="col-12">
                <div class="customer-field-item d-flex align-items-center gap-3"
                     data-field-id="{{ $type->id }}">
                    <div class="d-flex align-items-center gap-2" style="min-width: 200px">
                        <input
                            type="checkbox"
                            class="form-check-input customer-field-enabled mt-0"
                            id="field-enabled-{{ $type->id }}"
                            value="{{ $type->id }}"
                            @if($active) checked @endif
                        />
                        <label class="form-check-label mb-0" for="field-enabled-{{ $type->id }}">
                            {{ $type->label }}
                        </label>
                    </div>
                    <div class="d-flex align-items-center gap-2 customer-field-required-wrap" @if(!$active) style="opacity:.35;pointer-events:none" @endif>
                        <input
                            type="checkbox"
                            class="form-check-input customer-field-required mt-0"
                            id="field-required-{{ $type->id }}"
                            @if($required) checked @endif
                        />
                        <label class="form-check-label mb-0 text-secondary small" for="field-required-{{ $type->id }}">
                            Obbligatorio
                        </label>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</form>
