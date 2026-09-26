@extends('adminlte::page')

@section('title', 'Booking Change Requests')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Change Requests</h1>
        <a href="{{ route('admin.bookings.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Bookings
        </a>
    </div>
@stop

@section('content')
    @include('home::admin.includes.flash-toast')
    @include('home::admin.includes.errors-alert')

    <div class="mb-3">
        <div class="btn-group btn-group-sm" role="group">
            <a href="{{ route('admin.bookings.change-requests.index') }}"
                class="btn {{ $status ? 'btn-default' : 'btn-primary' }}">All</a>
            @foreach ($statuses as $statusKey)
                <a href="{{ route('admin.bookings.change-requests.index', ['status' => $statusKey]) }}"
                    class="btn {{ $status === $statusKey ? 'btn-primary' : 'btn-default' }}">
                    {{ $statusLabels[$statusKey] }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Booking</th>
                        <th>Type</th>
                        <th>Requested</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($changeRequests as $changeRequest)
                        <tr id="change-request-{{ $changeRequest->id }}">
                            <td>
                                <a href="{{ route('admin.bookings.show', $changeRequest->booking) }}">
                                    {{ $changeRequest->booking->booking_reference }}
                                </a>
                                <br>
                                <small class="text-muted">{{ $changeRequest->booking->service_title }}</small>
                            </td>
                            <td>{{ $changeRequest->type_label }}</td>
                            <td>
                                <small>
                                    @forelse (($changeRequest->requested ?? []) as $key => $value)
                                        <span class="badge text-bg-light border d-block mb-1">{{ $key }}: {{ $value }}</span>
                                    @empty
                                        <span class="text-muted">&mdash;</span>
                                    @endforelse
                                    @if ($changeRequest->reason)
                                        <span class="text-muted d-block">{{ $changeRequest->reason }}</span>
                                    @endif
                                </small>
                            </td>
                            <td>{!! $changeRequest->response_note ? e($changeRequest->response_note) : '&mdash;' !!}</td>
                            <td>
                                <span class="badge {{ $changeRequest->status_color }}">{{ $changeRequest->status_label }}</span>
                            </td>
                            <td class="text-end">
                                @if ($changeRequest->status === 'pending')
                                    <form action="{{ route('admin.bookings.change-requests.approve', $changeRequest) }}"
                                        method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="fa-solid fa-check me-1"></i>Approve
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-danger"
                                        data-bs-toggle="modal" data-bs-target="#cr-reject-{{ $changeRequest->id }}">
                                        <i class="fa-solid fa-xmark me-1"></i>Reject
                                    </button>
                                    <div class="modal fade" id="cr-reject-{{ $changeRequest->id }}" tabindex="-1">
                                        <form action="{{ route('admin.bookings.change-requests.reject', $changeRequest) }}"
                                            method="POST" class="modal-dialog">
                                            @csrf
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Reject change request</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body text-start">
                                                    <div class="form-group">
                                                        <label>Response note (optional)</label>
                                                        <textarea name="note" class="form-control" rows="3"></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-danger">Reject</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No change requests found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop