@php
    $isEdit = $vehicle !== null;
@endphp

<form action="{{ $formAction }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method($formMethod)

    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label required">Vehicle Name</label>
                    <input type="text" id="name" name="name"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $vehicle?->name) }}"
                        placeholder="e.g. Toyota Land Cruiser" required>
                    @error('name')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="slug" class="form-label">Slug</label>
                    <input type="text" id="slug" name="slug"
                        class="form-control @error('slug') is-invalid @enderror"
                        value="{{ old('slug', $vehicle?->slug) }}"
                        placeholder="auto-generated from name when empty">
                    <div class="form-text">Leave empty to auto-generate a unique slug from the name.</div>
                    @error('slug')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="brand" class="form-label">Brand / Make</label>
                    <input type="text" id="brand" name="brand"
                        class="form-control @error('brand') is-invalid @enderror"
                        value="{{ old('brand', $vehicle?->brand) }}"
                        placeholder="e.g. Toyota">
                    @error('brand')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="model" class="form-label">Model</label>
                    <input type="text" id="model" name="model"
                        class="form-control @error('model') is-invalid @enderror"
                        value="{{ old('model', $vehicle?->model) }}"
                        placeholder="e.g. Land Cruiser">
                    @error('model')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="vehicle_type" class="form-label required">Vehicle Type</label>
                    <select id="vehicle_type" name="vehicle_type"
                        class="form-select @error('vehicle_type') is-invalid @enderror" required>
                        @foreach (\Modules\Transport\Models\TransportVehicle::TYPE_LABELS as $value => $label)
                            <option value="{{ $value }}"
                                {{ old('vehicle_type', $vehicle?->vehicle_type ?? 'car') === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('vehicle_type')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="destination_id" class="form-label">Destination</label>
                    <select id="destination_id" name="destination_id"
                        class="form-select @error('destination_id') is-invalid @enderror">
                        <option value="">-- None --</option>
                        @foreach ($destinations as $destination)
                            <option value="{{ $destination->id }}"
                                {{ old('destination_id', (string) $vehicle?->destination_id) === (string) $destination->id ? 'selected' : '' }}>
                                {{ $destination->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Link this vehicle to an existing Home destination.</div>
                    @error('destination_id')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" id="location" name="location"
                        class="form-control @error('location') is-invalid @enderror"
                        value="{{ old('location', $vehicle?->location) }}"
                        placeholder="e.g. Dhangadhi">
                    @error('location')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="seating_capacity" class="form-label">Seating Capacity</label>
                    <input type="number" id="seating_capacity" name="seating_capacity" min="1" max="120"
                        class="form-control @error('seating_capacity') is-invalid @enderror"
                        value="{{ old('seating_capacity', $vehicle?->seating_capacity) }}"
                        placeholder="e.g. 7">
                    @error('seating_capacity')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="price" class="form-label required">Price (Rs.)</label>
                    <input type="number" id="price" name="price" min="0" step="0.01"
                        class="form-control @error('price') is-invalid @enderror"
                        value="{{ old('price', $vehicle?->price) }}" placeholder="0.00" required>
                    @error('price')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="price_unit" class="form-label required">Price Unit</label>
                    <select id="price_unit" name="price_unit"
                        class="form-select @error('price_unit') is-invalid @enderror" required>
                        @foreach (\Modules\Transport\Models\TransportVehicle::PRICE_UNIT_LABELS as $value => $label)
                            <option value="{{ $value }}"
                                {{ old('price_unit', $vehicle?->price_unit ?? 'per_day') === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('price_unit')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="year" class="form-label">Model Year</label>
                    <input type="number" id="year" name="year" min="1990" max="2026"
                        class="form-control @error('year') is-invalid @enderror"
                        value="{{ old('year', $vehicle?->year) }}"
                        placeholder="e.g. 2023">
                    @error('year')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="transmission" class="form-label">Transmission</label>
                    <select id="transmission" name="transmission"
                        class="form-select @error('transmission') is-invalid @enderror">
                        <option value="">-- None --</option>
                        <option value="Manual" {{ old('transmission', $vehicle?->transmission) === 'Manual' ? 'selected' : '' }}>Manual</option>
                        <option value="Automatic" {{ old('transmission', $vehicle?->transmission) === 'Automatic' ? 'selected' : '' }}>Automatic</option>
                    </select>
                    @error('transmission')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="sort_order" class="form-label">Sort Order</label>
                    <input type="number" id="sort_order" name="sort_order" min="0"
                        class="form-control @error('sort_order') is-invalid @enderror"
                        value="{{ old('sort_order', $vehicle?->sort_order ?? 0) }}">
                    @error('sort_order')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="image" class="form-label">Vehicle Image</label>
                    <div class="input-group">
                        <input type="file" id="image" name="image"
                            class="form-control @error('image') is-invalid @enderror"
                            accept="image/jpeg,image/png,image/webp"
                            data-filename-label="image-filename-label">
                        <span class="input-group-text" id="image-filename-label">
                            @if ($vehicle?->image_url)
                                {{ basename($vehicle->image) }}
                            @else
                                No file chosen
                            @endif
                        </span>
                    </div>
                    @error('image')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                    @include('home::admin.includes.file-input-helper')
                    @if ($vehicle?->image_url)
                        <div class="mt-2">
                            <img src="{{ $vehicle->image_url }}" alt="{{ $vehicle->name }}"
                                style="max-height: 90px; border-radius: 4px;"
                                class="border">
                        </div>
                    @endif
                </div>

                <div class="col-12">
                    <label for="short_description" class="form-label">Short Description</label>
                    <textarea id="short_description" name="short_description" rows="2"
                        class="form-control @error('short_description') is-invalid @enderror"
                        placeholder="One or two lines shown on the vehicle card...">{{ old('short_description', $vehicle?->short_description) }}</textarea>
                    @error('short_description')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="description" class="form-label">Full Description</label>
                    <textarea id="description" name="description" rows="6"
                        class="form-control @error('description') is-invalid @enderror"
                        placeholder="Full details: comfort, routes, use cases...">{{ old('description', $vehicle?->description) }}</textarea>
                    @error('description')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="features" class="form-label">Features</label>
                    <textarea id="features" name="features" rows="4"
                        class="form-control @error('features') is-invalid @enderror"
                        placeholder="One feature per line:&#10;AC climate control&#10;7 seats&#10;Driver included">{{ old('features', $vehicle?->features) }}</textarea>
                    <div class="form-text">Each line becomes a bullet point on the vehicle detail page.</div>
                    @error('features')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-check form-switch form-switch-lg">
                        <input type="checkbox" class="form-check-input" name="availability" value="1"
                            {{ old('availability', $vehicle?->availability ?? true) ? 'checked' : '' }}>
                        <span class="form-check-label">
                            Available
                            <span class="text-muted small d-block">Whether this vehicle can currently be rented.</span>
                        </span>
                    </label>
                </div>

                <div class="col-md-3">
                    <label class="form-check form-switch form-switch-lg">
                        <input type="checkbox" class="form-check-input" name="featured" value="1"
                            {{ old('featured', $vehicle?->featured) ? 'checked' : '' }}>
                        <span class="form-check-label">
                            Featured Vehicle
                            <span class="text-muted small d-block">Show in the featured rentals section.</span>
                        </span>
                    </label>
                </div>

                <div class="col-md-3">
                    <label class="form-check form-switch form-switch-lg">
                        <input type="checkbox" class="form-check-input" name="is_active" value="1"
                            {{ old('is_active', $vehicle?->is_active ?? true) ? 'checked' : '' }}>
                        <span class="form-check-label">
                            Active
                            <span class="text-muted small d-block">Hidden from the public website when inactive.</span>
                        </span>
                    </label>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('admin.transport.index') }}" class="btn btn-default">
                <i class="fa-solid fa-arrow-left me-1"></i> Back
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-save me-1"></i> {{ $isEdit ? 'Update Vehicle' : 'Create Vehicle' }}
            </button>
        </div>
    </div>
</form>