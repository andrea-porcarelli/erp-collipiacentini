@props(['model', 'categories', 'languages', 'fieldTypes' => collect()])

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
                            <x-select name="is_active" label="Stato prodotto" placeholder="Seleziona l'azienda" required :options="[['id' => 1, 'label' => 'Pubblicato'],['id' => 0, 'label' => 'Non Pubblicato']]" icon="fa-regular fa-circle-info" message="Questo campo è solo per uso interno e non visibile al pubblico" />
                        </div>
                        <div class="col-12 col-sm-6">
                            <x-input :value="$model->route" name="slug" label="URL" disabled required message="Non è possibile modificare l'URL" icon="fa-regular fa-circle-info" />
                        </div>
                    </div>
                </form>
                <div class="button-card-absolute">
                    <x-button class="btn-success btn-save-card" emphasis="primary" label="Salva modifiche" leading="fa-save" />
                </div>
            </x-card>
            <x-card title="Durata" class="mt-4 position-relative">
                <form id="form-info-duration">
                    <div class="row">
                        <div class="col-12 col-sm-4" style="display: flex; gap: 10px">
                            <x-input :model="$model" name="duration_days" label="Giorni" type="number" required message="inserisci il valore in giorni" icon="fa-regular fa-circle-info"/>
                            <x-input :model="$model" name="duration_hours" label="Ore" type="number" max="23" required message="inserisci il valore in ore" icon="fa-regular fa-circle-info"/>
                            <x-input :model="$model" name="duration_minutes" label="Minuti" type="number" max="59" required message="inserisci il valore in minuti" icon="fa-regular fa-circle-info"/>
                        </div>
                    </div>
                </form>
                <div class="button-card-absolute">
                    <x-button class="btn-success btn-save-card" emphasis="primary" label="Salva modifiche" leading="fa-save" />
                </div>
            </x-card>
            <x-card title="Categoria e Sottocategoria" class="mt-4 position-relative">
                <form id="form-info-categories">
                    <div class="row">
                        <div class="col-12 col-sm-6">
                            <x-select name="category_id" label="Categoria" message="La categoria aiuta gli utenti a filtrare per tipologia di esperienze, ad esempio &quot;visite&quot; e &quot;degustazioni&quot;" required :options="$categories" icon="fa-regular fa-circle-info" />
                        </div>
                        <div class="col-12 col-sm-6">
                            <x-select name="sub_category_id" label="Sottocategoria" message="La sottocategoria indica le diverse tipologie disponibili sotto alla categoria principale, ad esempio sotto &quot;visite&quot; si ha &quot;visita libera&quot; e &quot;visita guidata&quot;" required :options="$categories" icon="fa-regular fa-circle-info" />
                        </div>
                    </div>
                </form>
                <div class="button-card-absolute">
                    <x-button class="btn-success btn-save-card" emphasis="primary" label="Salva modifiche" leading="fa-save" />
                </div>
            </x-card>
            <x-card title="Impostazioni prodotto pubbliche" class="mt-4 mb-5 position-relative" sub_title="titolo e descrizione che vedranno gli utenti su Google e sul sito">
                <form id="form-info-public">
                    <div class="row">
                        <div class="col-12 col-sm-6">
                            <x-input :model="$model" maxlength="55" name="meta_title" label="Nome prodotto pubblico" required />
                            <x-textarea :model="$model" maxlength="150"  name="meta_description" label="Descrizione breve" required class_container="mt-4" />
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
                                <div class="google-preview-url">{{ $model->route }}</div>
                                <div class="google-preview-title" id="preview-meta-title">{{ $model->meta_title ?? $model->label }}</div>
                                <div class="google-preview-description" id="preview-meta-description">{{ $model->meta_description }}</div>
                            </div>
                        </div>
                    </div>
                </form>
                <div class="button-card-absolute">
                    <x-button class="btn-success btn-save-card" emphasis="primary" label="Salva modifiche" leading="fa-save" />
                </div>
            </x-card>

            <x-card title="Breve descrizione del prodotto" class="mt-4 mb-5 position-relative" sub_title="Inserisci una breve descrizione che verrà mostrata nella barra laterale (sidebar) della pagina prodotto. Serve sia per gli utenti che per i motori di ricerca (SEO).">
                <form id="form-info-public">
                    <div class="row">
                        <div class="col-12">
                            <x-textarea :model="$model" maxlength="300" name="description" rows="5" label="Breve descrizione del prodotto" required class_container="mt-4" />
                        </div>
                    </div>
                </form>
                <div class="button-card-absolute">
                    <x-button class="btn-success btn-save-card" emphasis="primary" label="Salva modifiche" leading="fa-save" />
                </div>
            </x-card>

            <x-card title="Link Utili" class="mt-4 mb-5 position-relative" sub_title="Inserisci solamente i link che potrebbero essere utili agli utenti o obbligatori per legge, verranno mostrati nella barra laterale (sidebar) della pagina prodotto.">
                <x-backoffice.product.links :model="$model" :languages="$languages" />
            </x-card>

            <x-card title="Domande frequenti (FAQ)" class="mt-4 mb-5 position-relative" sub_title="Inserisci fino a 5 FAQ con le risposte alle domande più frequenti che solitamente ricevi per questo prodotto">
                <x-backoffice.product.faqs :model="$model" :languages="$languages" />
            </x-card>

            <x-card title="Altri prodotti" class="mt-4 mb-5 position-relative" sub_title="Seleziona fino a 5 tuoi prodotti disponibili da mostrare come suggerimento">
                <x-backoffice.product.related :model="$model" :languages="$languages" />
            </x-card>

            <x-card title="Dati cliente" class="mt-4 mb-5 position-relative" sub_title="scegli quali dati chiedere al cliente per questo prodotto, verranno richiesti in fase di pagamento. <br />Di norma, meno dati si chiedono, più si riduce l’abbandono del carrello: richiedi solamente le informazioni strettamente necessarie per agevolare l’acquisto.">
                <x-backoffice.product.customer-fields :model="$model" :fieldTypes="$fieldTypes" />
                <div class="button-card-absolute">
                    <x-button class="btn-success btn-save-customer-fields" emphasis="primary" label="Salva modifiche" leading="fa-save" />
                </div>
            </x-card>

            <x-card title="Elimina il prodotto" class="mt-4 mb-5 position-relative" sub_title="Se elimini il prodotto, tutti i suoi dati verranno eliminati e non saranno recuperabili in alcun modo.<br />Le vendite rimarranno comunque nello storico e potrai consultarle ugualmente.">
                <x-button emphasis="outlined" status="danger" label="Elimina il prodotto e tutti i dati correlati" leading="fa-trash" class="btn-delete-product" />
            </x-card>
        </div>
    </div>
</div>
