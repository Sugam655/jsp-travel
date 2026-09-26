@extends('adminlte::page')

@section('title', 'User Management')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>User Management</h1>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <p class="card-text small text-muted mb-0">
                {{ __('Reviewing an account is read-only. Each user edits their own details from their own My Profile page.') }}
            </p>
        </div>
        <div class="card-body">
            <x-adminlte-datatable id="users-table" :heads="$heads" :config="$config" hoverable compressed>
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->phone }}</td>
                        <td>{{ $user->address }}</td>
                        <td>{{ $user->city }}</td>
                        <td>{{ $user->country }}</td>
                        <td>{{ $bookingCounts[$user->id] ?? 0 }}</td>
                        <td>
                            @if ($user->isAdmin())
                                <span class="badge text-bg-warning"><i class="fa-solid fa-user-shield fa-fw"></i> Admin</span>
                            @else
                                <span class="badge text-bg-info"><i class="fa-solid fa-user fa-fw"></i> User</span>
                            @endif
                        </td>
                        <td>
                            @if ($user->email_verified_at)
                                <span class="badge text-bg-success">Verified</span>
                            @else
                                <span class="badge text-bg-secondary">Unverified</span>
                            @endif
                        </td>
                        <td>{{ $user->created_at?->format('M d, Y h:i A') }}</td>
                        <td>
                            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fa-solid fa-eye me-1" aria-hidden="true"></i> View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="text-center text-muted py-4">
                            No registered users yet.
                        </td>
                    </tr>
                @endforelse
            </x-adminlte-datatable>
        </div>
    </div>
@stop