@props(['model'])

@php
    $authUser = Auth::user();
    $isGod = $authUser->role === 'god';

    // Account Miticko: utenti god/admin senza partner_id (visibili solo a god)
    $miticcoUsers = \App\Models\User::whereIn('role', ['god', 'admin'])
        ->whereNull('partner_id')
        ->orderBy('email')
        ->get();

    // Account Partner: utenti con partner_id = partner corrente
    $partnerUsers = \App\Models\User::where('partner_id', $model->id)
        ->orderBy('email')
        ->get();

    // Mappa id → auto_send dalle sottoscrizioni correnti
    $subscriptions = $model->reportSubscribers()->pluck('auto_send', 'users.id')->all();

    $roleLabels = [
        'god' => 'Admin miticko',
        'admin' => 'Collaboratore miticko',
    ];
    $partnerRoleLabels = [
        'admin' => 'Partner admin',
        'partner' => 'Partner collaboratore',
    ];

    // Registro invii: ultime righe (join su recipients per avere una riga per destinatario)
    $reportRows = \App\Models\PartnerReportRecipient::query()
        ->join('partner_reports', 'partner_reports.id', '=', 'partner_report_recipients.partner_report_id')
        ->where('partner_reports.partner_id', $model->id)
        ->orderByDesc('partner_report_recipients.created_at')
        ->select(
            'partner_report_recipients.*',
            'partner_reports.year as pr_year',
            'partner_reports.month as pr_month',
            'partner_reports.mode as pr_mode',
            'partner_reports.pdf_path as pr_pdf_path'
        )
        ->limit(50)
        ->get();

    $monthsIt = [1 => 'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'];
@endphp

<div class="tab-pane fade" id="partner-report-panel" role="tabpanel" data-partner-id="{{ $model->id }}">

    {{-- CARD 1: Impostazioni di invio (solo god) --}}
    @if($isGod)
        <x-card title="Impostazioni di invio" class="position-relative">
            <form id="form-partner-report-settings">
                <div class="row align-items-center">
                    <div class="col-12 col-md-9">
                        <div class="fw-semibold mb-1">Invio automatico</div>
                        <p class="text-secondary mb-0 small">Report del mese precedente, inviato il 5 del mese successivo</p>
                    </div>
                    <div class="col-12 col-md-3 text-md-end mt-3 mt-md-0">
                        <x-switch
                            name="report_auto_send_enabled"
                            class="switch-report-auto-send"
                            :checked="(bool) $model->report_auto_send_enabled" />
                    </div>
                </div>
            </form>
            <div class="button-card-absolute">
                <x-button class="btn-save-card btn-save-report-settings" label="Salva modifiche" leading="fa-save" status="Disabled" />
            </div>
        </x-card>
    @endif

    {{-- CARD 2: Registro di invio dei Report --}}
    <x-card title="Registro di invio dei Report" sub_title="Verifica lo storico di invio dei report sia automatico che manuale. Puoi inoltre scaricare i file originali che ti sono stati inviati via email" class="{{ $isGod ? 'mt-4' : '' }} position-relative">
        <div class="table-responsive">
            <table class="table table-miticko align-middle">
                <thead>
                    <tr>
                        <th>Periodo</th>
                        <th>Destinatario</th>
                        <th>Data invio</th>
                        <th>Modalità</th>
                        <th>Stato</th>
                        <th class="text-end">Download</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportRows as $row)
                        <tr>
                            <td>{{ $row->pr_year }} - {{ $monthsIt[$row->pr_month] ?? $row->pr_month }}</td>
                            <td>{{ $row->email }}</td>
                            <td>
                                @if($row->sent_at)
                                    {{ $row->sent_at->format('d M Y') }}<br>
                                    <small class="text-secondary">{{ $row->sent_at->format('H:i') }}</small>
                                @else
                                    <span class="text-secondary">—</span>
                                @endif
                            </td>
                            <td>{{ $row->pr_mode === 'auto' ? 'Automatica' : 'Manuale' }}</td>
                            <td>
                                @if($row->status === 'sent')
                                    <span class="badge bg-success-subtle text-success text-uppercase">Completato</span>
                                @elseif($row->status === 'failed')
                                    <span class="badge bg-danger-subtle text-danger text-uppercase">Errore</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary text-uppercase">In coda</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($row->pr_pdf_path)
                                    <a href="{{ route('partners.report.download-archived', ['partner' => $model->id, 'partnerReport' => $row->partner_report_id]) }}" class="link-primary">
                                        <i class="fa-regular fa-download me-1"></i>Scarica file
                                    </a>
                                @else
                                    <span class="text-secondary">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-4">Nessun report inviato</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    {{-- CARD 3: Impostazione di ricezione dei Report --}}
    <x-card title="Impostazione di ricezione dei Report" sub_title="Scegli quali utenti possono ricevere i report mensili via email" class="mt-4 position-relative">
        <form id="form-partner-report-subscriptions">

            @if($isGod)
                {{-- Account Miticko: visibile solo a god --}}
                <div class="fw-semibold mb-2">Account Miticko</div>
                <div class="table-responsive mb-4">
                    <table class="table table-miticko align-middle">
                        <thead>
                            <tr>
                                <th>Utente</th>
                                <th>Ruolo</th>
                                <th class="text-end">Invio automatico</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($miticcoUsers as $u)
                                <tr>
                                    <td>{{ $u->email }}</td>
                                    <td>{{ $roleLabels[$u->role] ?? $u->role }}</td>
                                    <td class="text-end">
                                        <x-switch
                                            name="subscription[{{ $u->id }}]"
                                            class="switch-report-subscription"
                                            :checked="(bool) ($subscriptions[$u->id] ?? false)" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-secondary py-3">Nessun account Miticko disponibile</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Account Partner: visibile sia a god che a partner --}}
            <div class="fw-semibold mb-2">Account Partner</div>
            <div class="table-responsive">
                <table class="table table-miticko align-middle">
                    <thead>
                        <tr>
                            <th>Utente</th>
                            <th>Ruolo</th>
                            <th class="text-end">Invio automatico</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($partnerUsers as $u)
                            <tr>
                                <td>{{ $u->email }}</td>
                                <td>{{ $partnerRoleLabels[$u->role] ?? $u->role }}</td>
                                <td class="text-end">
                                    <x-switch
                                        name="subscription[{{ $u->id }}]"
                                        class="switch-report-subscription"
                                        :checked="(bool) ($subscriptions[$u->id] ?? false)" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-secondary py-3">Nessun account Partner disponibile</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>
        <div class="button-card-absolute">
            <x-button class="btn-save-card btn-save-report-subscriptions" label="Salva modifiche" leading="fa-save" status="Disabled" />
        </div>
    </x-card>
</div>
