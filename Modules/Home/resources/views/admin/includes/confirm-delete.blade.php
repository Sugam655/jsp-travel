{{-- SweetAlert2 confirmation for any form flagged with data-confirm. --}}

@push('js')
<script>
    window._AdminLTE_Ready(() => {
        document.addEventListener('submit', (event) => {
            const form = event.target.closest('form[data-confirm]');

            if (!form) {
                return;
            }

            event.preventDefault();

            if (!window.Swal) {
                form.submit();
                return;
            }

            Swal.fire({
                title: form.dataset.confirmTitle || 'Are you sure?',
                text: form.dataset.confirmText || 'This record will be permanently deleted. This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'Cancel',
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
@endpush