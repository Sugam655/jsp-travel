@php
    $isEdit = $hotel !== null;
@endphp

<form action="{{ $formAction }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method($formMethod)

    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="title" class="form-label required">Hotel Name</label>
                    <input type="text" id="title" name="title"
                        class="form-control @error('title') is-invalid @enderror"
                        value="{{ old('title', $hotel?->title) }}"
                        placeholder="e.g. Silver Oak Resort" required>
                    @error('title')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="slug" class="form-label">Slug</label>
                    <input type="text" id="slug" name="slug"
                        class="form-control @error('slug') is-invalid @enderror"
                        value="{{ old('slug', $hotel?->slug) }}"
                        placeholder="auto-generated from title when empty">
                    <div class="form-text">Leave empty to auto-generate a unique slug from the name.</div>
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
                                {{ old('destination_id', (string) $hotel?->destination_id) === (string) $destination->id ? 'selected' : '' }}>
                                {{ $destination->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Link this hotel to an existing Home destination.</div>
                    @error('destination_id')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" id="location" name="location"
                        class="form-control @error('location') is-invalid @enderror"
                        value="{{ old('location', $hotel?->location) }}"
                        placeholder="e.g. Dhangadhi">
                    @error('location')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="address" class="form-label">Address</label>
                    <input type="text" id="address" name="address"
                        class="form-control @error('address') is-invalid @enderror"
                        value="{{ old('address', $hotel?->address) }}"
                        placeholder="e.g. Hasuliya Chowk, Dhangadhi">
                    @error('address')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label for="rating" class="form-label">Star Rating</label>
                    <select id="rating" name="rating"
                        class="form-select @error('rating') is-invalid @enderror">
                        <option value="">-- None --</option>
                        @for ($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}"
                                {{ old('rating', $hotel?->rating) == $i ? 'selected' : '' }}>
                                {{ str_repeat('★', $i) }} ({{ $i }}/5)
                            </option>
                        @endfor
                    </select>
                    @error('rating')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label for="price" class="form-label required">Price (Rs./night)</label>
                    <input type="number" id="price" name="price" min="0" step="0.01"
                        class="form-control @error('price') is-invalid @enderror"
                        value="{{ old('price', $hotel?->price) }}" placeholder="0.00" required>
                    @error('price')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="image" class="form-label">Hotel Image</label>
                    <div class="input-group">
                        <input type="file" id="image" name="image"
                            class="form-control @error('image') is-invalid @enderror"
                            accept="image/jpeg,image/png,image/webp"
                            data-filename-label="image-filename-label">
                        <span class="input-group-text" id="image-filename-label">
                            @if ($hotel?->image_url)
                                {{ basename($hotel->image) }}
                            @else
                                No file chosen
                            @endif
                        </span>
                    </div>
                    @error('image')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                    @include('home::admin.includes.file-input-helper')
                    @if ($hotel?->image_url)
                        <div class="mt-2">
                            <img src="{{ $hotel->image_url }}" alt="{{ $hotel->title }}"
                                style="max-height: 90px; border-radius: 4px;"
                                class="border">
                        </div>
                    @endif
                </div>

                <div class="col-12">
                    <label for="short_description" class="form-label">Short Description</label>
                    <textarea id="short_description" name="short_description" rows="2"
                        class="form-control @error('short_description') is-invalid @enderror"
                        placeholder="One or two lines shown on the hotel card...">{{ old('short_description', $hotel?->short_description) }}</textarea>
                    @error('short_description')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="description" class="form-label">Full Description</label>
                    <textarea id="description" name="description" rows="6"
                        class="form-control @error('description') is-invalid @enderror"
                        placeholder="Full details: rooms, facilities, highlights...">{{ old('description', $hotel?->description) }}</textarea>
                    @error('description')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" id="phone" name="phone"
                        class="form-control @error('phone') is-invalid @enderror"
                        value="{{ old('phone', $hotel?->phone) }}"
                        placeholder="e.g. 091-521432">
                    @error('phone')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email"
                        class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email', $hotel?->email) }}"
                        placeholder="reservations@example.com">
                    @error('email')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="website" class="form-label">Website</label>
                    <input type="url" id="website" name="website"
                        class="form-control @error('website') is-invalid @enderror"
                        value="{{ old('website', $hotel?->website) }}"
                        placeholder="https://example.com">
                    @error('website')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label for="sort_order" class="form-label">Sort Order</label>
                    <input type="number" id="sort_order" name="sort_order" min="0"
                        class="form-control @error('sort_order') is-invalid @enderror"
                        value="{{ old('sort_order', $hotel?->sort_order ?? 0) }}">
                    @error('sort_order')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-check form-switch form-switch-lg">
                        <input type="checkbox" class="form-check-input" name="featured" value="1"
                            {{ old('featured', $hotel?->featured) ? 'checked' : '' }}>
                        <span class="form-check-label">
                            Featured Hotel
                            <span class="text-muted small d-block">Feature this hotel on the hotels listing.</span>
                        </span>
                    </label>
                </div>

                <div class="col-md-3">
                    <label class="form-check form-switch form-switch-lg">
                        <input type="checkbox" class="form-check-input" name="is_active" value="1"
                            {{ old('is_active', $hotel?->is_active ?? true) ? 'checked' : '' }}>
                        <span class="form-check-label">
                            Active
                            <span class="text-muted small d-block">Hidden from the public website when inactive.</span>
                        </span>
                    </label>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('admin.hotels.index') }}" class="btn btn-default">
                <i class="fa-solid fa-arrow-left me-1"></i> Back
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-save me-1"></i> {{ $isEdit ? 'Update Hotel' : 'Create Hotel' }}
            </button>
        </div>
    </div>
</form>