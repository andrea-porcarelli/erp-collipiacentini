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
            @if($model->partner?->company?->has_woocommerce && $model->partner?->company?->endpoint_woocommerce)
                <div>
                    <x-button label="Sincronizza WooCommerce" status="warning" emphasis="outlined" size="small" leading="fa-rotate" class="btn-sync-woocommerce" />
                </div>
            @endif
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
                    id="closed-period-tab"
                    role="tab"
                    label="Periodi di chiusura"
                    :dataset="['bs-target' => '#closed-period-panel', 'bs-toggle' => 'tab']"
                    :ariaset="['controls' => 'closed-period-panel', 'selected' => 'false']"
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
            <li class="nav-item" role="presentation">
                <x-button
                    class=""
                    status="secondary"
                    emphasis="outlined"
                    id="links-tab"
                    role="tab"
                    label="Link Utili"
                    :dataset="['bs-target' => '#links-panel', 'bs-toggle' => 'tab']"
                    :ariaset="['controls' => 'links-panel', 'selected' => 'false']"
                />
            </li>
            <li class="nav-item" role="presentation">
                <x-button
                    class=""
                    status="secondary"
                    emphasis="outlined"
                    id="faqs-tab"
                    role="tab"
                    label="FAQ"
                    :dataset="['bs-target' => '#faqs-panel', 'bs-toggle' => 'tab']"
                    :ariaset="['controls' => 'faqs-panel', 'selected' => 'false']"
                />
            </li>
            <li class="nav-item" role="presentation">
                <x-button
                    class=""
                    status="secondary"
                    emphasis="outlined"
                    id="related-tab"
                    role="tab"
                    label="Prodotti correlati"
                    :dataset="['bs-target' => '#related-panel', 'bs-toggle' => 'tab']"
                    :ariaset="['controls' => 'related-panel', 'selected' => 'false']"
                />
            </li>
        </ul>
        {{-- Tabs Content --}}
        <div class="tab-content" id="productTabsContent">
            <x-backoffice.product.tab-info :model="$model" :categories="$categories" :languages="$languages" :fieldTypes="$fieldTypes" />
            <x-backoffice.product.tab-variants :model="$model" />
            <x-backoffice.product.tab-schedule :model="$model" />
            <x-backoffice.product.tab-special-schedule :model="$model" />
            <x-backoffice.product.tab-closed-period :model="$model" />
            <x-backoffice.product.tab-media :model="$model" />
            <div class="tab-pane fade" id="links-panel" role="tabpanel">
                <x-card title="Link Utili" :saveable="false">
                    <x-backoffice.product.links :model="$model" :languages="$languages" />
                </x-card>
            </div>
            <div class="tab-pane fade" id="faqs-panel" role="tabpanel">
                <x-card title="FAQ" :saveable="false">
                    <x-backoffice.product.faqs :model="$model" :languages="$languages" />
                </x-card>
            </div>
            <div class="tab-pane fade" id="related-panel" role="tabpanel">
                <x-card title="Prodotti correlati" :saveable="false">
                    <x-backoffice.product.related :model="$model" />
                </x-card>
            </div>
        </div>
    </div>
@endsection

@section('custom-css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/switchery/0.8.2/switchery.min.css" integrity="sha512-uyGg6dZr3cE1PxtKOCGqKGTiZybe5iSq3LsqOolABqAWlIRLo/HKyrMMD8drX+gls3twJdpYX0gDKEdtf2dpmw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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

    .google-preview-box {
        border-radius: 8px;
        padding: 16px;
        background: #fff;
        font-family: arial, sans-serif;
        max-width: 600px;
    }

    .google-preview-site {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 6px;
    }

    .google-preview-favicon {
        width: 16px;
        height: 16px;
        border-radius: 50%;
    }

    .google-preview-sitename {
        font-size: 13px;
        color: #4d5156;
    }

    .google-preview-url {
        font-size: 12px;
        color: #4d5156;
        margin-bottom: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .google-preview-title {
        font-size: 18px;
        color: #1a0dab;
        margin-bottom: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        cursor: pointer;
    }

    .google-preview-title:hover {
        text-decoration: underline;
    }

    .google-preview-description {
        font-size: 13px;
        color: #4d5156;
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
@endsection

@section('custom-script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/switchery/0.8.2/switchery.min.js" integrity="sha512-lC8vSUSlXWqh7A/F+EUS3l77bdlj+rGMN4NB5XFAHnTR3jQtg4ibZccWpuSSIdPoPUlUxtnGktLyrWcDhG8RvA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        window.PRODUCT_ID = {{ $model->id }};
        window.PRODUCT_CATEGORY_ID = {{ $model->category_id ?? 'null' }};
        window.PRODUCT_SUB_CATEGORY_ID = {{ $model->sub_category_id ?? 'null' }};
    </script>
    <script src="{{ asset('backoffice/js/products.js') }}?v=1.0" type="module"></script>
    <script>
        document.addEventListener('variants-notify', (e) => {
            toastr[e.detail.type]?.(e.detail.message);
        });
    </script>
    <script>
        $(document).on('input', '#form-info-public [name="meta_title"]', function () {
            const val = $(this).val().trim();
            $('#preview-meta-title').text(val || '{{ $model->label }}');
        });

        $(document).on('input', '#form-info-public [name="meta_description"]', function () {
            $('#preview-meta-description').text($(this).val());
        });

        $(document).ready(function(){
            setTimeout(() => {
                $(document).trigger('loadSwitchTrigger', [{ container: '.switch-container', options: {secondaryColor: '#cccccc', color: '#E87722'}}])
            }, 250)
            $(document).on('click', '.btn-sync-woocommerce', function () {
                $(document).trigger('sweetConfirmTrigger', [{
                    text: 'Vuoi avviare la sincronizzazione del prodotto con WooCommerce?',
                    title: 'Sincronizzazione WooCommerce',
                    callback: () => {
                        $(document).trigger('fetch', [{
                            path: `/backoffice/products/{{ $model->id }}/sync-woocommerce`,
                            method: "post",
                            then: (response) => {
                                toastr.success('Sincronizzazione avviata con successo');
                            },
                            catch: (error) => {
                                toastr.error(error?.message || 'Errore durante la sincronizzazione');
                            },
                        }]);
                    }
                }]);
            });
        });
    </script>
@endsection
