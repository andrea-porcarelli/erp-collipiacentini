@props(['model', 'languages'])

{{-- Selettore lingua --}}
<div class="mb-3" style="max-width: 220px">
    <div class="text-field" data-mode="medium">
        <label>Lingua (traduzioni)</label>
        <div class="text-field-container">
            <select class="input-miticko" id="faq-language-select">
                @foreach($languages as $lang)
                    <option value="{{ $lang['id'] }}" data-iso="{{ $lang['iso_code'] }}"
                        {{ $lang['iso_code'] === 'it' ? 'selected' : '' }}>
                        {{ $lang['label'] }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>

{{-- Intestazione colonne --}}
<div class="row g-2 align-items-center mb-1">
    <div class="col-12 col-sm-4 text-field">
        <label>Domanda</label>
    </div>
    <div class="col-12 col-sm-7 text-field">
        <label>Risposta</label>
    </div>
    <div class="col-12 col-sm-1"></div>
</div>

{{-- Form nuova FAQ --}}
<form id="form-faq-new">
    <div class="row g-2 align-items-start mb-2">
        <div class="col-12 col-sm-4">
            <x-input name="question" placeholder="es. Come posso prenotare?" />
        </div>
        <div class="col-12 col-sm-7">
            <x-textarea name="answer" rows="2" placeholder="Inserisci la risposta..." />
        </div>
        <div class="col-12 col-sm-1 d-flex gap-1 align-items-end justify-content-end pb-1">
            <x-button size="medium" status="disabled" emphasis="text-only" leading="fa-regular fa-trash icon" disabled="true" />
        </div>
    </div>
</form>

{{-- Lista FAQ --}}
<div id="faqs-list" class="mb-3">
    @forelse($model->faqs as $faq)
        @php
            $language = $faq->contents->first();
        @endphp
        <div class="faq-item py-1" data-id="{{ $faq->id }}">
            <div class="row g-2 align-items-start">
                <div class="col-12 col-sm-4">
                    <div class="text-field" data-mode="medium">
                        <div class="text-field-container">
                            <input class="input-miticko" name="question" value="{{ $faq->contentField('question', $language->language->iso_code) }}">
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-7">
                    <div class="text-field" data-mode="medium">
                        <div class="text-field-container">
                            <textarea class="input-miticko" name="answer" rows="2">{{ $faq->contentField('answer', $language->language->iso_code) }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-1 d-flex gap-1 align-items-end justify-content-end pb-1">
                    <x-button class="btn-faq-delete" size="medium" emphasis="text-only" leading="fa-regular fa-trash icon" />
                </div>
            </div>
            @php
                $flags = ['it'=>'🇮🇹','en'=>'🇬🇧','de'=>'🇩🇪','fr'=>'🇫🇷','es'=>'🇪🇸','pt'=>'🇵🇹','nl'=>'🇳🇱','ru'=>'🇷🇺','zh'=>'🇨🇳','ja'=>'🇯🇵','ar'=>'🇸🇦'];
            @endphp
            <div class="d-flex gap-2 flex-wrap mt-1">
                <span title="{{ $language->language->label }}" style="font-size:13px; opacity:1">
                    {{ $flags[$language->language->iso_code] ?? '🏳' }} <span class="small fw-semibold">{{ strtoupper($language->language->iso_code) }}</span>
                </span>
            </div>
        </div>
    @empty
        <p class="text-secondary small mb-0" id="faqs-empty">Nessuna FAQ aggiunta.</p>
    @endforelse
</div>
