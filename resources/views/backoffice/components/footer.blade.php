<!-- BEGIN GLOBAL MANDATORY SCRIPTS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"  integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="{{ asset('backoffice/vendor/sweetalert/sweetalert.min.js') }}"></script>

<link href="{{ asset('backoffice/vendor/toastr/toastr.min.css') }}" rel="stylesheet"/>

<script src="{{ asset('backoffice/vendor/toastr/toastr.min.js') }}"></script>



<script src="{{ asset('backoffice/js/lodash.js') }}?v=1.2" type="module"></script>
<script src="{{ asset('backoffice/js/index.js') }}?v=1.3" type="module"></script>
<script>
    csrf_token = '{{ csrf_token() }}';

    $(function () {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': csrf_token
            }
        });
    })
</script>
@yield('custom-script')

@stack('scripts')
