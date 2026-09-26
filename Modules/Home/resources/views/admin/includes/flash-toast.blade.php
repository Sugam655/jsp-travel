{{-- Flash message rendered as a SweetAlert2 toast notification. --}}

@php
    $flashType = session('error') ? 'error' : (session('success') ? 'success' : null);
    $flashMessage = session($flashType ?? 'success');
@endphp

@if ($flashType)
    @push('js')
    <script>
        window._AdminLTE_Ready(() => {
            if (window.Swal && @json($flashMessage)) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: @json($flashType),
                    title: @json($flashMessage),
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                });
            }
        });
    </script>
    @endpush
@endif