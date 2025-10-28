@extends('backoffice.layout', ['title' => 'Dashboard', 'active' => $path])

@section('main-content')
    <x-header-page title="Partner" />
    <div class="w-100">
        <div class="row">
            <div class="col-12">
                <x-card title="Partner" sub_title="I nostri Partner">
                    <x-table-header>
                        <span class="table-header-total" > - </span>
                    </x-table-header>
                    <div class="table-responsive">
                        <table class="table-miticko datatable">
                            <thead>
                            <tr>
                                <th style="width: 10%">#codice</th>
                                <th>Partner</th>
                                <th>Azienda</th>
                                <th></th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </x-card>
            </div>
        </div>
    </div>
@endsection

@section('custom-script')
    <script src='https://cdn.jsdelivr.net/momentjs/latest/moment.min.js'></script>
    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/it.js"></script>
    <script src="https://cdn.datatables.net/2.3.4/js/dataTables.js"></script>
    <script>
        $(document).ready(function(){

            setTimeout(() => {
                $(document).trigger('datatable', [{
                    columns: [
                        {data: 'partner_code', type: 'string'},
                        {data: 'partner_name'},
                        {data: 'company'},
                        {data: 'action', class: 'text-end'},
                    ],
                    path: '{{ route($path . '.data') }}',
                    drawCallback: function(api) {
                        var realApi = api.api; // l'API vera è qui
                        var info = realApi.page.info();
                        $('.table-header-total').html(`${info.recordsDisplay} partner`);
                    }
                }])
            })
        })
    </script>
@endsection
