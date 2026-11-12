@props(['model', 'fieldTypes'])

@php
    $activeFields = $model->customerFields()->with('fieldType')->get()
        ->keyBy('customer_field_type_id');
@endphp

<form id="form-customer-fields" data-mode="typoBody-Small">
    <div class="row g-3">
        <div class="col-12">
            <div class="customer-field-item d-flex align-items-center gap-3">
                <div class="d-flex align-items-center gap-2" style="min-width: 200px">
                    <input type="checkbox" class="form-check-input customer-field-enabled mt-0" checked disabled/>
                    <label class="form-check-label mb-0">
                        Nome (obbligatorio)
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
            </div>
        </div>
        @foreach($fieldTypes as $type)
            @php
                $active = $activeFields->has($type->id);
            @endphp
            <div class="col-12">
                <div class="customer-field-item d-flex align-items-center gap-3" data-field-id="{{ $type->id }}">
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
                </div>
            </div>
        @endforeach
    </div>
</form>
