@php
    $isEdit = $tour !== null;
@endphp

<form action="{{ $formAction }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method($formMethod)

    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="title" class="form-label required">Title</label>
                    <input type="text" id="title" name="title"
                        class="form-control @error('title') is-invalid @enderror"
                        value="{{ old('title', $tour?->title) }}"
                        placeholder="e.g. Khaptad National Park" required>
                    @error('title')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="slug" class="form-label">Slug</label>
                    <input type="text" id="slug" name="slug"
                        class="form-control @error('slug') is-invalid @enderror"
                        value="{{ old('slug', $tour?->slug) }}"
                        placeholder="auto-generated from title when empty">
                    <div class="form-text">Leave empty to auto-generate a unique slug from the title.</div>
                    @error('slug')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="destination_id" class="form-label">Destination</label>
                    <select id="destination_id" name="destination_id"
                        class="form-select @error('destination_id') is-invalid @enderror">
                        <option value="">-- None --</option>
                        @foreach ($destinations as $destination)
                            <option value="{{ $destination->id }}"
                                {{ old('destination_id', (string) $tour?->destination_id) === (string) $destination->id ? 'selected' : '' }}>
                                {{ $destination->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Link this tour to an existing Home destination.</div>
                    @error('destination_id')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" id="location" name="location"
                        class="form-control @error('location') is-invalid @enderror"
                        value="{{ old('location', $tour?->location) }}"
                        placeholder="e.g. Far Western Nepal">
                    @error('location')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="duration" class="form-label required">Duration</label>
                    <input type="text" id="duration" name="duration"
                        class="form-control @error('duration') is-invalid @enderror"
                        value="{{ old('duration', $tour?->duration) }}"
                        placeholder="e.g. 5 Days / 4 Nights" required>
                    @error('duration')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label for="duration_days" class="form-label">Duration (days)</label>
                    <input type="number" id="duration_days" name="duration_days" min="1"
                        class="form-control @error('duration_days') is-invalid @enderror"
                        value="{{ old('duration_days', $tour?->duration_days) }}"
                        placeholder="e.g. 5">
                    <div class="form-text">Numeric length used for booking date validation.</div>
                    @error('duration_days')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label for="capacity" class="form-label">Capacity (seats)</label>
                    <input type="number" id="capacity" name="capacity" min="1"
                        class="form-control @error('capacity') is-invalid @enderror"
                        value="{{ old('capacity', $tour?->capacity) }}"
                        placeholder="optional">
                    <div class="form-text">Leave empty for unlimited seats.</div>
                    @error('capacity')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label for="price" class="form-label required">Price (Rs.)</label>
                    <input type="number" id="price" name="price" min="0" step="0.01"
                        class="form-control @error('price') is-invalid @enderror"
                        value="{{ old('price', $tour?->price) }}" placeholder="0.00" required>
                    @error('price')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label for="old_price" class="form-label">Old Price (Rs.)</label>
                    <input type="number" id="old_price" name="old_price" min="0" step="0.01"
                        class="form-control @error('old_price') is-invalid @enderror"
                        value="{{ old('old_price', $tour?->old_price) }}" placeholder="optional">
                    <div class="form-text">Used to show the discount badge.</div>
                    @error('old_price')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label for="sort_order" class="form-label">Sort Order</label>
                    <input type="number" id="sort_order" name="sort_order" min="0"
                        class="form-control @error('sort_order') is-invalid @enderror"
                        value="{{ old('sort_order', $tour?->sort_order ?? 0) }}">
                    @error('sort_order')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="image" class="form-label">Tour Image</label>
                    <div class="input-group">
                        <input type="file" id="image" name="image"
                            class="form-control @error('image') is-invalid @enderror"
                            accept="image/jpeg,image/png,image/webp"
                            data-filename-label="image-filename-label">
                        <span class="input-group-text" id="image-filename-label">
                            @if ($tour?->image_url)
                                {{ basename($tour->image) }}
                            @else
                                No file chosen
                            @endif
                        </span>
                    </div>
                    @error('image')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                    @include('home::admin.includes.file-input-helper')
                    @if ($tour?->image_url)
                        <div class="mt-2">
                            <img src="{{ $tour->image_url }}" alt="{{ $tour->title }}"
                                style="max-height: 90px; border-radius: 4px;"
                                class="border">
                        </div>
                    @endif
                </div>

                <div class="col-12">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" name="description" rows="6"
                        class="form-control @error('description') is-invalid @enderror"
                        placeholder="Describe the tour, highlights and what is included...">{{ old('description', $tour?->description) }}</textarea>
                    @error('description')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-check form-switch form-switch-lg">
                        <input type="checkbox" class="form-check-input" name="featured" value="1"
                            {{ old('featured', $tour?->featured) ? 'checked' : '' }}>
                        <span class="form-check-label">
                            Featured Tour
                            <span class="text-muted small d-block">Feature this package on the tours listing.</span>
                        </span>
                    </label>
                </div>

                <div class="col-md-6">
                    <label class="form-check form-switch form-switch-lg">
                        <input type="checkbox" class="form-check-input" name="is_active" value="1"
                            {{ old('is_active', $tour?->is_active ?? true) ? 'checked' : '' }}>
                        <span class="form-check-label">
                            Active
                            <span class="text-muted small d-block">Hidden from the public website when inactive.</span>
                        </span>
                    </label>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('admin.tours.index') }}" class="btn btn-default">
                <i class="fa-solid fa-arrow-left me-1"></i> Back
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-save me-1"></i> {{ $isEdit ? 'Update Tour' : 'Create Tour' }}
            </button>
        </div>
    </div>
</form>

