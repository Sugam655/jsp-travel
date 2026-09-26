@extends('adminlte::page')

@section('title', 'All Vehicles')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>All Vehicles</h1>
        <a href="{{ route('admin.transport.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus me-1"></i> Add Vehicle
        </a>
    </div>
@stop

@section('content')
    @include('home::admin.includes.flash-toast')
    @include('home::admin.includes.errors-alert')

    <div class="card">
        <div class="card-body">
            <x-adminlte-datatable id="transport-table" :heads="$heads" :config="$config" hoverable compressed>
                @forelse ($vehicles as $vehicle)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if ($vehicle->image_url)
                                    <img src="{{ $vehicle->image_url }}" alt="{{ $vehicle->name }}"
                                        style="width: 56px; height: 40px; object-fit: cover; border-radius: 4px;">
                                @endif
                                <span>
                                    <strong>{{ $vehicle->name }}</strong>
                                    @if ($vehicle->year)
                                        <br>
                                        <span class="text-muted small">{{ $vehicle->year }}</span>
                                    @endif
                                </span>
                            </div>
                        </td>
                        <td>
                            <span class="badge text-bg-dark">
                                <i class="fa-solid fa-car fa-fw"></i> {{ $vehicle->type_label }}
                            </span>
                        </td>
                        <td>
                            @if ($vehicle->destination)
                                <span class="badge text-bg-info"><i class="fa-solid fa-map-marker-alt fa-fw"></i> {{ $vehicle->destination->name }}</span>
                            @else
                                {{ $vehicle->location ?? '&mdash;' }}
                            @endif
                        </td>
                        <td>
                            @if ($vehicle->seating_capacity)
                                {{ $vehicle->seating_capacity }} seats
                            @else
                                <span class="text-muted">&mdash;</span>
                            @endif
                        </td>
                        <td data-order="{{ $vehicle->price }}">
                            <strong>{{ $vehicle->price_display }}</strong>
                            @if ($vehicle->price_unit !== 'contact')
                                <span class="text-muted small">/ {{ strtolower(str_replace('_', ' ', $vehicle->price_unit)) }}</span>
                            @endif
                        </td>
                        <td>{{ $vehicle->sort_order }}</td>
                        <td>
                            <form action="{{ route('admin.transport.toggle-active', $vehicle) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <div class="d-inline-flex align-items-center gap-2">
                                    @if ($vehicle->is_active)
                                        <span class="badge text-bg-success">Active</span>
                                    @else
                                        <span class="badge text-bg-secondary">Inactive</span>
                                    @endif
                                    <label class="form-check form-switch mb-0">
                                        <input type="checkbox" class="form-check-input" role="switch"
                                            {{ $vehicle->is_active ? 'checked' : '' }}
                                            title="Toggle status" onchange="this.form.requestSubmit()">
                                        <span class="visually-hidden">Toggle active status</span>
                                    </label>
                                </div>
                            </form>
                        </td>
                        <td>
                            <form action="{{ route('admin.transport.toggle-availability', $vehicle) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <div class="d-inline-flex align-items-center gap-2">
                                    @if ($vehicle->availability)
                                        <span class="badge text-bg-success">Available</span>
                                    @else
                                        <span class="badge text-bg-secondary">Unavailable</span>
                                    @endif
                                    <label class="form-check form-switch mb-0">
                                        <input type="checkbox" class="form-check-input" role="switch"
                                            {{ $vehicle->availability ? 'checked' : '' }}
                                            title="Toggle availability" onchange="this.form.requestSubmit()">
                                        <span class="visually-hidden">Toggle availability</span>
                                    </label>
                                </div>
                            </form>
                        </td>
                        <td>
                            <form action="{{ route('admin.transport.toggle-featured', $vehicle) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <div class="d-inline-flex align-items-center gap-2">
                                    @if ($vehicle->featured)
                                        <span class="badge text-bg-warning">Featured</span>
                                    @else
                                        <span class="badge text-bg-secondary">Not Featured</span>
                                    @endif
                                    <label class="form-check form-switch mb-0">
                                        <input type="checkbox" class="form-check-input" role="switch"
                                            {{ $vehicle->featured ? 'checked' : '' }}
                                            title="Toggle featured status" onchange="this.form.requestSubmit()">
                                        <span class="visually-hidden">Toggle featured status</span>
                                    </label>
                                </div>
                            </form>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.transport.edit', $vehicle) }}"
                                class="btn btn-sm btn-info" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('admin.transport.destroy', $vehicle) }}"
                                method="POST" class="d-inline"
                                data-confirm
                                data-confirm-title="Delete this vehicle?"
                                data-confirm-text="This vehicle will be permanently deleted. This action cannot be undone.">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            No vehicles yet. Click "Add Vehicle" to create the first one.
                        </td>
                    </tr>
                @endforelse
            </x-adminlte-datatable>
        </div>
    </div>

    @include('home::admin.includes.confirm-delete')
@stop