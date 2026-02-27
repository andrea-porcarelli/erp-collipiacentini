@props(['model', 'languages'])

{{-- Lista FAQ --}}
<div id="faqs-list" class="mb-4">
    @forelse($model->faqs()->with('language')->get() as $faq)
        <div class="faq-item" data-id="{{ $faq->id }}">
            {{-- Riga display --}}
            <div class="faq-display py-3 border-bottom">
                <div class="d-flex align-items-start gap-3">
                    <span class="badge bg-secondary mt-1" style="font-size:11px;white-space:nowrap">{{ $faq->language?->label ?? '—' }}</span>
                    <div class="flex-grow-1">
                        <p class="fw-semibold mb-1">{{ $faq->question }}</p>
                        <p class="text-secondary small mb-0">{{ $faq->answer }}</p>
                    </div>
                    <div class="d-flex gap-2 flex-shrink-0">
                        <x-button emphasis="outlined" status="secondary" size="small" leading="fa-pen" class="btn-faq-edit" />
                        <x-button emphasis="outlined" status="danger" size="small" leading="fa-trash" class="btn-faq-delete" />
                    </div>
                </div>
            </div>
            {{-- Riga edit (nascosta) --}}
            <div class="faq-edit d-none py-3 border-bottom">
                <div class="row g-2">
                    <div class="col-12 col-sm-1">
                        <x-select name="language_id" label="Lingua" :options="$languages" :value="$faq->language_id" />
                    </div>
                    <div class="col-12">
                        <x-input name="question" label="Domanda" :value="$faq->question" />
                    </div>
                    <div class="col-12">
                        <x-textarea name="answer" label="Risposta" :value="$faq->answer" rows="3" />
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <x-button emphasis="primary" status="success" size="small" leading="fa-check" label="Salva" class="btn-faq-save" />
                        <x-button emphasis="outlined" status="secondary" size="small" leading="fa-xmark" label="Annulla" class="btn-faq-cancel" />
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
            <div class="col-12 col-sm-2">
                <x-select name="language_id" label="Lingua" :options="$languages" />
            </div>
            <div class="col-12 col-sm-10">
                <x-input name="question" label="Domanda" placeholder="es. Come posso prenotare?" />
            </div>
            <div class="col-12">
                <x-textarea name="answer" label="Risposta" rows="3" placeholder="Inserisci la risposta..." />
            </div>
            <div class="col-12">
                <x-button id="btn-faq-add" emphasis="primary" status="success" size="small" leading="fa-plus" label="Aggiungi FAQ" />
            </div>
        </div>
    </form>
</div>
