@extends('whitelabel.layout', compact('company'))

@section('content')
    <div class="container mt-5" style="min-height: 600px">
        <div class="row w-100">
            <div class="col-12 col-sm-6 offset-sm-3">
                <div class="row w-100">
                    <div class="col-12 text-center hero ">
                        <h1>Il tuo carrello</h1>
                    </div>
                </div>
                @if($cart)
                    <div class="button-progress">
                        <button id="btn-step-riepilogo" data-mode="small secondary" type="button" class="bt-miticko bt-m-default">
                            Riepilogo
                        </button>
                        <div class="progress-connector" id="connector-dati" ></div>
                        <button id="btn-step-dati" data-mode="small disabled" type="button" class="bt-miticko bt-m-default" disabled>
                            Dati
                        </button>
                        <div class="progress-connector" id="connector-pagamento" ></div>
                        <button id="btn-step-pagamento" data-mode="small disabled" type="button" class="bt-miticko bt-m-default" disabled>
                            Pagamento
                        </button>
                    </div>
                    <div id="step1-card">
                    <x-card class="cart-card" h1="true" leading="fa-shopping-cart">
                        <div class="cart-item">
                            <div class="cart-item-header">
                                <h3 class="product-name">Riepilogo prodotti</h3>
                                <p class="product-partner">verifica i prodotti e procedi</p>
                            </div>

                            <div class="cart-item-details">
                                <div class="cart-product-image">
                                    @if($cart->product->cover->first())
                                        <img src="{{ asset('storage/' . $cart->product->cover->first()->file_path) }}" alt="{{ $cart->product->meta_title }}">
                                    @else
                                        <div class="no-image">
                                            <i class="fa-regular fa-image"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="cart-product-info">
                                    <h4 class="product-title">{{ $cart->product->meta_title }}</h4>
                                    <div class="product-meta">
                                        <div class="meta-item">
                                            <i class="fa-regular fa-calendar"></i>
                                            <span>{{ $cart->date->locale('it')->isoFormat('ddd D MMMM YYYY') }}</span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fa-regular fa-clock"></i>
                                            <span>{{ \Carbon\Carbon::parse($cart->time)->format('H:i') }}</span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fa-regular fa-flag-swallowtail"></i>
                                            <span>{{ $cart->product->type }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="cart-tickets">
                                @if($cart->quantity_full > 0)
                                    <div class="ticket-row">
                                        <span class="ticket-type">{{ $cart->quantity_full }} Intero x {{ Utils::price($cart->price_full) }}</span>
                                        <span class="ticket-price">{{ Utils::price($cart->price_full * $cart->quantity_full) }}</span>
                                    </div>
                                @endif
                                @if($cart->quantity_reduced > 0)
                                    <div class="ticket-row">
                                        <span class="ticket-type">{{ $cart->quantity_reduced }} Ridotto x {{ Utils::price($cart->price_reduced) }}</span>
                                        <span class="ticket-price"> {{ Utils::price($cart->price_reduced * $cart->quantity_reduced) }}</span>
                                    </div>
                                @endif
                                @if($cart->quantity_free > 0)
                                    <div class="ticket-row">
                                        <span class="ticket-type">{{ $cart->quantity_free }} Gratuito x {{ Utils::price($cart->price_free) }}</span>
                                        <span class="ticket-price">{{ Utils::price($cart->price_free * $cart->quantity_free) }}</span>
                                    </div>
                                @endif
                                <div class="ticket-row ticket-subtotal">
                                    <span class="ticket-type">Subtotale</span>
                                    <span class="ticket-quantity"></span>
                                    <span class="ticket-price">{{ Utils::price($cart->total) }}</span>
                                </div>
                            </div>

                            <div class="cart-item-actions">
                                <a href="{{ $cart->product->route }}" class="bt-miticko bt-m-light text-center" data-mode="small primary">
                                    <i class="fa-regular fa-pen icon"></i> Modifica
                                </a>
                                <button type="button" class="bt-miticko bt-m-light btn-remove-cart" data-mode="small primary" id="btn-remove-cart">
                                    <i class="fa-regular fa-trash-can icon"></i> Rimuovi
                                </button>
                            </div>

                            <div class="cart-total">
                                <span class="total-label">Totale</span>
                                <span class="total-amount">{{ Utils::price($cart->total) }}</span>
                            </div>
                        </div>
                    </x-card>
                    </div>

                    <div class="cart-actions" id="step1-actions">
                        <a href="{{ $cart->product->route }}" class="bt-miticko bt-m-text-only " data-mode="small secondary">
                            Torna al sito
                        </a>
                        <button id="btn-checkout" type="button" class="bt-miticko bt-m-default btn-confirm" data-mode="small">
                            Conferma biglietti
                        </button>
                    </div>

                    {{-- Step 2: Dati anagrafici --}}
                    <div id="step2-card" style="display: none;">
                    <x-card class="cart-card step-card" title="Inserisci i tuoi dati" sub_title="per alcuni prodotti serve emettere la fattura, assicurati che i dati siano corretti">
                        <div class="cart-item">
                            <form id="customer-form" class="customer-form">
                                <div class="form-row">
                                    <div class="form-group">
                                        <x-input name="name" label="Nome" required />
                                    </div>
                                    <div class="form-group">
                                        <x-input name="surname" label="Cognome" required />
                                    </div>
                                </div>

                                <div class="form-group">
                                    <x-input name="email" label="Email" required />
                                </div>

                                <div class="form-group">
                                    <x-input name="address" label="Indirizzo" required />
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <x-input name="zip_code" label="CAP" required />
                                    </div>
                                    <div class="form-group">
                                        <x-input name="city" label="Città" required />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="country">Paese *</label>
                                    <div class="country-select-wrapper">
                                        <span class="country-flag" id="selected-flag">🇮🇹</span>
                                        <select id="country" name="country" required>
                                            <option value="IT" data-flag="🇮🇹" selected>Italia</option>
                                            <option value="DE" data-flag="🇩🇪">Germania</option>
                                            <option value="FR" data-flag="🇫🇷">Francia</option>
                                            <option value="ES" data-flag="🇪🇸">Spagna</option>
                                            <option value="GB" data-flag="🇬🇧">Regno Unito</option>
                                            <option value="AT" data-flag="🇦🇹">Austria</option>
                                            <option value="CH" data-flag="🇨🇭">Svizzera</option>
                                            <option value="BE" data-flag="🇧🇪">Belgio</option>
                                            <option value="NL" data-flag="🇳🇱">Paesi Bassi</option>
                                            <option value="PT" data-flag="🇵🇹">Portogallo</option>
                                            <option value="PL" data-flag="🇵🇱">Polonia</option>
                                            <option value="SE" data-flag="🇸🇪">Svezia</option>
                                            <option value="NO" data-flag="🇳🇴">Norvegia</option>
                                            <option value="DK" data-flag="🇩🇰">Danimarca</option>
                                            <option value="FI" data-flag="🇫🇮">Finlandia</option>
                                            <option value="IE" data-flag="🇮🇪">Irlanda</option>
                                            <option value="GR" data-flag="🇬🇷">Grecia</option>
                                            <option value="CZ" data-flag="🇨🇿">Repubblica Ceca</option>
                                            <option value="RO" data-flag="🇷🇴">Romania</option>
                                            <option value="HU" data-flag="🇭🇺">Ungheria</option>
                                            <option value="US" data-flag="🇺🇸">Stati Uniti</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <x-input name="phone" label="Cellulare" required />
                                </div>

                                <div class="form-group">
                                    <x-input name="fiscal_code" label="Codice fiscale" required />
                                </div>

                                <div class="form-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" id="privacy" name="privacy" required>
                                        <span>Accetto la <a href="#" target="_blank">Privacy Policy</a> *</span>
                                    </label>
                                </div>

                                <div class="form-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" id="newsletter" name="newsletter">
                                        <span>Desidero ricevere comunicazioni commerciali</span>
                                    </label>
                                </div>
                            </form>
                        </div>
                    </x-card>
                    </div>

                    <div class="cart-actions" id="step2-actions" style="display: none;">
                        <button id="btn-back-step1" type="button" class="bt-miticko bt-m-text-only" data-mode="small secondary">
                            Indietro
                        </button>
                        <button id="btn-to-payment" type="button" class="bt-miticko bt-m-default btn-confirm" data-mode="small">
                            Conferma dati
                        </button>
                    </div>

                    {{-- Step 3: Pagamento --}}
                    <div id="step3-card" style="display: none;">
                    <x-card class="cart-card step-card" title="Completa il pagamento" sub_title="inserisci i dati della carta per completare l'acquisto">
                        <div class="cart-item">
                            <div class="payment-summary-compact">
                                <div class="summary-row">
                                    <span>Totale da pagare</span>
                                    <span class="summary-total">{{ Utils::price($cart->total) }}</span>
                                </div>
                            </div>

                            <div id="stripe-payment-container">
                                <div id="stripe-loading" class="stripe-loading">
                                    <i class="fa-solid fa-spinner fa-spin"></i>
                                    <span>Caricamento metodi di pagamento...</span>
                                </div>
                                <div id="payment-element"></div>
                                <div id="payment-message" class="payment-message" style="display: none;"></div>
                            </div>
                        </div>
                    </x-card>
                    </div>

                    <div class="cart-actions" id="step3-actions" style="display: none;">
                        <button id="btn-back-step2" type="button" class="bt-miticko bt-m-text-only" data-mode="small secondary">
                            Indietro
                        </button>
                        <button id="btn-pay" type="button" class="bt-miticko bt-m-default btn-confirm" data-mode="small">
                            Paga {{ Utils::price($cart->total) }}
                        </button>
                    </div>
                @else
                    <x-card class="cart-card mt-5" h1="true" leading="fa-shopping-cart">
                        <b>Nessun prodotto nel carrello</b>
                        <br />
                        <br />
                        Sfoglia i prodotti e aggiungili al carrello
                    </x-card>

                    <a href="{{ url('/shop') }}" class="bt-miticko bt-m-default w-100 mt-4" data-mode="small">
                         Vai ai prodotti <i class="fa-regular fa-arrow-right icon"></i>
                    </a>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<style>
    .cart-item {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .cart-item-header {
        border-bottom: 1px solid var(--neutral-grey-10, #E6E6E6);
        padding-bottom: 16px;
    }

    .cart-item-header .product-name {
        font-family: var(--font-font-1, "DM Sans"), sans-serif;
        font-size: var(--typography-title-size-medium, 20px);
        font-weight: var(--typography-title-weight-medium, 600);
        line-height: var(--typography-title-lineheight-medium, 26px);
        color: var(--text-main, #0D0D0D);
        margin-bottom: 4px;
    }

    .cart-item-header .product-partner {
        font-family: var(--font-font-2, "DM Sans"), sans-serif;
        font-size: var(--typography-body-size-small, 14px);
        font-weight: var(--typography-body-weight-small, 300);
        color: var(--text-secondary, #666);
        margin-bottom: 0;
    }

    .cart-item-details {
        display: flex;
        gap: 16px;
        padding: 16px;
        border-radius: var(--border-radius-m, 8px);
    }

    .cart-product-image {
        flex-shrink: 0;
        width: 120px;
        height: 120px;
        border-radius: var(--border-radius-s, 8px);
        overflow: hidden;
    }

    .cart-product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .cart-product-image .no-image {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: var(--neutral-grey-10, #E6E6E6);
        color: var(--text-disabled, #999);
        font-size: 32px;
    }

    .cart-product-info {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .cart-product-info .product-title {
        font-family: var(--font-font-1, "DM Sans"), sans-serif;
        font-size: var(--typography-title-size-small, 16px);
        font-weight: var(--typography-title-weight-small, 600);
        color: var(--text-main, #0D0D0D);
        margin: 0;
    }

    .cart-product-info .product-partner {
        font-family: var(--font-font-2, "DM Sans"), sans-serif;
        font-size: var(--typography-body-size-small, 14px);
        font-weight: var(--typography-body-weight-small, 300);
        color: var(--text-secondary, #666);
        margin: 0;
    }

    .product-meta {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-family: var(--font-font-2, "DM Sans"), sans-serif;
        font-size: var(--typography-body-size-small, 14px);
        color: var(--text-secondary, #666);
    }

    .meta-item i {
        color: var(--secondary-brand, #2A3493);
    }

    @media (max-width: 576px) {
        .cart-item-details {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .cart-product-image {
            width: 100%;
            height: 180px;
        }

        .product-meta {
            align-items: center;
        }
    }

    .cart-tickets {
        border-radius: var(--border-radius-m, 8px);
        padding: 16px;
    }

    .cart-tickets h4 {
        font-family: var(--font-font-1, "DM Sans"), sans-serif;
        font-size: var(--typography-title-size-small, 14px);
        font-weight: var(--typography-title-weight-small, 700);
        color: var(--text-main, #0D0D0D);
        margin-bottom: 12px;
    }

    .ticket-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
    }

    .ticket-row:last-child {
        border-bottom: none;
    }

    .ticket-row.ticket-subtotal {
        border-bottom: none;
    }

    .ticket-row.ticket-subtotal .ticket-type {
        font-weight: var(--typography-title-weight-small, 600);
    }

    .ticket-row.ticket-subtotal .ticket-price {
        font-weight: var(--typography-title-weight-small, 600);
    }

    .cart-item-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }


    .ticket-type {
        font-family: var(--font-font-2, "DM Sans"), sans-serif;
        font-size: var(--typography-body-size-medium, 16px);
        font-weight: var(--typography-body-weight-medium, 400);
        color: var(--text-main, #0D0D0D);
        flex: 1;
    }

    .ticket-quantity {
        font-family: var(--font-font-2, "DM Sans"), sans-serif;
        font-size: var(--typography-body-size-medium, 16px);
        color: var(--text-secondary, #666);
        width: 60px;
        text-align: center;
    }

    .ticket-price {
        font-family: var(--font-font-1, "DM Sans"), sans-serif;
        font-size: var(--typography-body-size-medium, 16px);
        font-weight: var(--typography-body-weight-medium, 500);
        color: var(--text-main, #0D0D0D);
        width: 100px;
        text-align: right;
    }

    .cart-total {
        border-top: 1px solid var(--neutral-grey-10, #E6E6E6);
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px;
        height: 60px;
    }

    .total-label {
        font-family: var(--font-font-1, "DM Sans"), sans-serif;
        font-size: var(--typography-title-size-medium, 20px);
        font-weight: var(--typography-title-weight-medium, 600);
        color: var(--text-main, #0D0D0D);
    }

    .total-amount {
        font-family: var(--font-font-1, "DM Sans"), sans-serif;
        font-size: var(--typography-title-size-large, 28px);
        font-weight: var(--typography-title-weight-large, 700);
    }

    .cart-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 24px;
        gap: 16px;
    }

    .empty-cart {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 48px 24px;
        text-align: center;
    }

    .empty-cart-icon {
        font-size: 64px;
        color: var(--neutral-grey-10, #E6E6E6);
        margin-bottom: 24px;
    }

    .empty-cart p {
        font-family: var(--font-font-2, "DM Sans"), sans-serif;
        font-size: var(--typography-body-size-medium, 16px);
        color: var(--text-secondary, #666);
        margin-bottom: 24px;
    }
    .button-progress {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 10px 0;
        gap: 0;
    }

    .button-progress .progress-connector {
        width: 8px;
        height: 1px;
        background-color: var(--neutral-grey-10, #E6E6E6);
    }
    .btn-confirm {
        width: 70% !important;
    }

    /* Form styles */
    .customer-form {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .form-group label {
        font-family: var(--font-font-2, "DM Sans"), sans-serif;
        font-size: var(--typography-body-size-small, 14px);
        font-weight: var(--typography-body-weight-medium, 500);
        color: var(--text-main, #0D0D0D);
    }

    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group input[type="tel"] {
        padding: 12px 16px;
        border: 1px solid var(--neutral-grey-10, #E6E6E6);
        border-radius: var(--border-radius-s, 8px);
        font-family: var(--font-font-2, "DM Sans"), sans-serif;
        font-size: var(--typography-body-size-medium, 16px);
        transition: border-color 0.2s ease;
    }

    .form-group input[type="text"]:focus,
    .form-group input[type="email"]:focus,
    .form-group input[type="tel"]:focus {
        outline: none;
        border-color: var(--secondary-brand, #2A3493);
    }

    .form-group input.error {
        border-color: var(--error, #DC3545);
    }

    /* Country select with flag */
    .country-select-wrapper {
        display: flex;
        align-items: center;
        position: relative;
    }

    .country-flag {
        position: absolute;
        left: 12px;
        font-size: 20px;
        line-height: 1;
        z-index: 1;
        background: white;
        border-radius: 4px;
        padding: 2px;
    }

    .country-select-wrapper select {
        width: 100%;
        padding: 12px 16px 12px 48px;
        border: 1px solid var(--neutral-grey-10, #E6E6E6);
        border-radius: var(--border-radius-s, 8px);
        font-family: var(--font-font-2, "DM Sans"), sans-serif;
        font-size: var(--typography-body-size-medium, 16px);
        background-color: white;
        cursor: pointer;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23666' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        transition: border-color 0.2s ease;
    }

    .country-select-wrapper select:focus {
        outline: none;
        border-color: var(--secondary-brand, #2A3493);
    }

    .checkbox-label {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        cursor: pointer;
        font-weight: normal !important;
    }

    .checkbox-label input[type="checkbox"] {
        width: 18px;
        height: 18px;
        margin-top: 2px;
        cursor: pointer;
    }

    .checkbox-label span {
        font-family: var(--font-font-2, "DM Sans"), sans-serif;
        font-size: var(--typography-body-size-small, 14px);
        color: var(--text-secondary, #666);
    }

    .checkbox-label a {
        color: var(--secondary-brand, #2A3493);
        text-decoration: underline;
    }

    .step-card {
        margin-top: 0;
    }

    /* Payment step styles */
    .payment-summary {
        background-color: var(--neutral-grey-2, #F5F5F5);
        border-radius: var(--border-radius-m, 8px);
        padding: 16px;
        margin-bottom: 20px;
    }

    .payment-summary h4 {
        font-family: var(--font-font-1, "DM Sans"), sans-serif;
        font-size: var(--typography-title-size-small, 14px);
        font-weight: var(--typography-title-weight-small, 700);
        color: var(--text-main, #0D0D0D);
        margin-bottom: 12px;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        font-family: var(--font-font-2, "DM Sans"), sans-serif;
        font-size: var(--typography-body-size-small, 14px);
        color: var(--text-secondary, #666);
        border-bottom: 1px solid var(--neutral-grey-10, #E6E6E6);
    }

    .summary-row:last-child {
        border-bottom: none;
    }

    .summary-row.summary-total {
        padding-top: 12px;
        margin-top: 8px;
        border-top: 1px solid var(--neutral-grey-10, #E6E6E6);
        border-bottom: none;
        font-family: var(--font-font-1, "DM Sans"), sans-serif;
        font-size: var(--typography-body-size-medium, 16px);
        font-weight: var(--typography-title-weight-small, 600);
        color: var(--text-main, #0D0D0D);
    }

    .summary-row.summary-total span:last-child {
        color: var(--secondary-brand, #2A3493);
        font-size: var(--typography-title-size-medium, 20px);
    }

    .payment-methods h4 {
        font-family: var(--font-font-1, "DM Sans"), sans-serif;
        font-size: var(--typography-title-size-small, 14px);
        font-weight: var(--typography-title-weight-small, 700);
        color: var(--text-main, #0D0D0D);
        margin-bottom: 12px;
    }

    .payment-option {
        margin-bottom: 12px;
    }

    .payment-label {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px;
        border: 1px solid var(--neutral-grey-10, #E6E6E6);
        border-radius: var(--border-radius-s, 8px);
        cursor: pointer;
        transition: border-color 0.2s ease, background-color 0.2s ease;
    }

    .payment-label:hover {
        border-color: var(--primary-brand);
    }

    .payment-label input[type="radio"] {
        width: 20px;
        height: 20px;
        cursor: pointer;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        border: 2px solid var(--neutral-grey-10, #E6E6E6);
        border-radius: 50%;
        outline: none;
        transition: all 0.2s ease;
        position: relative;
        flex-shrink: 0;
    }

    .payment-label input[type="radio"]:checked {
        border-color:  var(--primary-brand);
        background-color: white;
    }

    .payment-label input[type="radio"]:checked::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 10px;
        height: 10px;
        background-color: var(--primary-brand);
        border-radius: 50%;
    }

    .payment-label:has(input[type="radio"]:checked) {
        border-color:  var(--primary-brand);
        background-color:  var(--light-background);
    }

    .payment-icon {
        font-size: 24px;
        color: var(--text-secondary, #666);
        width: 32px;
        text-align: center;
    }

    .payment-text {
        font-family: var(--font-font-2, "DM Sans"), sans-serif;
        font-size: var(--typography-body-size-medium, 16px);
        color: var(--text-main, #0D0D0D);
    }

    @media (max-width: 576px) {
        .form-row {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 576px) {
        .cart-actions {
            flex-direction: column;
        }

        .cart-actions .bt-miticko {
            width: 100%;
        }

        .cart-item-actions {
            justify-content: center;
        }
    }

    /* Stripe Payment Element styles */
    #stripe-payment-container {
        margin-top: 16px;
    }

    .stripe-loading {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        padding: 32px;
        color: var(--text-secondary, #666);
        font-family: var(--font-font-2, "DM Sans"), sans-serif;
        font-size: var(--typography-body-size-medium, 16px);
    }

    .stripe-loading i {
        font-size: 24px;
        color: var(--primary-brand);
    }

    #payment-element {
        padding: 16px;
        background-color: var(--neutral-grey-2, #F5F5F5);
        border-radius: var(--border-radius-m, 8px);
    }

    .payment-message {
        margin-top: 16px;
        padding: 12px 16px;
        border-radius: var(--border-radius-s, 8px);
        font-family: var(--font-font-2, "DM Sans"), sans-serif;
        font-size: var(--typography-body-size-small, 14px);
    }

    .payment-message.error {
        background-color: rgba(220, 53, 69, 0.1);
        color: var(--error, #DC3545);
        border: 1px solid var(--error, #DC3545);
    }

    .payment-message.success {
        background-color: rgba(40, 167, 69, 0.1);
        color: var(--success, #28a745);
        border: 1px solid var(--success, #28a745);
    }

    .payment-summary-compact {
        background-color: var(--neutral-grey-2, #F5F5F5);
        border-radius: var(--border-radius-m, 8px);
        padding: 16px;
        margin-bottom: 16px;
    }

    .payment-summary-compact .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-family: var(--font-font-2, "DM Sans"), sans-serif;
        font-size: var(--typography-body-size-medium, 16px);
        color: var(--text-main, #0D0D0D);
    }

    .payment-summary-compact .summary-total {
        font-family: var(--font-font-1, "DM Sans"), sans-serif;
        font-size: var(--typography-title-size-medium, 20px);
        font-weight: var(--typography-title-weight-medium, 700);
        color: var(--secondary-brand, #2A3493);
    }

    .btn-pay-loading {
        pointer-events: none;
        opacity: 0.7;
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Elementi
        const btnRemove = document.getElementById('btn-remove-cart');
        const btnCheckout = document.getElementById('btn-checkout');
        const btnBackStep1 = document.getElementById('btn-back-step1');
        const btnToPayment = document.getElementById('btn-to-payment');

        // Step cards e actions
        const step1Card = document.getElementById('step1-card');
        const step2Card = document.getElementById('step2-card');
        const step3Card = document.getElementById('step3-card');
        const step1Actions = document.getElementById('step1-actions');
        const step2Actions = document.getElementById('step2-actions');
        const step3Actions = document.getElementById('step3-actions');
        const btnBackStep2 = document.getElementById('btn-back-step2');
        const btnPay = document.getElementById('btn-pay');

        // Progress buttons e connectors
        const btnStepRiepilogo = document.getElementById('btn-step-riepilogo');
        const btnStepDati = document.getElementById('btn-step-dati');
        const btnStepPagamento = document.getElementById('btn-step-pagamento');
        const connectorDati = document.getElementById('connector-dati');
        const connectorPagamento = document.getElementById('connector-pagamento');

        // Funzione per passare allo step 2
        function goToStep2() {
            // Nascondi step 1
            if (step1Card) step1Card.style.display = 'none';
            if (step1Actions) step1Actions.style.display = 'none';

            // Mostra step 2
            if (step2Card) step2Card.style.display = 'block';
            if (step2Actions) step2Actions.style.display = 'flex';

            // Aggiorna progress bar: "Riepilogo" completato, "Dati" attivo
            if (btnStepRiepilogo) {
                btnStepRiepilogo.classList.remove('bt-m-default');
                btnStepRiepilogo.classList.add('bt-m-outlined');
                btnStepRiepilogo.removeAttribute('disabled');
                btnStepRiepilogo.setAttribute('data-mode', 'small secondary');
                btnStepRiepilogo.innerHTML = '<i class="fa fa-check icon"></i> Riepilogo';
            }
            if (btnStepDati) {
                btnStepDati.classList.remove('bt-m-outlined');
                btnStepDati.classList.add('bt-m-default');
                btnStepDati.removeAttribute('disabled');
                btnStepDati.setAttribute('data-mode', 'small secondary');
            }
        }

        // Funzione per tornare allo step 1
        function goToStep1() {
            // Mostra step 1
            if (step1Card) step1Card.style.display = 'block';
            if (step1Actions) step1Actions.style.display = 'flex';

            // Nascondi step 2 e 3
            if (step2Card) step2Card.style.display = 'none';
            if (step2Actions) step2Actions.style.display = 'none';
            if (step3Card) step3Card.style.display = 'none';
            if (step3Actions) step3Actions.style.display = 'none';

            // Aggiorna progress bar: "Riepilogo" attivo, "Dati" e "Pagamento" disabilitati
            if (btnStepRiepilogo) {
                btnStepRiepilogo.classList.remove('bt-m-outlined');
                btnStepRiepilogo.classList.add('bt-m-default');
                btnStepRiepilogo.setAttribute('data-mode', 'small secondary');
                btnStepRiepilogo.innerHTML = 'Riepilogo';
            }
            if (btnStepDati) {
                btnStepDati.classList.remove('bt-m-default');
                btnStepDati.classList.add('bt-m-outlined');
                btnStepDati.setAttribute('disabled', 'disabled');
                btnStepDati.setAttribute('data-mode', 'small disabled');
                btnStepDati.innerHTML = 'Dati';
            }
            if (btnStepPagamento) {
                btnStepPagamento.setAttribute('disabled', 'disabled');
                btnStepPagamento.setAttribute('data-mode', 'small disabled');
            }
        }

        // Funzione per passare allo step 3
        function goToStep3() {
            // Nascondi step 2
            if (step2Card) step2Card.style.display = 'none';
            if (step2Actions) step2Actions.style.display = 'none';

            // Mostra step 3
            if (step3Card) step3Card.style.display = 'block';
            if (step3Actions) step3Actions.style.display = 'flex';

            // Aggiorna progress bar: "Dati" completato, "Pagamento" attivo
            if (btnStepDati) {
                btnStepDati.classList.remove('bt-m-default');
                btnStepDati.classList.add('bt-m-outlined');
                btnStepDati.innerHTML = '<i class="fa fa-check icon"></i> Dati';
            }
            if (btnStepPagamento) {
                btnStepPagamento.classList.remove('bt-m-outlined');
                btnStepPagamento.classList.add('bt-m-default');
                btnStepPagamento.removeAttribute('disabled');
                btnStepPagamento.setAttribute('data-mode', 'small secondary');
            }
        }

        // Funzione per tornare allo step 2
        function goBackToStep2() {
            // Nascondi step 3
            if (step3Card) step3Card.style.display = 'none';
            if (step3Actions) step3Actions.style.display = 'none';

            // Mostra step 2
            if (step2Card) step2Card.style.display = 'block';
            if (step2Actions) step2Actions.style.display = 'flex';

            // Aggiorna progress bar: "Dati" attivo, "Pagamento" disabilitato
            if (btnStepDati) {
                btnStepDati.classList.remove('bt-m-outlined');
                btnStepDati.classList.add('bt-m-default');
                btnStepDati.innerHTML = 'Dati';
            }
            if (btnStepPagamento) {
                btnStepPagamento.classList.remove('bt-m-default');
                btnStepPagamento.classList.add('bt-m-outlined');
                btnStepPagamento.setAttribute('disabled', 'disabled');
                btnStepPagamento.setAttribute('data-mode', 'small disabled');
            }
        }

        // Event listener per "Conferma biglietti"
        if (btnCheckout) {
            btnCheckout.addEventListener('click', goToStep2);
        }

        // Event listener per "Indietro"
        if (btnBackStep1) {
            btnBackStep1.addEventListener('click', goToStep1);
        }

        // Event listener per click su "Riepilogo" nel progress
        if (btnStepRiepilogo) {
            btnStepRiepilogo.addEventListener('click', function() {
                if (!this.hasAttribute('disabled')) {
                    goToStep1();
                }
            });
        }

        // Event listener per click su "Dati" nel progress
        if (btnStepDati) {
            btnStepDati.addEventListener('click', function() {
                if (!this.hasAttribute('disabled')) {
                    goToStep2();
                }
            });
        }

        // Event listener per cambio paese (aggiorna bandiera)
        const countrySelect = document.getElementById('country');
        const selectedFlag = document.getElementById('selected-flag');
        if (countrySelect && selectedFlag) {
            countrySelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const flag = selectedOption.getAttribute('data-flag');
                if (flag) {
                    selectedFlag.textContent = flag;
                }
            });
        }

        // Event listener per "Procedi al pagamento"
        if (btnToPayment) {
            btnToPayment.addEventListener('click', function() {
                const form = document.getElementById('customer-form');
                const name = form.querySelector('[name="name"]');
                const surname = form.querySelector('[name="surname"]');
                const email = form.querySelector('[name="email"]');
                const address = form.querySelector('[name="address"]');
                const zipCode = form.querySelector('[name="zip_code"]');
                const city = form.querySelector('[name="city"]');
                const country = form.querySelector('[name="country"]');
                const phone = form.querySelector('[name="phone"]');
                const fiscalCode = form.querySelector('[name="fiscal_code"]');
                const birthDate = form.querySelector('[name="birth_date"]');
                const privacy = document.getElementById('privacy');
                const newsletter = document.getElementById('newsletter');

                // Reset errori
                form.querySelectorAll('input, select').forEach(input => {
                    input.classList.remove('error');
                });

                let hasError = false;

                // Validazione campi obbligatori
                if (!name || !name.value.trim()) {
                    if (name) name.classList.add('error');
                    hasError = true;
                }
                if (!surname || !surname.value.trim()) {
                    if (surname) surname.classList.add('error');
                    hasError = true;
                }
                if (!email || !email.value.trim()) {
                    if (email) email.classList.add('error');
                    hasError = true;
                }
                if (!address || !address.value.trim()) {
                    if (address) address.classList.add('error');
                    hasError = true;
                }
                if (!zipCode || !zipCode.value.trim()) {
                    if (zipCode) zipCode.classList.add('error');
                    hasError = true;
                }
                if (!city || !city.value.trim()) {
                    if (city) city.classList.add('error');
                    hasError = true;
                }
                if (!phone || !phone.value.trim()) {
                    if (phone) phone.classList.add('error');
                    hasError = true;
                }
                if (!privacy || !privacy.checked) {
                    alert('Devi accettare la Privacy Policy per procedere');
                    hasError = true;
                }

                if (hasError) {
                    return;
                }

                // Disabilita il bottone
                const btn = this;
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Salvataggio...';

                // Salva i dati cliente
                fetch('/shop/cart/customer', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        name: name.value.trim(),
                        surname: surname.value.trim(),
                        email: email.value.trim(),
                        address: address.value.trim(),
                        zip_code: zipCode.value.trim(),
                        city: city.value.trim(),
                        country: country ? country.value : 'IT',
                        phone: phone.value.trim(),
                        fiscal_code: fiscalCode ? fiscalCode.value.trim() : null,
                        birth_date: birthDate ? birthDate.value : null,
                        privacy: privacy.checked,
                        newsletter: newsletter ? newsletter.checked : false
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        goToStep3();
                    } else {
                        alert(data.error || 'Errore durante il salvataggio dei dati');
                    }
                    btn.disabled = false;
                    btn.innerHTML = 'Procedi al pagamento';
                })
                .catch(error => {
                    console.error('Errore:', error);
                    alert('Errore durante il salvataggio dei dati');
                    btn.disabled = false;
                    btn.innerHTML = 'Procedi al pagamento';
                });
            });
        }

        // Event listener per "Indietro" dallo step 3
        if (btnBackStep2) {
            btnBackStep2.addEventListener('click', goBackToStep2);
        }

        // Event listener per click su "Pagamento" nel progress
        if (btnStepPagamento) {
            btnStepPagamento.addEventListener('click', function() {
                if (!this.hasAttribute('disabled')) {
                    goToStep3();
                }
            });
        }

        // Rimozione carrello
        if (btnRemove) {
            btnRemove.addEventListener('click', function() {
                if (!confirm('Sei sicuro di voler rimuovere il prodotto dal carrello?')) {
                    return;
                }

                this.disabled = true;
                this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Rimozione...';

                fetch('/shop/cart/remove', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.redirect_url) {
                        window.location.href = data.redirect_url;
                    } else {
                        alert(data.error || 'Errore durante la rimozione');
                        this.disabled = false;
                        this.innerHTML = '<i class="fa-regular fa-trash-can"></i> Rimuovi';
                    }
                })
                .catch(error => {
                    console.error('Errore:', error);
                    alert('Errore durante la rimozione');
                    this.disabled = false;
                    this.innerHTML = '<i class="fa-regular fa-trash-can"></i> Rimuovi';
                });
            });
        }

        // Stripe Payment Element
        let stripe = null;
        let elements = null;
        let paymentElement = null;
        let clientSecret = null;
        let stripeInitialized = false;

        // Inizializza Stripe quando si arriva allo step 3
        async function initializeStripe() {
            if (stripeInitialized) return;

            const stripeLoading = document.getElementById('stripe-loading');
            const paymentElementContainer = document.getElementById('payment-element');
            const paymentMessage = document.getElementById('payment-message');

            try {
                // Crea PaymentIntent sul server
                const response = await fetch('/shop/payment/create-intent', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error || 'Errore nella creazione del pagamento');
                }

                clientSecret = data.clientSecret;

                // Inizializza Stripe
                stripe = Stripe('{{ config('services.stripe.key') }}');

                // Crea Elements
                elements = stripe.elements({
                    clientSecret: clientSecret,
                    appearance: {
                        theme: 'stripe',
                        variables: {
                            colorPrimary: getComputedStyle(document.documentElement).getPropertyValue('--primary-brand').trim() || '#2A3493',
                            colorBackground: '#ffffff',
                            colorText: '#0D0D0D',
                            colorDanger: '#DC3545',
                            fontFamily: '"DM Sans", sans-serif',
                            borderRadius: '8px',
                        },
                    },
                    locale: 'it'
                });

                // Monta il Payment Element
                paymentElement = elements.create('payment', {
                    layout: 'tabs'
                });

                // Nascondi loading e mostra payment element
                if (stripeLoading) stripeLoading.style.display = 'none';
                paymentElement.mount('#payment-element');

                stripeInitialized = true;

            } catch (error) {
                console.error('Errore inizializzazione Stripe:', error);
                if (stripeLoading) stripeLoading.style.display = 'none';
                showPaymentMessage(error.message || 'Errore nel caricamento dei metodi di pagamento', 'error');
            }
        }

        // Mostra messaggio di errore/successo
        function showPaymentMessage(message, type = 'error') {
            const paymentMessage = document.getElementById('payment-message');
            if (paymentMessage) {
                paymentMessage.textContent = message;
                paymentMessage.className = 'payment-message ' + type;
                paymentMessage.style.display = 'block';
            }
        }

        // Nascondi messaggio
        function hidePaymentMessage() {
            const paymentMessage = document.getElementById('payment-message');
            if (paymentMessage) {
                paymentMessage.style.display = 'none';
            }
        }

        // Gestisci click sul bottone Paga
        if (btnPay) {
            btnPay.addEventListener('click', async function() {
                if (!stripe || !elements) {
                    showPaymentMessage('Sistema di pagamento non inizializzato. Riprova.', 'error');
                    return;
                }

                hidePaymentMessage();

                // Disabilita il bottone
                btnPay.disabled = true;
                btnPay.classList.add('btn-pay-loading');
                btnPay.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Elaborazione...';

                try {
                    // Conferma il pagamento con Stripe
                    const { error, paymentIntent } = await stripe.confirmPayment({
                        elements,
                        confirmParams: {
                            return_url: window.location.origin + '/shop/cart',
                        },
                        redirect: 'if_required'
                    });

                    if (error) {
                        // Errore di pagamento
                        let errorMessage = 'Si è verificato un errore durante il pagamento.';

                        if (error.type === 'card_error' || error.type === 'validation_error') {
                            errorMessage = error.message;
                        }

                        showPaymentMessage(errorMessage, 'error');
                        btnPay.disabled = false;
                        btnPay.classList.remove('btn-pay-loading');
                        btnPay.innerHTML = 'Paga {{ Utils::price($cart->total) }}';
                        return;
                    }

                    // Pagamento riuscito o in elaborazione
                    if (paymentIntent && (paymentIntent.status === 'succeeded' || paymentIntent.status === 'processing')) {
                        // Conferma l'ordine sul server
                        const confirmResponse = await fetch('/shop/payment/confirm', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                payment_intent_id: paymentIntent.id
                            })
                        });

                        const confirmData = await confirmResponse.json();

                        if (confirmData.success && confirmData.redirect_url) {
                            showPaymentMessage('Pagamento completato! Reindirizzamento...', 'success');
                            window.location.href = confirmData.redirect_url;
                        } else {
                            throw new Error(confirmData.error || 'Errore nella conferma dell\'ordine');
                        }
                    } else {
                        showPaymentMessage('Stato del pagamento: ' + (paymentIntent?.status || 'sconosciuto'), 'error');
                        btnPay.disabled = false;
                        btnPay.classList.remove('btn-pay-loading');
                        btnPay.innerHTML = 'Paga {{ Utils::price($cart->total) }}';
                    }

                } catch (error) {
                    console.error('Errore pagamento:', error);
                    showPaymentMessage(error.message || 'Errore durante il pagamento', 'error');
                    btnPay.disabled = false;
                    btnPay.classList.remove('btn-pay-loading');
                    btnPay.innerHTML = 'Paga {{ Utils::price($cart->total) }}';
                }
            });
        }

        // Modifica la funzione goToStep3 per inizializzare Stripe
        const originalGoToStep3 = goToStep3;
        goToStep3 = function() {
            originalGoToStep3();
            // Inizializza Stripe quando si arriva allo step 3
            initializeStripe();
        };
    });
</script>
@endpush
