@props(['model'])

<div class="tab-pane fade" id="variants-panel" role="tabpanel" aria-labelledby="variants-tab">
    <div class="row">
        <div class="col-12">
            <x-card title="Capienza" sub_title="" class="position-relative">
                <form id="form-info-settings">
                    <div class="row">
                        <div class="col-12">
                            <x-input :model="$model" name="occupancy" label="Capienza massima per fascia oraria" required message="Ogni biglietto venduto influirà sulla capienza indicata, indipendentemente dalla variante" trailing="fa-users"/>
                        </div>
                    </div>
                    <div class="row mt-3 switch-container">
                        <div class="col-12">
                            <x-switch name=" " class="occupancy_for_price-switch" label="Gestisci capienza per variante" message="Assegna una capienza specifica a ogni variante. Il sistema aggiorna i posti disponibili in base alle vendite." />
                        </div>
                        <div class="col-12 mt-4">
                            <x-switch name="free_occupancy_rule" label="Gratuiti con capienza illimitata" message="Le varianti con prezzo 0 € restano disponibili senza limiti e non scalano i posti della fascia oraria." />
                        </div>
                    </div>
                </form>
                <div class="button-card-absolute">
                    <x-button class="btn-success btn-save-card" emphasis="primary" label="Salva modifiche" leading="fa-save" />
                </div>
            </x-card>
            <x-card title="Varianti prodotto" sub_title="Crea le tipologie di biglietto (es. intero, ridotto) e definisci prezzo e capienza. Puoi riordinare la varianti, l’ordine che imposti qui sarà lo stesso che vedranno gli utenti online in fase di prenotazione." class="mt-4 position-relative">
                @livewire('product-variants', ['product' => $model], key('product-variants-' . $model->id))
            </x-card>
        </div>
    </div>
</div>
