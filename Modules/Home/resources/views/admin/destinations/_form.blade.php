<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">{{ isset($destination) ? 'Edit Destination' : 'Add Destination' }}</h3>
    </div>

    <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method($formMethod)

        <div class="card-body">
            <div class="form-group">
                <label for="name">Destination Name <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name"
                    class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name', $destination->name ?? '') }}" required>
                @error('name')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="badge">Badge</label>
                <input type="text" name="badge" id="badge"
                    class="form-control @error('badge') is-invalid @enderror"
                    value="{{ old('badge', $destination->badge ?? '') }}"
                    placeholder="e.g. Popular, Trending">
                @error('badge')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" name="location" id="location"
                    class="form-control @error('location') is-invalid @enderror"
                    value="{{ old('location', $destination->location ?? '') }}">
                @error('location')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="price">Price</label>
                <input type="text" name="price" id="price"
                    class="form-control @error('price') is-invalid @enderror"
                    value="{{ old('price', $destination->price ?? '') }}"
                    placeholder="e.g. Rs.699">
                @error('price')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="latitude">Latitude</label>
                        <input type="text" name="latitude" id="latitude"
                            class="form-control @error('latitude') is-invalid @enderror"
                            value="{{ old('latitude', $destination->latitude ?? '') }}"
                            placeholder="e.g. 29.2755">
                        @error('latitude')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="longitude">Longitude</label>
                        <input type="text" name="longitude" id="longitude"
                            class="form-control @error('longitude') is-invalid @enderror"
                            value="{{ old('longitude', $destination->longitude ?? '') }}"
                            placeholder="e.g. 81.1440">
                        @error('longitude')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
            <p class="text-muted small">
                Latitude and longitude pin this destination on the dashboard map. Leave empty to use the
                built-in reference coordinates automatically.
            </p>

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

                @if (isset($destination) && $destination->image_url)
                    <div class="mt-3">
                        <p class="mb-1 text-muted">Current image:</p>
                        <img src="{{ $destination->image_url }}" alt="Current destination image"
                            style="max-width: 100%; max-height: 220px; border-radius: 6px; object-fit: cover;">
                    </div>
                @endif
            </div>

            <div class="form-group">
                <label for="sort_order">Display Order</label>
                <input type="number" name="sort_order" id="sort_order" min="0"
                    class="form-control @error('sort_order') is-invalid @enderror"
                    value="{{ old('sort_order', $destination->sort_order ?? 0) }}">
                @error('sort_order')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
                <small class="form-text text-muted">Lower numbers appear first on the homepage.</small>
            </div>

            <div class="form-group">
                <div class="form-check form-switch">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                        class="form-check-input @error('is_active') is-invalid @enderror" role="switch"
                        {{ old('is_active', $destination->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                    @error('is_active')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <div class="card-footer d-flex justify-content-between align-items-center">
            <a href="{{ route('admin.home.destinations.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Back
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-save me-1"></i> Save Destination
            </button>
        </div>
    </form>
</div>

@include('home::admin.includes.file-input-helper')