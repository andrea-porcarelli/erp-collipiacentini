@props([
    'product' => null
])
<div class="col-12">
    <div class="card h-100 border-0 shadow-sm">
        <div class="row g-0">
            <div class="col-md-3">
                <div class="position-relative">
                    <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjI1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICA8cmVjdCB3aWR0aD0iNDAwIiBoZWlnaHQ9IjI1MCIgZmlsbD0iIzY2YTY1OSIvPgogIDx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTYiIGZpbGw9IndoaXRlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+Q2FzdGVsbG8gZGkgTmV1c2Nod2Fuc3RlaW48L3RleHQ+Cjwvc3ZnPg=="
                         class="card-img-top" alt="{{ $product->label }}" style="height: 200px; object-fit: cover;">
                    <div class="position-absolute top-0 end-0 m-2">
                        <span class="badge" style="background-color: var(--primary-brand); color: var(--primary-onbrand);">2 ore</span>
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <div class="card-body">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="visitaSelezionata" id="castello1" value="castello1">
                        <label class="form-check-label fw-bold fs-5" for="castello1">
                            {{ $product->label }}
                        </label>
                    </div>
                    <p class="card-text custom-text-secondary mb-3">
                        {!! $product->description !!}
                    </p>

                    <!-- Caratteristiche con icone -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <div class="d-flex align-items-center custom-text-secondary">
                                <i class="fas fa-users me-2 custom-text-primary"></i>
                                <span>Max 25 persone</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex align-items-center custom-text-secondary">
                                <i class="fas fa-camera me-2 custom-text-primary"></i>
                                <span>Foto consentite</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex align-items-center custom-text-secondary">
                                <i class="fas fa-microphone me-2 custom-text-primary"></i>
                                <span>Guida esperta</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex align-items-center custom-text-secondary">
                                <i class="fas fa-wheelchair me-2 custom-text-primary"></i>
                                <span>Accessibile</span>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fs-4 fw-bold custom-text-primary">€25</span>
                            <small class="custom-text-muted d-block">€15 ridotto</small>
                        </div>
                        <div class="text-end">
                            <div class="custom-text-success mb-1">
                                <i class="fas fa-star me-1"></i>4.8/5 (234 recensioni)
                            </div>
                            <small class="custom-text-success">
                                <i class="fas fa-clock me-1"></i>Durata: 2 ore
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
