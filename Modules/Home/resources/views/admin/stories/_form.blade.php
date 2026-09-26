<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">{{ isset($story) ? 'Edit Story' : 'Add Story' }}</h3>
    </div>

    <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method($formMethod)

        <div class="card-body">
            <div class="form-group">
                <label for="author_name">Author Name <span class="text-danger">*</span></label>
                <input type="text" name="author_name" id="author_name"
                    class="form-control @error('author_name') is-invalid @enderror"
                    value="{{ old('author_name', $story->author_name ?? '') }}" required>
                @error('author_name')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="trip">Trip / Destination</label>
                <input type="text" name="trip" id="trip"
                    class="form-control @error('trip') is-invalid @enderror"
                    value="{{ old('trip', $story->trip ?? '') }}"
                    placeholder="e.g. Everest Base Camp, Nepal">
                @error('trip')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="review">Review <span class="text-danger">*</span></label>
                <textarea name="review" id="review" rows="4"
                    class="form-control @error('review') is-invalid @enderror" required>{{ old('review', $story->review ?? '') }}</textarea>
                @error('review')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="avatar">Avatar</label>
                <input type="file" name="avatar" id="avatar"
                    class="form-control @error('avatar') is-invalid @enderror"
                    data-filename-label="avatar-filename"
                    accept="image/jpeg,image/png,image/webp">
                @error('avatar')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
                <div id="avatar-filename" class="form-text text-info"></div>
                <small class="form-text text-muted">
                    Allowed: jpg, jpeg, png, webp. Max size: 5 MB. Leave empty to keep the current image.
                </small>

                @if (isset($story) && $story->avatar_url)
                    <div class="mt-3">
                        <p class="mb-1 text-muted">Current image:</p>
                        <img src="{{ $story->avatar_url }}" alt="Current avatar"
                            style="width: 96px; height: 96px; border-radius: 50%; object-fit: cover;">
                    </div>
                @endif
            </div>

            <div class="form-group">
                <label for="sort_order">Display Order</label>
                <input type="number" name="sort_order" id="sort_order" min="0"
                    class="form-control @error('sort_order') is-invalid @enderror"
                    value="{{ old('sort_order', $story->sort_order ?? 0) }}">
                @error('sort_order')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
                <small class="form-text text-muted">Lower numbers appear first (grouped 3 per slide).</small>
            </div>

            <div class="form-group">
                <div class="form-check form-switch">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                        class="form-check-input @error('is_active') is-invalid @enderror" role="switch"
                        {{ old('is_active', $story->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                    @error('is_active')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <div class="card-footer d-flex justify-content-between align-items-center">
            <a href="{{ route('admin.home.stories.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Back
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-save me-1"></i> Save Story
            </button>
        </div>
    </form>
</div>

@include('home::admin.includes.file-input-helper')