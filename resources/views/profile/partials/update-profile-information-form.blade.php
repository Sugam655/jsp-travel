<section>
    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('patch')

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-check me-2" aria-hidden="true"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card card-primary card-outline mb-4">
            <div class="card-header">
                <h3 class="card-title">{{ __('Personal Information') }}</h3>
                <p class="card-text small text-muted mb-0 mt-1">
                    @if ($user->isAdmin())
                        {{ __('Your name, email address and contact details.') }}
                    @else
                        {{ __("Your account's name and email address.") }}
                    @endif
                </p>
            </div>
            <div class="card-body">
                <div class="mb-4 d-flex align-items-center gap-3">
                    @if ($user->profile_photo_url)
                        <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}"
                            style="width:64px;height:64px;object-fit:cover;border-radius:50%;" class="img-thumbnail">
                    @else
                        <div class="bg-secondary rounded-circle text-white d-flex align-items-center justify-content-center"
                            style="width:64px;height:64px;font-size:1.5rem;">
                            {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                        </div>
                    @endif

                    <div class="mb-0">
                        <label for="profile_photo" class="form-label">{{ __('Profile Photo') }} <small class="text-muted">{{ __('(optional)') }}</small></label>
                        <input id="profile_photo" name="profile_photo" type="file" accept="image/*"
                            class="form-control @error('profile_photo') is-invalid @enderror">
                        @if ($errors->get('profile_photo'))
                            <div class="invalid-feedback d-block">
                                @foreach ($errors->get('profile_photo') as $message)
                                    {{ $message }}
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <div class="mb-3">
                    <label for="name" class="form-label">{{ __('Name') }}</label>
                    <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
                    @if ($errors->get('name'))
                        <div class="invalid-feedback d-block">
                            @foreach ($errors->get('name') as $message)
                                {{ $message }}
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">{{ __('Email') }}</label>
                    <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email', $user->email) }}" required autocomplete="username">
                    @if ($errors->get('email'))
                        <div class="invalid-feedback d-block">
                            @foreach ($errors->get('email') as $message)
                                {{ $message }}
                            @endforeach
                        </div>
                    @endif

                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                        <div class="mt-2">
                            <p class="small mb-0">
                                {{ __('Your email address is unverified.') }}

                                <button form="send-verification" class="btn btn-link btn-sm p-0 align-baseline">
                                    {{ __('Click here to re-send the verification email.') }}
                                </button>
                            </p>

                            @if (session('status') === 'verification-link-sent')
                                <p class="mt-2 text-success small mb-0">
                                    {{ __('A new verification link has been sent to your email address.') }}
                                </p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card card-info card-outline mb-4">
            <div class="card-header">
                <h3 class="card-title">{{ __('Contact Information') }}</h3>
                <p class="card-text small text-muted mb-0 mt-1">
                    {{ __('How we can reach you about your bookings.') }}
                </p>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="phone" class="form-label">{{ __('Phone Number') }} <span class="text-danger">*</span></label>
                    <input id="phone" name="phone" type="tel" class="form-control @error('phone') is-invalid @enderror"
                        placeholder="+977 98xx-xxxxxx" value="{{ old('phone', $user->phone) }}" required autocomplete="tel">
                    @if ($errors->get('phone'))
                        <div class="invalid-feedback d-block">
                            @foreach ($errors->get('phone') as $message)
                                {{ $message }}
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3 mb-md-0">
                            <label for="emergency_contact" class="form-label">{{ __('Emergency Contact') }} <small class="text-muted">{{ __('(optional)') }}</small></label>
                            <input id="emergency_contact" name="emergency_contact" type="tel"
                                class="form-control @error('emergency_contact') is-invalid @enderror"
                                placeholder="+977 98xx-xxxxxx" value="{{ old('emergency_contact', $user->emergency_contact) }}"
                                autocomplete="tel">
                            @if ($errors->get('emergency_contact'))
                                <div class="invalid-feedback d-block">
                                    @foreach ($errors->get('emergency_contact') as $message)
                                        {{ $message }}
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3 mb-md-0">
                            <label for="date_of_birth" class="form-label">{{ __('Date of Birth') }} <small class="text-muted">{{ __('(optional)') }}</small></label>
                            <input id="date_of_birth" name="date_of_birth" type="date"
                                class="form-control @error('date_of_birth') is-invalid @enderror"
                                value="{{ old('date_of_birth', $user->date_of_birth) }}" max="{{ now()->toDateString() }}">
                            @if ($errors->get('date_of_birth'))
                                <div class="invalid-feedback d-block">
                                    @foreach ($errors->get('date_of_birth') as $message)
                                        {{ $message }}
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-secondary card-outline mb-4">
            <div class="card-header">
                <h3 class="card-title">{{ __('Address') }}</h3>
                <p class="card-text small text-muted mb-0 mt-1">
                    {{ __('Your street / town, city and country.') }}
                </p>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="address" class="form-label">{{ __('Address') }} <span class="text-danger">*</span></label>
                    <input id="address" name="address" type="text" class="form-control @error('address') is-invalid @enderror"
                        placeholder="Street, town" value="{{ old('address', $user->address) }}" required autocomplete="street-address">
                    @if ($errors->get('address'))
                        <div class="invalid-feedback d-block">
                            @foreach ($errors->get('address') as $message)
                                {{ $message }}
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3 mb-md-0">
                            <label for="city" class="form-label">{{ __('City') }} <span class="text-danger">*</span></label>
                            <input id="city" name="city" type="text" class="form-control @error('city') is-invalid @enderror"
                                placeholder="e.g. Kathmandu" value="{{ old('city', $user->city) }}" required autocomplete="address-level2">
                            @if ($errors->get('city'))
                                <div class="invalid-feedback d-block">
                                    @foreach ($errors->get('city') as $message)
                                        {{ $message }}
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3 mb-md-0">
                            <label for="country" class="form-label">{{ __('Country') }} <span class="text-danger">*</span></label>
                            <input id="country" name="country" type="text" class="form-control @error('country') is-invalid @enderror"
                                placeholder="e.g. Nepal" value="{{ old('country', $user->country) }}" required autocomplete="country-name">
                            @if ($errors->get('country'))
                                <div class="invalid-feedback d-block">
                                    @foreach ($errors->get('country') as $message)
                                        {{ $message }}
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3">
            <button type="submit" class="btn btn-primary">{{ __('Save Profile') }}</button>
        </div>
    </form>

    @if ($user->isAdmin())
        <div class="card card-warning card-outline mt-4">
            <div class="card-header">
                <h3 class="card-title">{{ __('Administrator Information') }}</h3>
                <p class="card-text small text-muted mb-0 mt-1">
                    {{ __('Account details for your administrator access.') }}
                </p>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">{{ __('Account ID') }}</dt>
                    <dd class="col-sm-8">{{ $user->id }}</dd>

                    <dt class="col-sm-4">{{ __('Role') }}</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-warning text-dark">{{ __('Administrator') }}</span>
                    </dd>

                    <dt class="col-sm-4">{{ __('Email Verification') }}</dt>
                    <dd class="col-sm-8">
                        @if ($user->hasVerifiedEmail())
                            <span class="badge bg-success">{{ __('Verified') }}</span>
                            <span class="text-muted small ms-1">
                                {{ __('on') }} {{ $user->email_verified_at->format('M j, Y') }}
                            </span>
                        @else
                            <span class="badge bg-secondary">{{ __('Unverified') }}</span>
                            <span class="text-muted small ms-1">
                                {{ __('Your email address has not been verified yet.') }}
                            </span>
                        @endif
                    </dd>

                    <dt class="col-sm-4">{{ __('Joined') }}</dt>
                    <dd class="col-sm-8">{{ $user->created_at->format('M j, Y') }}</dd>
                </dl>
            </div>
        </div>
    @endif
</section>

<form id="send-verification" method="post" action="{{ route('verification.send') }}" class="d-none">
    @csrf
</form>