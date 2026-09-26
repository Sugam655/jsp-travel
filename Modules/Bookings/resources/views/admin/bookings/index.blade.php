@extends('adminlte::page')

@section('title', 'Bookings')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Bookings</h1>
        <a href="{{ route('admin.bookings.index', ['status' => 'pending']) }}" class="btn btn-primary">
            <i class="fa-solid fa-envelope-open-text me-1"></i> Review Requests
        </a>
    </div>
@stop

@section('content')
    @include('home::admin.includes.flash-toast')
    @include('home::admin.includes.errors-alert')

    <div class="mb-3">
        <div class="btn-group btn-group-sm" role="group" aria-label="Booking type filter">
            <a href="{{ route('admin.bookings.index', array_filter(['status' => $status])) }}"
                class="btn {{ $type ? 'btn-default' : 'btn-primary' }}">All types</a>
            @foreach ($typeLabels as $key => $label)
                <a href="{{ route('admin.bookings.index', array_filter(['status' => $status, 'type' => $key])) }}"
                    class="btn {{ $type === $key ? 'btn-primary' : 'btn-default' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="mb-3">
        <div class="btn-group btn-group-sm" role="group" aria-label="Status filter">
            <a href="{{ route('admin.bookings.index', array_filter(['type' => $type])) }}"
                class="btn {{ $status ? 'btn-default' : 'btn-primary' }}">All</a>
            @foreach ($statuses as $key => $label)
                <a href="{{ route('admin.bookings.index', array_filter(['status' => $key, 'type' => $type])) }}"
                    class="btn {{ $status === $key ? 'btn-primary' : 'btn-default' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <x-adminlte-datatable id="bookings-table" :heads="$heads" :config="$config" hoverable compressed>
                @forelse ($bookings as $booking)
                    <tr>
                        <td>
                            <a href="{{ route('admin.bookings.show', $booking) }}" class="fw-semibold text-decoration-none">
                                {{ $booking->booking_reference }}
                            </a>
                            <br><small class="text-muted">#{{ $booking->id }}</small>
                        </td>
                        <td>
                            <strong>{{ $booking->service_title }}</strong>
                            <br>
                            <span class="badge text-bg-light border">{{ $booking->booking_type_label }}</span>
                        </td>
                        <td>
                            <strong>{{ $booking->name }}</strong>
                            <br>
                            <a href="mailto:{{ $booking->email }}">{{ $booking->email }}</a>
                        </td>
                        <td>
                            {{ $booking->start_date ? $booking->start_date->format('M d, Y') : '&mdash;' }}
                            @if ($booking->end_date)
                                <br><small>{{ $booking->end_date->format('M d, Y') }}</small>
                            @endif
                        </td>
                        <td>{!! $booking->amount_display !!}</td>
                        <td>{!! $booking->paid_display !!}</td>
                        <td>
                            <span class="badge {{ $booking->status_color }}">{{ $booking->status_label }}</span>
                            @if ($booking->status === 'payment_pending' && $booking->expires_at)
                                <br><small class="text-muted">expires {{ $booking->expires_at->format('M d, H:i') }}</small>
                            @endif
                        </td>
                        <td data-order="{{ $booking->created_at->timestamp }}">{{ $booking->created_at->format('M d, Y') }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.bookings.show', $booking) }}"
                                class="btn btn-sm btn-info" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            No bookings available yet.
                        </td>
                    </tr>
                @endforelse
            </x-adminlte-datatable>
        </div>
    </div>
@stop