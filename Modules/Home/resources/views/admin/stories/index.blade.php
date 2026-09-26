@extends('adminlte::page')

@section('title', 'Stories Worth Sharing')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Stories Worth Sharing</h1>
        <a href="{{ route('admin.home.stories.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus me-1"></i> Add Story
        </a>
    </div>
@stop

@section('content')
    @include('home::admin.includes.flash-toast')
    @include('home::admin.includes.errors-alert')

    <div class="card">
        <div class="card-body">
            <x-adminlte-datatable id="stories-table" :heads="$heads" :config="$config" hoverable compressed>
                @forelse ($stories as $story)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if ($story->avatar_url)
                                    <img src="{{ $story->avatar_url }}" alt="{{ $story->author_name }}"
                                        style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                @endif
                                <strong>{{ $story->author_name }}</strong>
                            </div>
                        </td>
                        <td>{{ $story->trip ?? '&mdash;' }}</td>
                        <td data-search="{{ $story->review }}">{{ \Illuminate\Support\Str::limit($story->review, 90) }}</td>
                        <td>{{ $story->sort_order }}</td>
                        <td>
                            <form action="{{ route('admin.home.stories.toggle-active', $story) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <div class="d-inline-flex align-items-center gap-2">
                                    @if ($story->is_active)
                                        <span class="badge text-bg-success">Active</span>
                                    @else
                                        <span class="badge text-bg-secondary">Inactive</span>
                                    @endif
                                    <label class="form-check form-switch mb-0">
                                        <input type="checkbox" class="form-check-input" role="switch"
                                            {{ $story->is_active ? 'checked' : '' }}
                                            title="Toggle status" onchange="this.form.requestSubmit()">
                                        <span class="visually-hidden">Toggle active status</span>
                                    </label>
                                </div>
                            </form>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.home.stories.edit', $story) }}"
                                class="btn btn-sm btn-info" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('admin.home.stories.destroy', $story) }}"
                                method="POST" class="d-inline"
                                data-confirm
                                data-confirm-title="Delete this story?"
                                data-confirm-text="This story will be permanently deleted. This action cannot be undone.">
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
                            No stories yet. Click "Add Story" to create the first one.
                        </td>
                    </tr>
                @endforelse
            </x-adminlte-datatable>
        </div>
    </div>

    @include('home::admin.includes.confirm-delete')
@stop