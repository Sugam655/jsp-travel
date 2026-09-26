{{-- Shows the selected file name next to any input[type=file] that
     declares a data-filename-label pointing to a <span id=...>. --}}

@push('js')
<script>
    window._AdminLTE_Ready(() => {
        document.addEventListener('change', (event) => {
            const input = event.target.closest('input[type="file"][data-filename-label]');

            if (!input) {
                return;
            }

            const label = document.getElementById(input.dataset.filenameLabel);

            if (label) {
                label.textContent = input.files && input.files[0]
                    ? 'Selected file: ' + input.files[0].name
                    : '';
            }
        });
    });
</script>
@endpush