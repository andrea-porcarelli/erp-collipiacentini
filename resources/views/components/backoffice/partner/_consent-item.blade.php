@php
    $isLocked     = $consent && $consent->is_locked;
    $isRequired   = $consent ? (bool) $consent->is_required : false;
    $isActive     = $consent ? (bool) $consent->is_active : true;
    $expiryDays   = $consent ? (int) $consent->expiry_days : 0;
    $expiryMonths = $consent ? (int) $consent->expiry_months : 0;
    $expiryYears  = $consent ? (int) $consent->expiry_years : 0;
    $contentIt    = $consent ? ($consent->contentField('content', 'it') ?? '') : '';
    $consentId    = $consent ? (string) $consent->id : '';
    $titleLabel   = $isLocked ? 'Checkbox (obbligatorio)' : 'Checkbox';
    $disAttr      = $isLocked ? 'disabled' : '';
    $hasCustomers = $consent ? (($consent->customer_consents_count ?? null) !== null
                                  ? (int) $consent->customer_consents_count > 0
                                  : $consent->customerConsents()->exists())
                              : false;
    $canDelete    = ! $isLocked && ! $hasCustomers;
@endphp

<div class="consent-item mt-3 {{ $isActive ? '' : 'consent-disabled' }}"
     data-consent-id="{{ $consentId }}"
     data-is-locked="{{ $isLocked ? '1' : '0' }}"
     data-is-active="{{ $isActive ? '1' : '0' }}">
<x-card :title="$titleLabel" class="position-relative">
    <div class="consent-handle-wrap d-flex align-items-center gap-2 mb-3">
        <i class="fa-regular fa-grip-lines text-secondary consent-handle" style="cursor: grab" title="Trascina per ordinare"></i>
        <span class="badge bg-secondary consent-disabled-badge" style="display: {{ $isActive ? 'none' : 'inline-block' }}">Disabilitato</span>
    </div>

    <div class="mb-3">
        <div class="small text-secondary mb-1">Scadenza consenso</div>
        <div class="row g-2">
            <div class="col-12 col-sm-4">
                <div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting">
                    <label>Giorni</label>
                    <div class="text-field-container">
                        <select class="input-miticko consent-expiry-days" name="expiry_days" {{ $disAttr }}>
                            @foreach($dayOpts as $opt)
                                <option value="{{ $opt['id'] }}" @if($opt['id'] === $expiryDays) selected @endif>{{ $opt['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-4">
                <div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting">
                    <label>Mesi</label>
                    <div class="text-field-container">
                        <select class="input-miticko consent-expiry-months" name="expiry_months" {{ $disAttr }}>
                            @foreach($monthOpts as $opt)
                                <option value="{{ $opt['id'] }}" @if($opt['id'] === $expiryMonths) selected @endif>{{ $opt['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-4">
                <div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting">
                    <label>Anni</label>
                    <div class="text-field-container">
                        <select class="input-miticko consent-expiry-years" name="expiry_years" {{ $disAttr }}>
                            @foreach($yearOpts as $opt)
                                <option value="{{ $opt['id'] }}" @if($opt['id'] === $expiryYears) selected @endif>{{ $opt['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-2">
        <div class="small text-secondary mb-1">Testo</div>
        <div class="mb-2" style="max-width: 240px">
            <div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting">
                <label>Lingua (traduzioni)</label>
                <div class="text-field-container">
                    <i class="fa-regular fa-language icon"></i>
                    <select class="input-miticko consent-language">
                        @foreach($langOpts as $opt)
                            <option value="{{ $opt['id'] }}" @if($opt['id'] === 'it') selected @endif>{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting">
            <div class="text-field-container">
                <textarea class="input-miticko consent-editor"
                          rows="6"
                          data-initial="{{ $contentIt }}">{{ $contentIt }}</textarea>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3 gap-2 flex-wrap">
        <div class="consent-required-wrap d-inline-flex align-items-center gap-2 {{ $isLocked ? 'disabled' : '' }}">
            <span class="consent-check-box {{ $isRequired ? 'checked' : '' }}">
                @if($isRequired)<i class="fa-solid fa-check"></i>@endif
            </span>
            <span>Obbligatorio</span>
            <input type="hidden" class="consent-required-input" name="is_required" value="{{ $isRequired ? '1' : '0' }}">
        </div>
        <div class="d-flex align-items-center gap-3">
            @if(! $isLocked)
                <button type="button"
                        class="btn-consent-toggle bt-miticko bt-m-text-only text-secondary"
                        data-mode="medium primary"
                        title="{{ $isActive ? 'Disabilita questo consenso lato frontend' : 'Riabilita questo consenso lato frontend' }}">
                    <i class="fa-regular {{ $isActive ? 'fa-toggle-on' : 'fa-toggle-off' }} icon me-1"></i>
                    <span class="consent-toggle-label">{{ $isActive ? 'Disabilita' : 'Abilita' }}</span>
                </button>
            @endif
            @if($canDelete)
                <button type="button"
                        class="btn-consent-delete bt-miticko bt-m-text-only text-danger"
                        data-mode="medium primary">
                    <span>Elimina</span>
                    <i class="fa-regular fa-trash icon ms-1"></i>
                </button>
            @endif
        </div>
    </div>

    <div class="button-card-absolute">
        <x-button class="btn-save-card" label="Salva modifiche" leading="fa-save" status="Disabled" />
    </div>
</x-card>
</div>
