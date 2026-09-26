<section>
    <div class="card card-primary card-outline mb-4">
        <div class="card-header">
            <h3 class="card-title">{{ __('Update Password') }}</h3>
            <p class="card-text small text-muted mb-0 mt-1">
                {{ __('Ensure your account is using a long, random password to stay secure.') }}
            </p>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('password.update') }}">
                @csrf
                @method('put')

                <div class="mb-3">
                    <label for="update_password_current_password" class="form-label">{{ __('Current Password') }}</label>
                    <input id="update_password_current_password" name="current_password" type="password"
                        class="form-control" autocomplete="current-password">
                    @if ($errors->updatePassword->get('current_password'))
                        <div class="invalid-feedback d-block">
                            @foreach ($errors->updatePassword->get('current_password') as $message)
                                {{ $message }}
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="update_password_password" class="form-label">{{ __('New Password') }}</label>
                    <input id="update_password_password" name="password" type="password"
                        class="form-control" autocomplete="new-password">
                    @if ($errors->updatePassword->get('password'))
                        <div class="invalid-feedback d-block">
                            @foreach ($errors->updatePassword->get('password') as $message)
                                {{ $message }}
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="update_password_password_confirmation" class="form-label">{{ __('Confirm Password') }}</label>
                    <input id="update_password_password_confirmation" name="password_confirmation" type="password"
                        class="form-control" autocomplete="new-password">
                    @if ($errors->updatePassword->get('password_confirmation'))
                        <div class="invalid-feedback d-block">
                            @foreach ($errors->updatePassword->get('password_confirmation') as $message)
                                {{ $message }}
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="d-flex align-items-center gap-3">
                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>

                    @if (session('status') === 'password-updated')
                        <span class="text-success">{{ __('Saved.') }}</span>
                    @endif
                </div>
            </form>
        </div>
    </div>
</section>