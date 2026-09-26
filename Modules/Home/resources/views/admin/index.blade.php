@extends('adminlte::page')

@section('title', 'Home Settings')

@section('content_header')
    <h1>Home Settings</h1>
@stop

@section('content')
    @include('home::admin.includes.flash-toast')
    @include('home::admin.includes.errors-alert')

    <div class="row">
        <div class="col-md-8">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Hero Section</h3>
                </div>

                <form action="{{ route('admin.home.hero.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        <div class="form-group">
                            <label for="title">Hero Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="title"
                                class="form-control @error('title') is-invalid @enderror"
                                value="{{ old('title', $hero->title ?? $defaults['title']) }}" required>
                            @error('title')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="description">Hero Description</label>
                            <textarea name="description" id="description" rows="4"
                                class="form-control @error('description') is-invalid @enderror">{{ old('description', $hero->description ?? $defaults['description']) }}</textarea>
                            @error('description')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="background_image">Background Image</label>
                            <input type="file" name="background_image" id="background_image"
                                class="form-control @error('background_image') is-invalid @enderror"
                                data-filename-label="background-image-filename"
                                accept="image/jpeg,image/png,image/webp">
                            @error('background_image')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            <div id="background-image-filename" class="form-text text-info"></div>
                            <small class="form-text text-muted">
                                Allowed: jpg, jpeg, png, webp. Max size: 5 MB. Leave empty to keep the current image.
                            </small>

                            @if ($hero && $hero->background_image_url)
                                <div class="mt-3">
                                    <p class="mb-1 text-muted">Current image:</p>
                                    <img src="{{ $hero->background_image_url }}" alt="Current hero background"
                                        style="max-width: 100%; max-height: 220px; border-radius: 6px; object-fit: cover;">
                                </div>
                            @endif
                        </div>

                        <div class="form-group">
                            <label for="button_text">Button Text</label>
                            <input type="text" name="button_text" id="button_text"
                                class="form-control @error('button_text') is-invalid @enderror"
                                value="{{ old('button_text', $hero->button_text ?? $defaults['button_text']) }}">
                            @error('button_text')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="button_url">Button URL</label>
                            <input type="text" name="button_url" id="button_url"
                                class="form-control @error('button_url') is-invalid @enderror"
                                value="{{ old('button_url', $hero->button_url ?? $defaults['button_url']) }}">
                            @error('button_url')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="is_active" id="is_active" value="1"
                                    class="form-check-input @error('is_active') is-invalid @enderror" role="switch"
                                    {{ old('is_active', $hero->is_active ?? true) ? 'checked' : '' }}>
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
                </form>
            </div>
        </div>
    </div>
@stop

@include('home::admin.includes.file-input-helper')