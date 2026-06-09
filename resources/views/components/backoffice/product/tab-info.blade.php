@props(['model', 'categories', 'languages', 'fieldTypes' => collect(), 'features' => collect()])

<div class="tab-pane fade show active" id="info-panel" role="tabpanel" aria-labelledby="info-tab">
    <div class="row">
        <div class="col-12">
            <x-card title="Impostazioni prodotto interne" sub_title="nome interno, codice e visibilità online" class="position-relative">
                <form id="form-info-settings">
                    <div class="row">
                        <div class="col-12 col-sm-6">
                            <x-input :model="$model" name="label" label="Nome prodotto interno" required message="Questo campo è solo per uso interno e non visibile al pubblico" icon="fa-regular fa-circle-info"/>
                        </div>
                        <div class="col-12 col-sm-6">
                            <x-input :value="$model->product_code" name="name" label="Codice prodotto" disabled required message="Il codice prodotto è assegnato automaticamente dal sistema." icon="fa-regular fa-circle-info" />
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12 col-sm-6">
                            <x-select :model="$model"  name="is_active" label="Stato prodotto" required :options="[['id' => 1, 'label' => 'Pubblicato'],['id' => 0, 'label' => 'Non Pubblicato']]" icon="fa-regular fa-circle-info" />
                        </div>
                        <div class="col-12 col-sm-6">
                            <x-input :value="$model->public_url" name="slug" label="URL" disabled required message="L'URL è assegnato dal sistema e non può essere modificato" icon="fa-regular fa-circle-info" />
                        </div>
                    </div>
                </form>
                <div class="button-card-absolute">
                    <x-button class="btn-save-card" label="Salva modifiche" leading="fa-save" status="Disabled" />
                </div>
            </x-card>
            <x-card title="Nome e descrizione del prodotto sull'E-commerce" sub_title="Homepage dell'E-commerce" class="mt-4 position-relative">
                <form id="form-info-ecommerce">
                    <div class="row">
                        <div class="col-12">
                            <x-input :value="$model->contentField('short_title')" name="short_title" maxlength="55" label="Nome breve (card prodotto)" required message="Indica l’esperienza che offri: non usare il nome della tua attività e non inserire il prezzo (es. Visita guidata completa con degustazione)" icon="fa-regular fa-circle-info"/>
                        </div>
                        <div class="col-12 mt-spacing-l">
                            <x-textarea :value="$model->contentField('short_description')" name="short_description" maxlength="110" label="Descrizione breve (card prodotto)" required message="Indica l’esperienza che offri: non usare il nome della tua attività e non inserire il prezzo (es. Visita guidata completa con degustazione)" icon="fa-regular fa-circle-info" />
                        </div>
                        <div class="col-12 mt-spacing-l">
                            <x-input :value="$model->contentField('long_title')" name="long_title" maxlength="62" label="Nome completo nella pagina prodotto dedicata" required message="Scrivi il nome dell’attività includendo: cosa faranno i clienti, il prezzo ed il nome della tua attività (es. Visita guidata completa con degustazione 15€ a Veleia Romana)" icon="fa-regular fa-circle-info"/>
                        </div>
                    </div>
                </form>
                <div class="button-card-absolute">
                    <x-button class="btn-save-card" label="Salva modifiche" leading="fa-save" status="Disabled" />
                </div>
            </x-card>
            <x-card title="Durata" class="mt-4 position-relative">
                <form id="form-info-duration">
                    <div class="row">
                        <div class="col-12 col-sm-4" style="display: flex; gap: 10px">
                            <div class="row">
                                <div class="col-xs-12 col-sm-4">
                                    <x-input :model="$model" name="duration_days" label="Giorni" type="number" required icon="fa-regular fa-circle-info"/>
                                </div>
                                <div class="col-xs-12 col-sm-4">
                                    <x-input :model="$model" name="duration_hours" label="Ore" type="number" max="23" required  icon="fa-regular fa-circle-info"/>
                                </div>
                                <div class="col-xs-12 col-sm-4">
                                    <x-input :model="$model" name="duration_minutes" label="Minuti" type="number" max="59" required icon="fa-regular fa-circle-info"/>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-8 mt-3 mt-sm-0">
                            <x-input :model="$model" name="booking_deadline_hours" type="number" label="Tempo limite prenotazione (minuti prima dell’inizio dell’orario di visita)" type="number" min="0" message="indica in minuti entro quando i clienti possono prenotare a ridosso dell'inizio del turno" icon="fa-regular fa-circle-info"/>
                        </div>
                    </div>
                </form>
                <div class="button-card-absolute">
                    <x-button class="btn-save-card" label="Salva modifiche" leading="fa-save" stat us="Disabled" />
                </div>
            </x-card>
            <x-card title="Categoria prodotto" class="mt-4 position-relative">
                <form id="form-info-categories">
                    <div class="row">
                        <div class="col-12">
                            <x-select name="category_id" label="Seleziona Categoria" message="La categoria aiuta gli utenti a filtrare per tipologia di esperienze, ad esempio 'visite' e 'degustazioni'" required :options="$categories" icon="fa-regular fa-circle-info" />
                        </div>
                    </div>
                </form>
                <div class="button-card-absolute">
                    <x-button class="btn-save-card" label="Salva modifiche" leading="fa-save" status="Disabled" />
                </div>
            </x-card>
            <x-backoffice.product.features :model="$model" :features="$features" />
            <x-card title="Impostazioni per i motori di ricerca" class="mt-4 mb-5 position-relative" sub_title="Usa descrizioni brevi, includi il nome della tua attività, il nome del prodotto ed il prezzo.">
                <form id="form-info-public">
                    <div class="row">
                        <div class="col-12 col-sm-6">

                            <div class="d-flex align-items-start gap-2 mt-4">
                                <div class="flex-grow-1">
                                    <x-input :model="$model" name="meta_keywords" label="Keywords" placeholder="es. visite guidate, degustazioni, vino" message="Parole chiave separate da virgola" icon="fa-regular fa-circle-info" />
                                </div>
                                <button type="button" data-mode="buttonSize-Medium buttonEmphasis-Medium buttonAppearance-Neutral" data-field="meta_keywords" class="bt-miticko btn-public-meta-translations mt-spacing-2xl"><i class="fa-regular fa-language icon"></i></button>
                            </div>
                            <div class="d-flex align-items-start gap-2 mt-4">
                                <div class="flex-grow-1">
                                    <x-input :model="$model" maxlength="55" name="meta_title" label="Nome prodotto pubblico (meta title)" required />
                                </div>
                                <button type="button" data-mode="buttonSize-Medium buttonEmphasis-Medium buttonAppearance-Neutral" data-field="meta_title" class="bt-miticko btn-public-meta-translations mt-spacing-2xl"><i class="fa-regular fa-language icon"></i></button>
                            </div>
                            <div class="d-flex align-items-start gap-2 mt-4">
                                <div class="flex-grow-1">
                                    <x-textarea :model="$model" maxlength="150" name="meta_description" rows="3" label="Descrizione breve (meta description)" required />
                                </div>
                                <button type="button" data-mode="buttonSize-Medium buttonEmphasis-Medium buttonAppearance-Neutral" data-field="meta_description" class="bt-miticko btn-public-meta-translations mt-spacing-3xl"><i class="fa-regular fa-language icon"></i></button>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="text-field">
                                <label>Come apparirà su Google</label>
                            </div>
                            <div class="google-preview-box mt-2">
                                <div class="google-preview-site">
                                    <img src="{{ asset('favicon.ico') }}" class="google-preview-favicon" alt="" onerror="this.style.display='none'">
                                    <span class="google-preview-sitename">{{ config('app.name') }}</span>
                                </div>
                                <div class="google-preview-url">{{ $model->public_url }}</div>
                                <div class="google-preview-title" id="preview-meta-title">{{ $model->meta_title ?? $model->label }}</div>
                                <div class="google-preview-description" id="preview-meta-description">{{ $model->meta_description }}</div>
                            </div>
                        </div>
                    </div>
                </form>
                <div class="button-card-absolute">
                    <x-button class="btn-save-card" label="Salva modifiche" leading="fa-save" status="Disabled" />
                </div>
            </x-card>
            <x-card title="Personalizza informazioni per la visita inviate via Email in formato PDF" class="mt-4 mb-5 position-relative" sub_title="Le informazioni che inserisci qui verranno visualizzate nel PDF di riepilogo per l'accesso all'esperienza che i clienti ricevono via email">
                <form id="form-info-visit">
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex align-items-start gap-2">
                                <div class="flex-grow-1">
                                    <x-textarea
                                        name="visit_info"
                                        :value="$model->contentField('visit_info') ?? ''"
                                        maxlength="600"
                                        rows="3"
                                        label="Informazioni importanti sulla visita"
                                        placeholder="Inserisci le informazioni più importanti per l'accesso all'esperienza es. &quot;presentati 15 minuti prima in biglietteria, il parcheggio si trova dietro&quot;"
                                    />
                                </div>
                                <button type="button" data-mode="buttonSize-Medium buttonEmphasis-Medium buttonAppearance-Neutral" class="bt-miticko btn-visit-info-translations mt-spacing-3xl"><i class="fa-regular fa-language icon"></i></button>
                            </div>
                        </div>
                        <div class="col-12  mt-4">
                            <x-input :model="$model" name="support_email" type="email" label="Email contatto per assistenza clienti" leading="fa-envelope" placeholder="inserisci email..." />
                        </div>
                    </div>
                </form>
                <div class="button-card-absolute">
                    <x-button class="btn-save-card" label="Salva modifiche" leading="fa-save" status="Disabled" />
                </div>
            </x-card>

            <x-card title="Link Utili" class="mt-4 mb-5 position-relative" sub_title="Inserisci solamente i link che potrebbero essere utili agli utenti o obbligatori per legge, verranno mostrati nella barra laterale (sidebar) della pagina prodotto.">
                <x-backoffice.product.links :model="$model" :languages="$languages" />
                <div class="button-card-absolute">
                    <x-button class="btn-link-add" status="Primary" label="Aggiungi link" leading="fa-plus" />
                    <x-button class="btn-save-links" label="Salva modifiche" leading="fa-save" status="Disabled" />
                </div>
            </x-card>

            <x-card title="Domande frequenti (FAQ)" class="mt-4 mb-5 position-relative" sub_title="Inserisci fino a 5 FAQ con le risposte alle domande più frequenti che solitamente ricevi per questo prodotto">
                <x-backoffice.product.faqs :model="$model" :languages="$languages" />
                <div class="button-card-absolute">
                    <x-button class="btn-link-faq" status="Primary" label="Aggiungi domanda" leading="fa-plus" />
                    <x-button class="btn-save-faq" label="Salva modifiche" leading="fa-save" status="Disabled" />
                </div>
            </x-card>

            <x-card title="Altri prodotti" class="mt-4 mb-5 position-relative" sub_title="Seleziona fino a 5 tuoi prodotti disponibili da mostrare come suggerimento">
                <div class="row">
                    <div class="col-3">
                        <x-backoffice.product.related :model="$model" />
                    </div>
                </div>
                <div class="button-card-absolute">
                    <x-button class="btn-save-related" label="Salva modifiche" leading="fa-save" status="Disabled" />
                </div>
            </x-card>

            <x-card title="Dati cliente" class="mt-4 mb-5 position-relative" sub_title="scegli quali dati chiedere al cliente per questo prodotto, verranno richiesti in fase di pagamento. <br />Di norma, meno dati si chiedono, più si riduce l’abbandono del carrello: richiedi solamente le informazioni strettamente necessarie per agevolare l’acquisto.">
                <x-backoffice.product.customer-fields :model="$model" :fieldTypes="$fieldTypes" />
                <div class="button-card-absolute">
                    <x-button class="btn-save-customer-fields" label="Salva modifiche" leading="fa-save" status="Disabled" />
                </div>
            </x-card>

            <x-card title="Elimina il prodotto" class="mt-4 mb-5 position-relative" sub_title="Se elimini il prodotto, tutti i suoi dati verranno eliminati e non saranno recuperabili in alcun modo.<br />Le vendite rimarranno comunque nello storico e potrai consultarle ugualmente.">
                <x-button status="Error" emphasis="MediumLow" label="Elimina il prodotto e tutti i dati correlati" leading="fa-trash-can" class="btn-delete-product" />
            </x-card>
        </div>
    </div>
</div>
