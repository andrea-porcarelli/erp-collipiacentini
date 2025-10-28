@extends('whitelabel.layout', compact('company'))

@section('content')
    <div class="container mt-4">
        <div class="row">
            <!-- Main Content Area (70%) -->
            <div class="col-lg-8" style="min-height: 800px">
                <!-- Tours Section -->
                <div class="custom-card">
                    <div class="custom-card-header primary">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Visite Disponibili
                        </h5>
                    </div>
                    <div class="custom-card-body">
                        <div class="row g-4">
                            @if($products->count() === 0)
                                <div class="alert alert-danger">Nessun prodotto trovato</div>
                            @else
                                @foreach($products as $product)
                                    <x-whitelabel.product-card :product="$product" />
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
                @if($products->count() > 0)
                    <!-- Ticket Selection -->
                    <div class="custom-card">
                        <div class="custom-card-header primary">
                            <h5 class="mb-0">
                                <i class="fas fa-ticket-alt me-2"></i>
                                Seleziona i Biglietti
                            </h5>
                        </div>
                        <div class="custom-card-body">
                            <div class="row g-4">
                                <!-- Biglietto Intero -->
                                <div class="col-md-6">
                                    <div class="border rounded p-4 h-100" style="border-color: var(--primary-brand) !important; border-width: 2px !important;">
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                                                     style="width: 50px; height: 50px; background-color: var(--primary-brandlight);">
                                                    <i class="fas fa-user fs-4 custom-text-primary"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1 fw-bold">Biglietto Intero</h6>
                                                    <small class="custom-text-secondary">Adulti (18+ anni)</small>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div class="fs-3 fw-bold custom-text-primary">€25</div>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center justify-content-between">
                                            <span class="fw-semibold">Quantità:</span>
                                            <div class="d-flex align-items-center">
                                                <button class="btn btn-sm" style="background-color: var(--primary-brandlight); color: var(--primary-brand); border: 1px solid var(--primary-brand);" type="button">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                                <input type="number" class="form-control mx-2 text-center" style="width: 60px;" value="2" min="0" max="10">
                                                <button class="btn btn-sm" style="background-color: var(--primary-brandlight); color: var(--primary-brand); border: 1px solid var(--primary-brand);" type="button">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Biglietto Ridotto -->
                                <div class="col-md-6">
                                    <div class="border rounded p-4 h-100" style="border-color: var(--success-brand) !important; border-width: 2px !important;">
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                                                     style="width: 50px; height: 50px; background-color: var(--success-brandlight);">
                                                    <i class="fas fa-user-friends fs-4 custom-text-success"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1 fw-bold">Biglietto Ridotto</h6>
                                                    <small class="custom-text-secondary">Bambini, studenti, over 65</small>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div class="fs-3 fw-bold custom-text-success">€15</div>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center justify-content-between">
                                            <span class="fw-semibold">Quantità:</span>
                                            <div class="d-flex align-items-center">
                                                <button class="btn btn-sm" style="background-color: var(--success-brandlight); color: var(--success-brand); border: 1px solid var(--success-brand);" type="button">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                                <input type="number" class="form-control mx-2 text-center" style="width: 60px;" value="1" min="0" max="10">
                                                <button class="btn btn-sm" style="background-color: var(--success-brandlight); color: var(--success-brand); border: 1px solid var(--success-brand);" type="button">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Personal Information -->
                    <div class="custom-card">
                        <div class="custom-card-header primary">
                            <h5 class="mb-0">
                                <i class="fas fa-user-edit me-2"></i>
                                Dati Personali
                            </h5>
                        </div>
                        <div class="custom-card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nome" class="custom-form-label">Nome *</label>
                                    <input type="text" class="form-control custom-form-control" id="nome" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="cognome" class="custom-form-label">Cognome *</label>
                                    <input type="text" class="form-control custom-form-control" id="cognome" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="custom-form-label">Email *</label>
                                    <input type="email" class="form-control custom-form-control" id="email" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="telefono" class="custom-form-label">Telefono</label>
                                    <input type="tel" class="form-control custom-form-control" id="telefono">
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="note" class="custom-form-label">Note aggiuntive</label>
                                    <textarea class="form-control custom-form-control" id="note" rows="3" placeholder="Eventuali richieste speciali, allergie, esigenze particolari..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Confirmation -->
                    <div class="custom-card">
                        <div class="custom-card-header primary">
                            <h5 class="mb-0">
                                <i class="fas fa-check-circle me-2"></i>
                                Conferma Prenotazione
                            </h5>
                        </div>
                        <div class="custom-card-body">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="accettoTermini" required>
                                <label class="form-check-label" for="accettoTermini">
                                    Accetto i <a href="#" class="custom-text-primary">termini e condizioni</a> e la <a href="#" class="custom-text-primary">privacy policy</a> *
                                </label>
                            </div>

                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="newsletter">
                                <label class="form-check-label" for="newsletter">
                                    Desidero ricevere offerte e novità via email
                                </label>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="button" class="btn btn-outline-secondary btn-lg me-md-2">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Indietro
                                </button>
                                <button type="submit" class="custom-btn success btn-lg px-5">
                                    <i class="fas fa-credit-card me-2"></i>
                                    Procedi al Pagamento
                                </button>
                            </div>
                        </div>
                    </div>

                @endif
            </div>

            <!-- Sidebar (30%) -->
            <div class="col-lg-4">

                @if($products->count() > 0)
                <!-- Date and Time Selection -->
                <div class="custom-card">
                    <div class="custom-card-header primary">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>
                            Data e Orario
                        </h5>
                    </div>
                    <div class="custom-card-body">
                        <div class="mb-3">
                            <label for="dataVisita" class="custom-form-label fw-bold">Data della visita</label>
                            <input type="date" class="form-control custom-form-control" id="dataVisita" min="2024-11-01">
                        </div>

                        <!-- Available time slots -->
                        <div class="mb-3">
                            <label class="custom-form-label fw-bold">Orari disponibili</label>
                            <div class="d-grid gap-2">
                                <input type="radio" class="btn-check" name="orario" id="ore09" value="09:00">
                                <label class="btn custom-btn outlined-primary" for="ore09">09:00</label>

                                <input type="radio" class="btn-check" name="orario" id="ore11" value="11:00">
                                <label class="btn custom-btn outlined-primary" for="ore11">11:00</label>

                                <input type="radio" class="btn-check" name="orario" id="ore14" value="14:00" checked>
                                <label class="btn custom-btn outlined-primary" for="ore14">14:00</label>

                                <input type="radio" class="btn-check" name="orario" id="ore16" value="16:00">
                                <label class="btn custom-btn outlined-primary" for="ore16">16:00</label>

                                <input type="radio" class="btn-check" name="orario" id="ore18" value="18:00" disabled>
                                <label class="btn btn-outline-secondary" for="ore18">18:00 - Esaurito</label>
                            </div>
                        </div>

                        <div class="custom-alert info">
                            <small>
                                <i class="fas fa-info-circle me-1"></i>
                                Prenotazione obbligatoria • Cancellazione gratuita fino a 24h prima
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Booking Summary -->
                <div class="custom-card">
                    <div class="custom-card-header primary">
                        <h5 class="mb-0">
                            <i class="fas fa-shopping-cart me-2"></i>
                            Riepilogo Prenotazione
                        </h5>
                    </div>
                    <div class="custom-card-body">
                        <!-- Selected Tour -->
                        <div class="mb-3 pb-3 border-bottom">
                            <h6 class="fw-bold custom-text-primary mb-2">Castello di Neuschwanstein</h6>
                            <div class="row g-1 mb-2">
                                <div class="col-12">
                                    <small class="d-flex align-items-center custom-text-secondary">
                                        <i class="fas fa-calendar me-1 custom-text-primary"></i>
                                        <span>25 Novembre 2024</span>
                                    </small>
                                </div>
                                <div class="col-12">
                                    <small class="d-flex align-items-center custom-text-secondary">
                                        <i class="fas fa-clock me-1 custom-text-primary"></i>
                                        <span>14:00 (2 ore)</span>
                                    </small>
                                </div>
                                <div class="col-12">
                                    <small class="d-flex align-items-center custom-text-secondary">
                                        <i class="fas fa-users me-1 custom-text-primary"></i>
                                        <span>3 persone</span>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Tickets Summary -->
                        <div class="mb-3">
                            <h6 class="fw-semibold mb-2">Biglietti:</h6>
                            <div class="d-flex justify-content-between mb-1">
                                <small>2x Intero</small>
                                <small>€50.00</small>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <small>1x Ridotto</small>
                                <small>€15.00</small>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Totale</span>
                                <span class="custom-text-primary">€65.00</span>
                            </div>
                            <small class="custom-text-secondary">IVA inclusa</small>
                        </div>

                        <!-- Availability Status -->
                        <div class="text-center p-3 rounded" style="background-color: var(--success-brandlight);">
                            <div class="display-6 fw-bold custom-text-success">12/25</div>
                            <small class="custom-text-success">posti disponibili</small>
                        </div>
                    </div>
                </div>

                <!-- Payment Info -->
                <div class="custom-card">
                    <div class="custom-card-header primary">
                        <h5 class="mb-0">
                            <i class="fas fa-shield-alt me-2"></i>
                            Informazioni
                        </h5>
                    </div>
                    <div class="custom-card-body">
                        <ul class="list-unstyled mb-0 small">
                            <li class="mb-2">
                                <i class="fas fa-lock me-2 custom-text-success"></i>
                                Pagamento sicuro SSL
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-credit-card me-2 custom-text-success"></i>
                                Carta di credito/PayPal
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-envelope me-2 custom-text-success"></i>
                                Conferma immediata
                            </li>
                            <li class="mb-0">
                                <i class="fas fa-undo me-2 custom-text-success"></i>
                                Rimborso fino a 24h prima
                            </li>
                        </ul>
                    </div>
                </div>

                @endif
            </div>
        </div>
    </div>
@endsection
