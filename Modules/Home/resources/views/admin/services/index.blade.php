@extends('adminlte::page')

@section('title', 'Our Services')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Our Services</h1>
        <a href="{{ route('admin.home.services.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus me-1"></i> Add Service
        </a>
    </div>
@stop

@section('content')
    @include('home::admin.includes.flash-toast')
    @include('home::admin.includes.errors-alert')

    <div class="card">
        <div class="card-body">
            <x-adminlte-datatable id="services-table" :heads="$heads" :config="$config" hoverable compressed>
                @forelse ($services as $service)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if ($service->image_url)
                                    <img src="{{ $service->image_url }}" alt="{{ $service->title }}"
                                        style="width: 56px; height: 40px; object-fit: cover; border-radius: 4px;">
                                @endif
                                <span>
                                    <strong>{{ $service->title }}</strong>
                                    @if ($service->subtitle)
                                        <br>
                                        <span class="text-muted">{{ $service->subtitle }}</span>
                                    @endif
                                </span>
                            </div>
                        </td>
                        <td>
                            @if ($service->button_text)
                                {{ $service->button_text }}
                                <br>
                                <code class="text-muted">{{ $service->button_url ?? '&mdash;' }}</code>
                            @else
                                <span class="text-muted">&mdash;</span>
                            @endif
                        </td>
                        <td>{{ $service->sort_order }}</td>
                        <td>
                            <form action="{{ route('admin.home.services.toggle-active', $service) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <div class="d-inline-flex align-items-center gap-2">
                                    @if ($service->is_active)
                                        <span class="badge text-bg-success">Active</span>
                                    @else
                                        <span class="badge text-bg-secondary">Inactive</span>
                                    @endif
                                    <label class="form-check form-switch mb-0">
                                        <input type="checkbox" class="form-check-input" role="switch"
                                            {{ $service->is_active ? 'checked' : '' }}
                                            title="Toggle status" onchange="this.form.requestSubmit()">
                                        <span class="visually-hidden">Toggle active status</span>
                                    </label>
                                </div>
                            </form>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.home.services.edit', $service) }}"
                                class="btn btn-sm btn-info" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('admin.home.services.destroy', $service) }}"
                                method="POST" class="d-inline"
                                data-confirm
                                data-confirm-title="Delete this service?"
                                data-confirm-text="This service will be permanently deleted. This action cannot be undone.">
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
                        <td colspan="5" class="text-center text-muted py-4">
                            No services yet. Click "Add Service" to create the first one.
                        </td>
                    </tr>
                @endforelse
            </x-adminlte-datatable>
        </div>
    </div>

    @include('home::admin.includes.confirm-delete')
@stop