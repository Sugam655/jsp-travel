<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">{{ isset($service) ? 'Edit Service' : 'Add Service' }}</h3>
    </div>

    <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method($formMethod)

        <div class="card-body">
            <div class="form-group">
                <label for="title">Service Title <span class="text-danger">*</span></label>
                <input type="text" name="title" id="title"
                    class="form-control @error('title') is-invalid @enderror"
                    value="{{ old('title', $service->title ?? '') }}" required>
                @error('title')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="subtitle">Subtitle (optional second line)</label>
                <input type="text" name="subtitle" id="subtitle"
                    class="form-control @error('subtitle') is-invalid @enderror"
                    value="{{ old('subtitle', $service->subtitle ?? '') }}"
                    placeholder="Displayed below the title, e.g. with Dashboardstay">
                @error('subtitle')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="image">Image</label>
                <input type="file" name="image" id="image"
                    class="form-control @error('image') is-invalid @enderror"
                    data-filename-label="image-filename"
                    accept="image/jpeg,image/png,image/webp">
                @error('image')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
                <div id="image-filename" class="form-text text-info"></div>
                <small class="form-text text-muted">
                    Allowed: jpg, jpeg, png, webp. Max size: 5 MB. Leave empty to keep the current image.
                </small>

                @if (isset($service) && $service->image_url)
                    <div class="mt-3">
                        <p class="mb-1 text-muted">Current image:</p>
                        <img src="{{ $service->image_url }}" alt="Current service image"
                            style="max-width: 100%; max-height: 220px; border-radius: 6px; object-fit: cover;">
                    </div>
                @endif
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="button_text">Button Text</label>
                        <input type="text" name="button_text" id="button_text"
                            class="form-control @error('button_text') is-invalid @enderror"
                            value="{{ old('button_text', $service->button_text ?? 'Learn More') }}">
                        @error('button_text')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="button_url">Button URL</label>
                        <input type="text" name="button_url" id="button_url"
                            class="form-control @error('button_url') is-invalid @enderror"
                            value="{{ old('button_url', $service->button_url ?? '') }}"
                            placeholder="e.g. destinations.html or https://...">
                        @error('button_url')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="sort_order">Display Order</label>
                <input type="number" name="sort_order" id="sort_order" min="0"
                    class="form-control @error('sort_order') is-invalid @enderror"
                    value="{{ old('sort_order', $service->sort_order ?? 0) }}">
                @error('sort_order')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
                <small class="form-text text-muted">Lower numbers appear first on the homepage.</small>
            </div>

            <div class="form-group">
                <div class="form-check form-switch">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                        class="form-check-input @error('is_active') is-invalid @enderror" role="switch"
                        {{ old('is_active', $service->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                    @error('is_active')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <div class="card-footer d-flex justify-content-between align-items-center">
            <a href="{{ route('admin.home.services.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Back
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-save me-1"></i> Save Service
            </button>
        </div>
    </form>
</div>

@include('home::admin.includes.file-input-helper')