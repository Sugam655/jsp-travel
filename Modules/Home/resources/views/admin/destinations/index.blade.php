@extends('adminlte::page')

@section('title', 'Popular Destinations')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Popular Destinations</h1>
        <a href="{{ route('admin.home.destinations.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus me-1"></i> Add Destination
        </a>
    </div>
@stop

@section('content')
    @include('home::admin.includes.flash-toast')
    @include('home::admin.includes.errors-alert')

    <div class="card">
        <div class="card-body">
            <x-adminlte-datatable id="destinations-table" :heads="$heads" :config="$config" hoverable compressed>
                @forelse ($destinations as $destination)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if ($destination->image_url)
                                    <img src="{{ $destination->image_url }}" alt="{{ $destination->name }}"
                                        style="width: 56px; height: 40px; object-fit: cover; border-radius: 4px;">
                                @endif
                                <span>
                                    <strong>{{ $destination->name }}</strong>
                                    @if ($destination->badge)
                                        <br>
                                        <span class="badge text-bg-warning">{{ $destination->badge }}</span>
                                    @endif
                                </span>
                            </div>
                        </td>
                        <td>{{ $destination->location ?? '&mdash;' }}</td>
                        <td>{{ $destination->price ?? '&mdash;' }}</td>
                        <td>{{ $destination->sort_order }}</td>
                        <td>
                            <form action="{{ route('admin.home.destinations.toggle-active', $destination) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <div class="d-inline-flex align-items-center gap-2">
                                    @if ($destination->is_active)
                                        <span class="badge text-bg-success">Active</span>
                                    @else
                                        <span class="badge text-bg-secondary">Inactive</span>
                                    @endif
                                    <label class="form-check form-switch mb-0">
                                        <input type="checkbox" class="form-check-input" role="switch"
                                            {{ $destination->is_active ? 'checked' : '' }}
                                            title="Toggle status" onchange="this.form.requestSubmit()">
                                        <span class="visually-hidden">Toggle active status</span>
                                    </label>
                                </div>
                            </form>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.home.destinations.edit', $destination) }}"
                                class="btn btn-sm btn-info" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('admin.home.destinations.destroy', $destination) }}"
                                method="POST" class="d-inline"
                                data-confirm
                                data-confirm-title="Delete this destination?"
                                data-confirm-text="This destination will be permanently deleted. This action cannot be undone.">
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
                        <td colspan="6" class="text-center text-muted py-4">
                            No destinations yet. Click "Add Destination" to create the first one.
                        </td>
                    </tr>
                @endforelse
            </x-adminlte-datatable>
        </div>
    </div>

    @include('home::admin.includes.confirm-delete')
@stop