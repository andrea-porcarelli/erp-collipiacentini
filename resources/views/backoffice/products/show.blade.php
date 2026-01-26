@extends('backoffice.layout', ['title' => 'Modifica prodotto', 'active' => $path])

@section('main-content')
    <div class="d-flex justify-content-between">
        <div class="d-flex gap-3 align-items-center">
            <div>
                <x-button  class="btn-success" emphasis="outlined"  leading="fa-arrow-left" />
            </div>
            <div>
                <x-breadcrumb :first="['Prodotti', 'products.index']" :second="[$model->label]" />
                <x-header-page :title="$model->label" />
            </div>
        </div>
        <div class="d-flex gap-3 align-items-center">
            <div class="d-flex gap-1 product-status align-items-center">
                <span class="small-icon {{ $model->is_active->class() }}"></span>
                {{ $model->is_active->label() }}
            </div>
            <div>
                <x-button  class="btn-success" emphasis="primary" label="Salva modifiche" leading="fa-save" />
            </div>
        </div>
    </div>
    <div class="w-100">
        {{-- Tabs Navigation --}}
        <ul class="nav nav-tabs product-tabs" id="productTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <x-button
                    class="active"
                    status="secondary"
                    id="info-tab"
                    role="tab"
                    label="Informazioni"
                    :dataset="['bs-target' => '#info-panel', 'bs-toggle' => 'tab']"
                    :ariaset="['controls' => 'info-panel', 'selected' => 'true']"
                />
            </li>
            <li class="nav-item" role="presentation">
                <x-button
                    class=""
                    status="secondary"
                    emphasis="outlined"
                    id="variants-tab"
                    role="tab"
                    label="Varianti e prezzi"
                    :dataset="['bs-target' => '#variants-panel', 'bs-toggle' => 'tab']"
                    :ariaset="['controls' => 'variants-panel', 'selected' => 'false']"
                />
            </li>
            <li class="nav-item" role="presentation">
                <x-button
                    class=""
                    status="secondary"
                    emphasis="outlined"
                    id="schedule-tab"
                    role="tab"
                    label="Date e orari"
                    :dataset="['bs-target' => '#schedule-panel', 'bs-toggle' => 'tab']"
                    :ariaset="['controls' => 'schedule-panel', 'selected' => 'false']"
                />
            </li>
            <li class="nav-item" role="presentation">
                <x-button
                    class=""
                    status="secondary"
                    emphasis="outlined"
                    id="special-schedule-tab"
                    role="tab"
                    label="Date e orari speciali"
                    :dataset="['bs-target' => '#special-schedule-panel', 'bs-toggle' => 'tab']"
                    :ariaset="['controls' => 'special-schedule-panel', 'selected' => 'false']"
                />
            </li>
            <li class="nav-item" role="presentation">
                <x-button
                    class=""
                    status="secondary"
                    emphasis="outlined"
                    id="media-tab"
                    role="tab"
                    label="Foto e descrizione"
                    :dataset="['bs-target' => '#media-panel', 'bs-toggle' => 'tab']"
                    :ariaset="['controls' => 'media-panel', 'selected' => 'false']"
                />
            </li>
        </ul>

        <form id="update-product-form">
            {{-- Tabs Content --}}
            <div class="tab-content" id="productTabsContent">
                {{-- Tab 1: Informazioni --}}
                <div class="tab-pane fade show active" id="info-panel" role="tabpanel" aria-labelledby="info-tab">
                    <div class="row">
                        <div class="col-12">
                            <x-card title="Impostazioni prodotto interne" sub_title="nome interno, codice e visibilità online">
                                <div class="row">
                                    <div class="col-12 col-sm-6">
                                        <x-input :model="$model" name="label" label="Nome prodotto interno" required message="Questo campo è solo per uso interno e non visibile al pubblico" icon="fa-regular fa-circle-info"/>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <x-input name="name" label="Codice prodotto" disabled required message="Il codice prodotto è assegnato automaticamente dal sistema." icon="fa-regular fa-circle-info" />
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-12 col-sm-6">
                                        <x-input name="name" label="Stato prodotto" required message="Questo campo è solo per uso interno e non visibile al pubblico" icon="fa-regular fa-circle-info"/>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <x-input name="name" label="URL" disabled required message="Non è possibile modificare l’URL" icon="fa-regular fa-circle-info" />
                                    </div>
                                </div>
                            </x-card>
                            <x-card title="Durata e tipologia" class="mt-4">
                                <div class="row">
                                    <div class="col-12 col-sm-6">
                                        <x-input :model="$model" name="duration" label="Durata (minuti)" required message="inserisci il valore in minuti" icon="fa-regular fa-circle-info"/>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <x-input name="name" label="Tipologia" required  />
                                    </div>
                                </div>
                            </x-card>
                            <x-card title="Impostazioni prodotto pubbliche" class="mt-4 mb-5" sub_title="titolo e descrizione che vedranno gli utenti su Google e sul sito">
                                <div class="row">
                                    <div class="col-12 col-sm-6">
                                        <x-input :model="$model" name="meta_title" label="Nome prodotto pubblico" required />
                                        <x-textarea :model="$model" name="meta_title" label="Descrizione breve" required class_container="mt-4" />
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <div class="text-field">
                                            <label>Come apparirà su Google</label>
                                        </div>
                                    </div>
                                </div>
                            </x-card>
                        </div>
                    </div>
                </div>

                {{-- Tab 2: Varianti e prezzi --}}
                <div class="tab-pane fade" id="variants-panel" role="tabpanel" aria-labelledby="variants-tab">
                    <div class="row">
                        <div class="col-12">
                            <x-card title="Varianti e prezzi">
                                {{-- Contenuto tab varianti --}}
                            </x-card>
                        </div>
                    </div>
                </div>

                {{-- Tab 3: Date e orari --}}
                <div class="tab-pane fade" id="schedule-panel" role="tabpanel" aria-labelledby="schedule-tab">
                    <div class="row">
                        <div class="col-12">
                            <x-card title="Date e orari">
                                {{-- Contenuto tab date e orari --}}
                            </x-card>
                        </div>
                    </div>
                </div>

                {{-- Tab 4: Date e orari speciali --}}
                <div class="tab-pane fade" id="special-schedule-panel" role="tabpanel" aria-labelledby="special-schedule-tab">
                    <div class="row">
                        <div class="col-12">
                            <x-card title="Date e orari speciali">
                                {{-- Contenuto tab date e orari speciali --}}
                            </x-card>
                        </div>
                    </div>
                </div>

                {{-- Tab 5: Foto e descrizione --}}
                <div class="tab-pane fade" id="media-panel" role="tabpanel" aria-labelledby="media-tab">
                    <div class="row">
                        <div class="col-12">
                            <x-card title="Foto e descrizione">
                                {{-- Contenuto tab foto e descrizione --}}
                            </x-card>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('custom-css')
<style>
    .product-tabs {
        border-bottom: 2px solid #e9ecef;
        margin-bottom: 24px;
        gap: 8px;
    }

    .product-tabs .nav-link {
        border: none;
        border-bottom: 3px solid transparent;
        border-radius: 0;
        color: #6c757d;
        font-weight: 500;
        padding: 12px 20px;
        transition: all 0.2s ease;
    }

    .product-tabs .nav-link:hover {
        border-bottom-color: #dee2e6;
        color: #495057;
    }

    .product-tabs .nav-link.active {
        border-bottom-color: var(--bs-primary, #0d6efd);
        color: var(--bs-primary, #0d6efd);
        background-color: transparent;
    }

    .product-tabs .nav-link i {
        font-size: 14px;
    }

    .tab-content {
        padding-top: 8px;
    }
</style>
@endsection
