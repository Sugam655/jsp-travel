<section>
    <div class="card card-danger card-outline">
        <div class="card-header">
            <h3 class="card-title">{{ __('Delete Account') }}</h3>
            <p class="card-text small text-muted mb-0 mt-1">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
            </p>
        </div>
        <div class="card-body">
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirm-user-deletion">
                {{ __('Delete Account') }}
            </button>
        </div>
    </div>

    <div class="modal fade" id="confirm-user-deletion" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('delete')
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Are you sure you want to delete your account?') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted">
                            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                        </p>
                        <label for="password" class="sr-only form-label">{{ __('Password') }}</label>
                        <input id="password" name="password" type="password" class="form-control"
                            placeholder="{{ __('Password') }}">
                        @if ($errors->userDeletion->get('password'))
                            <div class="invalid-feedback d-block mt-1">
                                @foreach ($errors->userDeletion->get('password') as $message)
                                    {{ $message }}
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-danger">{{ __('Delete Account') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

@if ($errors->userDeletion->isNotEmpty())
    <script>
        window.addEventListener('DOMContentLoaded', function () {
            new bootstrap.Modal(document.getElementById('confirm-user-deletion')).show();
        });
    </script>
@endif