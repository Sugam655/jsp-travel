@extends('adminlte::page')

@section('title', 'All Hotels')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>All Hotels</h1>
        <a href="{{ route('admin.hotels.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus me-1"></i> Add Hotel
        </a>
    </div>
@stop

@section('content')
    @include('home::admin.includes.flash-toast')
    @include('home::admin.includes.errors-alert')

    <div class="card">
        <div class="card-body">
            <x-adminlte-datatable id="hotels-table" :heads="$heads" :config="$config" hoverable compressed>
                @forelse ($hotels as $hotel)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if ($hotel->image_url)
                                    <img src="{{ $hotel->image_url }}" alt="{{ $hotel->title }}"
                                        style="width: 56px; height: 40px; object-fit: cover; border-radius: 4px;">
                                @endif
                                <span>
                                    <strong>{{ $hotel->title }}</strong>
                                    @if ($hotel->featured)
                                        <br>
                                        <span class="badge text-bg-warning" title="Featured hotel">
                                            <i class="fa-solid fa-star fa-fw"></i> Featured
                                        </span>
                                    @endif
                                </span>
                            </div>
                        </td>
                        <td>
                            @if ($hotel->destination)
                                <span class="badge text-bg-info"><i class="fa-solid fa-map-marker-alt fa-fw"></i> {{ $hotel->destination->name }}</span>
                            @else
                                {{ $hotel->location ?? '&mdash;' }}
                            @endif
                        </td>
                        <td>
                            @if ($hotel->rating)
                                <span class="text-warning">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i class="fa-solid fa-star {{ $i <= $hotel->rating ? '' : 'text-secondary' }}"></i>
                                    @endfor
                                    <span class="text-muted small">{{ $hotel->rating }}/5</span>
                                </span>
                            @else
                                <span class="text-muted">&mdash;</span>
                            @endif
                        </td>
                        <td data-order="{{ $hotel->price }}">
                            <strong>Rs.{{ number_format($hotel->price) }}</strong>
                            <span class="text-muted small">/ night</span>
                        </td>
                        <td>{{ $hotel->sort_order }}</td>
                        <td>
                            <form action="{{ route('admin.hotels.toggle-active', $hotel) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <div class="d-inline-flex align-items-center gap-2">
                                    @if ($hotel->is_active)
                                        <span class="badge text-bg-success">Active</span>
                                    @else
                                        <span class="badge text-bg-secondary">Inactive</span>
                                    @endif
                                    <label class="form-check form-switch mb-0">
                                        <input type="checkbox" class="form-check-input" role="switch"
                                            {{ $hotel->is_active ? 'checked' : '' }}
                                            title="Toggle status" onchange="this.form.requestSubmit()">
                                        <span class="visually-hidden">Toggle active status</span>
                                    </label>
                                </div>
                            </form>
                        </td>
                        <td>
                            <form action="{{ route('admin.hotels.toggle-featured', $hotel) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <div class="d-inline-flex align-items-center gap-2">
                                    @if ($hotel->featured)
                                        <span class="badge text-bg-warning">Featured</span>
                                    @else
                                        <span class="badge text-bg-secondary">Not Featured</span>
                                    @endif
                                    <label class="form-check form-switch mb-0">
                                        <input type="checkbox" class="form-check-input" role="switch"
                                            {{ $hotel->featured ? 'checked' : '' }}
                                            title="Toggle featured status" onchange="this.form.requestSubmit()">
                                        <span class="visually-hidden">Toggle featured status</span>
                                    </label>
                                </div>
                            </form>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.hotels.edit', $hotel) }}"
                                class="btn btn-sm btn-info" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('admin.hotels.destroy', $hotel) }}"
                                method="POST" class="d-inline"
                                data-confirm
                                data-confirm-title="Delete this hotel?"
                                data-confirm-text="This hotel will be permanently deleted. This action cannot be undone.">
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
                        <td colspan="8" class="text-center text-muted py-4">
                            No hotels yet. Click "Add Hotel" to create the first one.
                        </td>
                    </tr>
                @endforelse
            </x-adminlte-datatable>
        </div>
    </div>

    @include('home::admin.includes.confirm-delete')
@stop