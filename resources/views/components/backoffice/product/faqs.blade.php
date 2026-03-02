@props(['model', 'languages'])

{{-- Lista FAQ --}}
<div id="faqs-list" class="mb-3">
    @forelse($model->faqs as $faq)
        <div class="faq-item py-3 border-bottom" data-id="{{ $faq->id }}">
            {{-- View mode --}}
            <div class="faq-view">
                <div class="row g-2 align-items-start">
                    <div class="col-12 col-sm-10">
                        <div class="text-field" data-mode="medium">
                            <label>Domanda</label>
                            <div class="text-field-container">
                                <input class="input-miticko" name="question" value="{{ $faq->question }}" disabled>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-2 d-flex gap-1 align-items-start justify-content-end pt-4">
                        <button type="button" data-mode="small secondary" class="bt-miticko btn-faq-edit bt-m-outlined"><i class="fa-regular fa-pen icon"></i></button>
                        <button type="button" data-mode="small danger" class="bt-miticko btn-faq-delete bt-m-outlined"><i class="fa-regular fa-trash icon"></i></button>
                        <button type="button" data-mode="small secondary" class="bt-miticko btn-faq-translations bt-m-outlined"><i class="fa-regular fa-globe icon"></i></button>
                    </div>
                    <div class="col-12">
                        <div class="text-field" data-mode="medium">
                            <label>Risposta</label>
                            <div class="text-field-container">
                                <textarea class="input-miticko" name="answer" rows="3" disabled>{{ $faq->answer }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Edit mode --}}
            <div class="faq-edit d-none">
                <div class="row g-2">
                    <div class="col-12">
                        <div class="text-field" data-mode="medium">
                            <label>Domanda</label>
                            <div class="text-field-container">
                                <input class="input-miticko" name="question" value="{{ $faq->question }}" placeholder="es. Come posso prenotare?">
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="text-field" data-mode="medium">
                            <label>Risposta</label>
                            <div class="text-field-container">
                                <textarea class="input-miticko" name="answer" rows="3" placeholder="Inserisci la risposta...">{{ $faq->answer }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 d-flex gap-1 justify-content-end">
                        <button type="button" data-mode="small success" class="bt-miticko btn-faq-save bt-m-primary"><i class="fa-regular fa-check icon"></i> Salva</button>
                        <button type="button" data-mode="small secondary" class="bt-miticko btn-faq-cancel bt-m-outlined"><i class="fa-regular fa-xmark icon"></i> Annulla</button>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <p class="text-secondary small mb-0" id="faqs-empty">Nessuna FAQ aggiunta.</p>
    @endforelse
</div>

{{-- Form nuova FAQ --}}
<div class="border-top pt-3">
    <p class="fw-semibold small mb-3">Aggiungi FAQ</p>
    <form id="form-faq-new">
        <div class="row g-2">
            <div class="col-12">
                <x-input name="question" label="Domanda" placeholder="es. Come posso prenotare?" />
            </div>
            <div class="col-12">
                <x-textarea name="answer" label="Risposta" rows="3" placeholder="Inserisci la risposta..." />
            </div>
            <div class="col-12 d-flex justify-content-end">
                <x-button id="btn-faq-add" emphasis="primary" status="success" size="small" leading="fa-plus" label="Aggiungi FAQ" />
            </div>
        </div>
    </form>
</div>
