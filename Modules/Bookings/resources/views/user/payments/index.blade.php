{{-- The customer payment ledger inside the shared AdminLTE panel. PaymentController
     scopes every query to the signed-in account, so this page can never list
     another customer's payment, whatever is typed into the url. --}}
@extends('adminlte::page')

@section('title', 'My Payments')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>My Payments</h1>
        <a href="{{ route('user.dashboard') }}" class="btn btn-sm btn-outline-primary">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>
@stop

@section('content')
    {{-- Shown as the panel's toast rather than as an alert in this content: an alert
         sits here until it is clicked away, which reads as a permanent message. --}}
    @include('home::admin.includes.flash-toast')

    <div class="row mb-4">
        @foreach ([
            ['label' => 'Payments recorded', 'value' => $recordCount, 'icon' => 'fa-coins', 'tone' => 'text-primary'],
            ['label' => 'Awaiting verification', 'value' => $pendingCount, 'icon' => 'fa-regular fa-clock', 'tone' => 'text-warning'],
            ['label' => 'Verified amount', 'value' => $currency.' '.number_format($verifiedTotal, 2), 'icon' => 'fa-hand-holding-dollar', 'tone' => 'text-success'],
        ] as $card)
            <div class="col-sm-4">
                <div class="border rounded p-3 h-100 bg-white shadow-sm">
                    <div class="text-muted small"><i class="fa-solid {{ $card['icon'] }} me-1"></i>{{ $card['label'] }}</div>
                    <div class="fs-4 fw-semibold {{ $card['tone'] }}">{{ $card['value'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h3 class="card-title">Payment history</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Booking</th>
                            <th>Service</th>
                            <th>Method</th>
                            <th>Type</th>
                            <th>Reference</th>
                            <th class="text-end">Amount</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $payment)
                            <tr>
                                <td>
                                    {{ $payment->created_at->format('M d, Y') }}<br>
                                    <small class="text-muted">{{ $payment->created_at->format('H:i') }}</small>
                                </td>
                                <td>
                                    <a href="{{ route('bookings.show', $payment->booking->booking_reference) }}"
                                        class="text-primary fw-semibold text-decoration-none">
                                        {{ $payment->booking->booking_reference }}
                                    </a>
                                    <br>
                                    <small class="text-muted">#{{ $payment->id }}</small>
                                </td>
                                <td>
                                    <strong>{{ $payment->booking->service_title }}</strong><br>
                                    <small class="text-muted">{{ $payment->booking->booking_type_label }}</small>
                                </td>
                                <td>{{ ucwords(str_replace('_', ' ', $payment->method ?? 'manual')) }}</td>
                                <td><span class="badge {{ $payment->type_color }}">{{ $payment->type_label }}</span></td>
                                <td>{!! $payment->reference ? e($payment->reference) : '&mdash;' !!}</td>
                                <td class="text-end">{{ $payment->amount_display }}</td>
                                <td><span class="badge {{ $payment->status_color }}">{{ $payment->status_label }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('payments.show', $payment) }}" class="btn btn-sm btn-outline-primary">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    You don't have any payments yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($payments->hasPages())
            <div class="card-footer clearfix">
                {{ $payments->links() }}
            </div>
        @endif
    </div>
@stop