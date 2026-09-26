@extends('adminlte::page')

@section('title', 'All Tours')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>All Tours</h1>
        <a href="{{ route('admin.tours.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus me-1"></i> Add Tour
        </a>
    </div>
@stop

@section('content')
    @include('home::admin.includes.flash-toast')
    @include('home::admin.includes.errors-alert')

    <div class="card">
        <div class="card-body">
            <x-adminlte-datatable id="tours-table" :heads="$heads" :config="$config" hoverable compressed>
                @forelse ($tours as $tour)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if ($tour->image_url)
                                    <img src="{{ $tour->image_url }}" alt="{{ $tour->title }}"
                                        style="width: 56px; height: 40px; object-fit: cover; border-radius: 4px;">
                                @endif
                                <span>
                                    <strong>{{ $tour->title }}</strong>
                                    @if ($tour->featured)
                                        <br>
                                        <span class="badge text-bg-warning" title="Featured tour">
                                            <i class="fa-solid fa-star fa-fw"></i> Featured
                                        </span>
                                    @endif
                                </span>
                            </div>
                        </td>
                        <td>
                            @if ($tour->destination)
                                <span class="badge text-bg-info"><i class="fa-solid fa-map-marker-alt fa-fw"></i> {{ $tour->destination->name }}</span>
                            @else
                                {{ $tour->location ?? '&mdash;' }}
                            @endif
                        </td>
                        <td>
                            @if ($tour->duration)
                                <i class="fa-regular fa-clock me-1"></i>{{ $tour->duration }}
                            @else
                                <span class="text-muted">&mdash;</span>
                            @endif
                        </td>
                        <td data-order="{{ $tour->price }}">
                            <strong>Rs.{{ number_format($tour->price) }}</strong>
                            @if ($tour->old_price)
                                <br>
                                <span class="text-muted text-decoration-line-through">Rs.{{ number_format($tour->old_price) }}</span>
                            @endif
                        </td>
                        <td>{{ $tour->sort_order }}</td>
                        <td>
                            <form action="{{ route('admin.tours.toggle-active', $tour) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <div class="d-inline-flex align-items-center gap-2">
                                    @if ($tour->is_active)
                                        <span class="badge text-bg-success">Active</span>
                                    @else
                                        <span class="badge text-bg-secondary">Inactive</span>
                                    @endif
                                    <label class="form-check form-switch mb-0">
                                        <input type="checkbox" class="form-check-input" role="switch"
                                            {{ $tour->is_active ? 'checked' : '' }}
                                            title="Toggle status" onchange="this.form.requestSubmit()">
                                        <span class="visually-hidden">Toggle active status</span>
                                    </label>
                                </div>
                            </form>
                        </td>
                        <td>
                            <form action="{{ route('admin.tours.toggle-featured', $tour) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <div class="d-inline-flex align-items-center gap-2">
                                    @if ($tour->featured)
                                        <span class="badge text-bg-warning">Featured</span>
                                    @else
                                        <span class="badge text-bg-secondary">Not Featured</span>
                                    @endif
                                    <label class="form-check form-switch mb-0">
                                        <input type="checkbox" class="form-check-input" role="switch"
                                            {{ $tour->featured ? 'checked' : '' }}
                                            title="Toggle featured status" onchange="this.form.requestSubmit()">
                                        <span class="visually-hidden">Toggle featured status</span>
                                    </label>
                                </div>
                            </form>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.tours.edit', $tour) }}"
                                class="btn btn-sm btn-info" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('admin.tours.destroy', $tour) }}"
                                method="POST" class="d-inline"
                                data-confirm
                                data-confirm-title="Delete this tour?"
                                data-confirm-text="This tour will be permanently deleted. This action cannot be undone.">
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
                            No tours yet. Click "Add Tour" to create the first one.
                        </td>
                    </tr>
                @endforelse
            </x-adminlte-datatable>
        </div>
    </div>

    @include('home::admin.includes.confirm-delete')
@stop