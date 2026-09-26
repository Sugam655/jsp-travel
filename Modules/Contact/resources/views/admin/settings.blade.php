@extends('adminlte::page')

@section('title', 'Contact Settings')

@section('content_header')
    <h1>Contact Settings</h1>
@stop

@section('content')
    @include('home::admin.includes.flash-toast')
    @include('home::admin.includes.errors-alert')

    <form action="{{ route('admin.contact.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-md-8">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Page & Company</h3>
                    </div>

                    <div class="card-body">
                        <div class="form-group">
                            <label for="company_name">Company Name <span class="text-danger">*</span></label>
                            <input type="text" name="company_name" id="company_name"
                                class="form-control @error('company_name') is-invalid @enderror"
                                value="{{ old('company_name', $settings->company_name ?? $defaults['company_name']) }}" required>
                            @error('company_name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="page_title">Page Title <span class="text-danger">*</span></label>
                            <input type="text" name="page_title" id="page_title"
                                class="form-control @error('page_title') is-invalid @enderror"
                                value="{{ old('page_title', $settings->page_title ?? $defaults['page_title']) }}" required>
                            @error('page_title')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="page_subtitle">Page Subtitle</label>
                            <textarea name="page_subtitle" id="page_subtitle" rows="3"
                                class="form-control @error('page_subtitle') is-invalid @enderror">{{ old('page_subtitle', $settings->page_subtitle ?? $defaults['page_subtitle']) }}</textarea>
                            @error('page_subtitle')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="manager_name">Manager Name</label>
                            <input type="text" name="manager_name" id="manager_name"
                                class="form-control @error('manager_name') is-invalid @enderror"
                                value="{{ old('manager_name', $settings->manager_name ?? $defaults['manager_name']) }}">
                            @error('manager_name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="md_name">Managing Director Name</label>
                            <input type="text" name="md_name" id="md_name"
                                class="form-control @error('md_name') is-invalid @enderror"
                                value="{{ old('md_name', $settings->md_name ?? $defaults['md_name']) }}">
                            @error('md_name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="opening_hours">Opening Hours</label>
                            <input type="text" name="opening_hours" id="opening_hours"
                                class="form-control @error('opening_hours') is-invalid @enderror"
                                value="{{ old('opening_hours', $settings->opening_hours ?? $defaults['opening_hours']) }}">
                            @error('opening_hours')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Contact Image</h3>
                    </div>

                    <div class="card-body">
                        <div class="form-group">
                            <label for="contact_image">Contact Page Image</label>
                            <input type="file" name="contact_image" id="contact_image"
                                class="form-control @error('contact_image') is-invalid @enderror"
                                data-filename-label="contact-image-filename"
                                accept="image/jpeg,image/png,image/webp">
                            @error('contact_image')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            <div id="contact-image-filename" class="form-text text-info"></div>
                            <small class="form-text text-muted">
                                Allowed: jpg, jpeg, png, webp. Max size: 5 MB. Leave empty to keep the current image.
                            </small>

                            @php
                                $currentImage = $settings?->contact_image_url ?? $defaults['contact_image'];
                            @endphp
                            @if ($currentImage)
                                <div class="mt-3">
                                    <p class="mb-1 text-muted">Current image:</p>
                                    <img src="{{ $currentImage }}" alt="Current contact image"
                                        style="max-width: 100%; border-radius: 6px; object-fit: cover;">
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Address & Contact Numbers</h3>
                    </div>

                    <div class="card-body">
                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="text" name="address" id="address"
                                class="form-control @error('address') is-invalid @enderror"
                                value="{{ old('address', $settings->address ?? $defaults['address']) }}">
                            @error('address')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="text" name="phone" id="phone"
                                class="form-control @error('phone') is-invalid @enderror"
                                value="{{ old('phone', $settings->phone ?? $defaults['phone']) }}">
                            @error('phone')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="alternate_phone">Alternate Phone</label>
                            <input type="text" name="alternate_phone" id="alternate_phone"
                                class="form-control @error('alternate_phone') is-invalid @enderror"
                                value="{{ old('alternate_phone', $settings->alternate_phone ?? $defaults['alternate_phone']) }}">
                            @error('alternate_phone')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="landline">Landline</label>
                            <input type="text" name="landline" id="landline"
                                class="form-control @error('landline') is-invalid @enderror"
                                value="{{ old('landline', $settings->landline ?? $defaults['landline']) }}">
                            @error('landline')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="whatsapp_number">WhatsApp Number</label>
                            <input type="text" name="whatsapp_number" id="whatsapp_number"
                                class="form-control @error('whatsapp_number') is-invalid @enderror"
                                value="{{ old('whatsapp_number', $settings->whatsapp_number ?? $defaults['whatsapp_number']) }}">
                            @error('whatsapp_number')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="viber">Viber</label>
                            <input type="text" name="viber" id="viber"
                                class="form-control @error('viber') is-invalid @enderror"
                                value="{{ old('viber', $settings->viber ?? $defaults['viber']) }}">
                            @error('viber')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Emails & Social Links</h3>
                    </div>

                    <div class="card-body">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email"
                                class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email', $settings->email ?? $defaults['email']) }}">
                            @error('email')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="alternate_email">Alternate Email</label>
                            <input type="email" name="alternate_email" id="alternate_email"
                                class="form-control @error('alternate_email') is-invalid @enderror"
                                value="{{ old('alternate_email', $settings->alternate_email ?? $defaults['alternate_email']) }}">
                            @error('alternate_email')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <hr>

                        <div class="form-group">
                            <label for="facebook_url">Facebook URL</label>
                            <input type="url" name="facebook_url" id="facebook_url"
                                class="form-control @error('facebook_url') is-invalid @enderror"
                                value="{{ old('facebook_url', $settings->facebook_url ?? $defaults['facebook_url']) }}">
                            @error('facebook_url')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="instagram_url">Instagram URL</label>
                            <input type="url" name="instagram_url" id="instagram_url"
                                class="form-control @error('instagram_url') is-invalid @enderror"
                                value="{{ old('instagram_url', $settings->instagram_url ?? $defaults['instagram_url']) }}">
                            @error('instagram_url')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="youtube_url">YouTube URL</label>
                            <input type="url" name="youtube_url" id="youtube_url"
                                class="form-control @error('youtube_url') is-invalid @enderror"
                                value="{{ old('youtube_url', $settings->youtube_url ?? $defaults['youtube_url']) }}">
                            @error('youtube_url')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="twitter_url">Twitter / X URL</label>
                            <input type="url" name="twitter_url" id="twitter_url"
                                class="form-control @error('twitter_url') is-invalid @enderror"
                                value="{{ old('twitter_url', $settings->twitter_url ?? $defaults['twitter_url']) }}">
                            @error('twitter_url')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Map</h3>
                    </div>

                    <div class="card-body">
                        <div class="form-group">
                            <label for="map_url">Map Embed URL</label>
                            <input type="url" name="map_url" id="map_url"
                                class="form-control @error('map_url') is-invalid @enderror"
                                value="{{ old('map_url', $settings->map_url ?? $defaults['map_url']) }}"
                                placeholder="https://www.google.com/maps?q=Attariya,Kailali&output=embed">
                            @error('map_url')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            <small class="form-text text-muted">
                                Use a Google Maps embed URL (e.g. ending with &output=embed) to show a map on the contact page. Leave empty to hide the map.
                            </small>
                        </div>

                        <div class="form-group mb-0">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="is_active" id="is_active" value="1"
                                    class="form-check-input @error('is_active') is-invalid @enderror" role="switch"
                                    {{ old('is_active', $settings->is_active ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Active</label>
                                @error('is_active')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save me-1"></i> Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@stop

@include('home::admin.includes.file-input-helper')