@extends('adminlte::page')

@section('title', 'Payments')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h1>Payments</h1>
        <a href="{{ route('admin.bookings.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Bookings
        </a>
    </div>
@stop

@section('content')
    @include('home::admin.includes.flash-toast')
    @include('home::admin.includes.errors-alert')

    @php
        $filterQuery = array_filter([
            'search' => $search,
            'booking' => $bookingReference,
            'from' => $from,
            'to' => $to,
            'method' => $method,
        ], fn ($value) => $value !== null && $value !== '');
    @endphp

    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">Filter payments</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.bookings.payments.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label" for="payment-filter-search">Search</label>
                    <input type="search" id="payment-filter-search" name="search" value="{{ $search }}" class="form-control" placeholder="Payment ID, reference, customer, email, phone, or service">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="payment-filter-booking">Booking reference</label>
                    <input type="text" id="payment-filter-booking" name="booking" value="{{ $bookingReference }}" class="form-control" maxlength="30" placeholder="Booking reference">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="payment-filter-from">Reported from</label>
                    <input type="date" id="payment-filter-from" name="from" value="{{ $from }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="payment-filter-to">Reported to</label>
                    <input type="date" id="payment-filter-to" name="to" value="{{ $to }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="payment-filter-method">Method</label>
                    <select id="payment-filter-method" name="method" class="form-select">
                        <option value="">All methods</option>
                        @foreach ($methods as $methodKey => $methodLabel)
                            <option value="{{ $methodKey }}" @selected($method === $methodKey)>{{ $methodLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="payment-filter-status">Status</label>
                    <select id="payment-filter-status" name="status" class="form-select">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $statusKey)
                            <option value="{{ $statusKey }}" @selected($status === $statusKey)>{{ $statusLabels[$statusKey] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 d-grid">
                    <button type="submit" class="btn btn-primary" title="Apply filters">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                </div>
                @if ($filterQuery || $status)
                    <div class="col-12">
                        <a href="{{ route('admin.bookings.payments.index') }}" class="btn btn-sm btn-outline-secondary">Clear filters</a>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <div class="mb-3">
        <div class="btn-group btn-group-sm" role="group">
            <a href="{{ route('admin.bookings.payments.index', $filterQuery) }}" class="btn {{ $status ? 'btn-default' : 'btn-primary' }}">All</a>
            @foreach ($statuses as $statusKey)
                <a href="{{ route('admin.bookings.payments.index', array_merge($filterQuery, ['status' => $statusKey])) }}"
                    class="btn {{ $status === $statusKey ? 'btn-primary' : 'btn-default' }}">
                    {{ $statusLabels[$statusKey] }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Payment</th>
                            <th>Booking</th>
                            <th>Customer</th>
                            <th>Service</th>
                            <th>Method</th>
                            <th>Type</th>
                            <th>Reference</th>
                            <th>Amount</th>
                            <th>Reported</th>
                            <th>Verified</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $payment)
                            <tr id="payment-{{ $payment->id }}" @class(['table-warning' => $highlightedPaymentId === $payment->id])>
                                <td>
                                    <a href="{{ route('admin.payments.show', $payment) }}" class="fw-semibold">#{{ $payment->id }}</a>
                                </td>
                                <td>
                                    <a href="{{ route('admin.bookings.show', $payment->booking) }}">{{ $payment->booking->booking_reference }}</a>
                                    <br><small class="text-muted">#{{ $payment->booking->id }}</small>
                                </td>
                                <td>
                                    <strong>{{ $payment->booking->name }}</strong>
                                    <br>
                                    <a href="mailto:{{ $payment->booking->email }}">{{ $payment->booking->email }}</a>
                                    @if ($payment->booking->phone)
                                        <br><small class="text-muted">{{ $payment->booking->phone }}</small>
                                    @endif
                                    @if ($payment->booking->user)
                                        <br><small class="text-muted">Account: {{ $payment->booking->user->name }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if ($payment->booking->service_admin_url)
                                        <a href="{{ $payment->booking->service_admin_url }}" target="_blank" class="text-decoration-none">
                                            <strong>{{ $payment->booking->service_title }}</strong>
                                        </a>
                                    @else
                                        <strong>{{ $payment->booking->service_title }}</strong>
                                    @endif
                                    <br>
                                    <span class="badge text-bg-light border">{{ $payment->booking->booking_type_label }}</span>
                                </td>
                                <td>{{ $methods[$payment->method] ?? \Illuminate\Support\Str::headline((string) $payment->method) }}</td>
                                <td><span class="badge {{ $payment->type_color }}">{{ $payment->type_label }}</span></td>
                                <td>{!! $payment->reference ? e($payment->reference) : '&mdash;' !!}</td>
                                <td>{{ $payment->amount_display }}</td>
                                <td>
                                    {{ $payment->created_at->format('M d, Y') }}<br>
                                    <small class="text-muted">{{ $payment->created_at->format('H:i') }}</small>
                                </td>
                                <td>
                                    @if ($payment->verified_at)
                                        {{ $payment->verified_at->format('M d, Y') }}<br>
                                        <small class="text-muted">{{ $payment->verifier?->name ?? 'Admin' }}</small>
                                    @else
                                        <span class="text-muted">&mdash;</span>
                                    @endif
                                </td>
                                <td><span class="badge {{ $payment->status_color }}">{{ $payment->status_label }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('admin.payments.show', $payment) }}" class="btn btn-sm btn-outline-primary" title="View payment">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    @if ($payment->receipt_path)
                                        <a href="{{ route('admin.payments.receipt', $payment) }}" class="btn btn-sm btn-outline-secondary" title="Download receipt">
                                            <i class="fa-solid fa-file-arrow-down"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center text-muted py-4">No payments found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($payments->hasPages())
            <div class="card-footer">{{ $payments->links() }}</div>
        @endif
    </div>
@stop
