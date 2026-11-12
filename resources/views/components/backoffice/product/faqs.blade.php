@props(['model', 'languages'])

{{-- Selettore lingua --}}
<div class="mb-3" style="max-width: 220px">
    <div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting">
        <label>Lingua (traduzioni)</label>
        <div class="text-field-container">
            <i class="fa-regular fa-language icon"></i>
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
            <x-input name="question" placeholder="es. Come posso prenotare?" maxlength="50" />
        </div>
        <div class="col-12 col-sm-7">
            <div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting">
                <div class="text-field-container">
                    <textarea class="input-miticko" name="answer" rows="2" placeholder="Inserisci la risposta..." data-maxlength="300"></textarea>
                </div>
                <x-supporting-text extra_class="char-count-answer" message="300 / 300 caratteri rimanenti"/>
            </div>
        </div>
        <div class="col-12 col-sm-1 d-flex gap-1 align-items-center justify-content-end align-self-stretch">
            <x-icon-button icon="fa-trash" appearance="Disabled" emphasis="Medium" />
        </div>
    </div>
</form>

{{-- Lista FAQ --}}
<div id="faqs-list" class="mb-3">
    @forelse($model->faqs as $faq)
        @php
            $language = $faq->contents->first();
            $questionValue = $faq->contentField('question', $language->language->iso_code) ?? '';
            $answerValue   = $faq->contentField('answer', $language->language->iso_code) ?? '';
            $answerPlainLen = mb_strlen(trim(strip_tags($answerValue)));
        @endphp
        <div class="faq-item py-1" data-id="{{ $faq->id }}">
            <div class="row g-2 align-items-start">
                <div class="col-12 col-sm-4">
                    <div class="text-field" data-mode="textfieldSize-Medium">
                        <div class="text-field-container">
                            <input class="input-miticko" name="question" value="{{ $questionValue }}"
                                   maxlength="50"
                                   oninput="this.closest('.text-field').querySelector('.char-count').textContent = (50 - [...this.value].length) + ' / 50 caratteri rimanenti'">
                        </div>
                        <x-supporting-text extra_class="char-count" :message="(50 - mb_strlen($questionValue)) . ' / 50 caratteri rimanenti'"/>
                    </div>
                    <div class="mt-2">
                        <div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting">
                            <div class="text-field-container">
                                <i class="fa-regular fa-language icon"></i>
                                <select class="input-miticko" disabled>
                                    <option selected>{{ $language->language->label }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-7">
                    <div class="text-field" data-mode="textfieldSize-Medium">
                        <div class="text-field-container">
                            <textarea class="input-miticko" name="answer" rows="2" data-maxlength="300">{{ $answerValue }}</textarea>
                        </div>
                        <x-supporting-text extra_class="char-count-answer" :message="(300 - $answerPlainLen) . ' / 300 caratteri rimanenti'"/>
                    </div>
                </div>
                <div class="col-12 col-sm-1 d-flex gap-1 align-items-center justify-content-end align-self-stretch">
                    <x-icon-button class="btn-faq-delete" icon="fa-trash" appearance="Neutral" emphasis="Medium" />
                </div>
            </div>
        </div>
    @empty
        <p class="text-secondary small mb-0" id="faqs-empty">Nessuna FAQ aggiunta.</p>
    @endforelse
</div>
