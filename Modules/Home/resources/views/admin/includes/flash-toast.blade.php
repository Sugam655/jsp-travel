{{-- Flash message rendered as a SweetAlert2 toast notification.

     This is the one place a flash message is shown in the panel, for the admin
     area and the customer area alike. It is deliberately the only rendering:
     a Bootstrap .alert left in the page content sits there until it is clicked
     away, which reads as a permanent message rather than a confirmation.

     Being a toast, it is transient by construction - it draws itself over the
     page, closes itself on a timer, and is gone. It is not a second delivery
     channel: the flash it reads is ordinary session flash data, so the message
     is available for exactly the one request that follows the redirect that set
     it and is never seen again on a refresh. Anything that must outlive the
     request - a booking update the customer has not read yet - belongs in the
     notification bell, not here. --}}

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
                    showCloseButton: true,
                    timer: 4500,
                    timerProgressBar: true,
                });
            }
        });
    </script>
    @endpush
@endif
