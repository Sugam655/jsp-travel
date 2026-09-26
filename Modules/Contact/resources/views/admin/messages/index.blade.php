@extends('adminlte::page')

@section('title', 'Contact Messages')

@section('content_header')
    <h1>Contact Messages</h1>
@stop

@section('content')
    @include('home::admin.includes.flash-toast')
    @include('home::admin.includes.errors-alert')

    <div class="card">
        <div class="card-body">
            <x-adminlte-datatable id="messages-table" :heads="$heads" :config="$config" hoverable compressed>
                @forelse ($messages as $message)
                    <tr>
                        <td>{{ $message->id }}</td>
                        <td>
                            <strong>{{ $message->name }}</strong>
                            @if ($message->status === 'new')
                                <br>
                                <span class="badge text-bg-primary">Unread</span>
                            @endif
                        </td>
                        <td>
                            <a href="mailto:{{ $message->email }}">{{ $message->email }}</a>
                        </td>
                        <td>{{ $message->phone ?? '&mdash;' }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($message->subject, 40) }}</td>
                        <td>
                            <span class="badge {{ $message->status_color }}">{{ $message->status_label }}</span>
                        </td>
                        <td data-order="{{ $message->created_at->timestamp }}">{{ $message->created_at->format('M d, Y H:i') }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.contact.messages.show', $message) }}"
                                class="btn btn-sm btn-info" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <form action="{{ route('admin.contact.messages.destroy', $message) }}"
                                method="POST" class="d-inline"
                                data-confirm
                                data-confirm-title="Delete this message?"
                                data-confirm-text="This message will be permanently deleted. This action cannot be undone.">
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
                            No messages received yet.
                        </td>
                    </tr>
                @endforelse
            </x-adminlte-datatable>
        </div>
    </div>

    @include('home::admin.includes.confirm-delete')
@stop