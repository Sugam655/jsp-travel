@extends('adminlte::page')

@section('title', 'Payment #'.$payment->id)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Payment #{{ $payment->id }}</h1>
        <div>
            <a href="{{ route('payments.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> My Payments
            </a>
            <a href="{{ route('bookings.show', $booking->booking_reference) }}" class="btn btn-primary">
                <i class="fa-solid fa-calendar-check me-1"></i> View Booking
            </a>
        </div>
    </div>
@stop

@section('content')
    @include('home::admin.includes.flash-toast')

    @php
        // Outstanding comes from the recalculated summary, so it always matches
        // the booking page for the same booking. Pending payments are excluded
        // from "paid" and therefore never reduce this figure.
        $outstanding = (float) $summary['due'];
        $isFullyPaid = $outstanding <= 0.005;
        $isPayable = in_array($booking->status, ['confirmed', 'payment_pending'], true);
    @endphp

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Payment details</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <tbody>
                            <tr>
                                <th class="ps-4" style="width: 40%">Status</th>
                                <td class="pe-4"><span class="badge {{ $payment->status_color }}">{{ $payment->status_label }}</span></td>
                            </tr>
                            <tr>
                                <th class="ps-4">Type</th>
                                <td class="pe-4"><span class="badge {{ $payment->type_color }}">{{ $payment->type_label }}</span></td>
                            </tr>
                            <tr>
                                <th class="ps-4">Method</th>
                                <td class="pe-4">{{ ucwords(str_replace('_', ' ', $payment->method ?? 'manual')) }}</td>
                            </tr>
                            <tr>
                                <th class="ps-4">Transaction reference</th>
                                <td class="pe-4">{!! $payment->reference ? e($payment->reference) : '&mdash;' !!}</td>
                            </tr>
                            <tr>
                                <th class="ps-4">Amount</th>
                                <td class="pe-4 fw-bold">{{ $payment->amount_display }}</td>
                            </tr>
                            <tr>
                                <th class="ps-4">Reported</th>
                                <td class="pe-4">{{ $payment->created_at->format('M d, Y H:i') }}</td>
                            </tr>
                            <tr>
                                <th class="ps-4">Verified at</th>
                                <td class="pe-4">{!! $payment->verified_at?->format('M d, Y H:i') ?: '&mdash;' !!}</td>
                            </tr>
                            @if ($payment->receipt_path)
                                <tr>
                                    <th class="ps-4">Receipt</th>
                                    <td class="pe-4"><a href="{{ route('payments.receipt', $payment) }}" class="btn btn-sm btn-outline-primary">Download receipt</a></td>
                                </tr>
                            @endif
                            @if ($payment->note)
                                <tr>
                                    <th class="ps-4">Note</th>
                                    <td class="pe-4" style="white-space: pre-wrap;">{{ $payment->note }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Booking</h3>
                    <div class="card-tools">
                        <span class="badge {{ $booking->status_color }}">{{ $booking->status_label }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <h5 class="mb-1">{{ $booking->service_title }}</h5>
                    <div class="text-muted small mb-3">
                        {{ $booking->booking_reference }} &middot; {{ $booking->booking_type_label }}
                        @if ($booking->start_date)
                            <br>{{ $booking->start_date->format('D, M d, Y') }}
                            @if ($booking->end_date)
                                &ndash; {{ $booking->end_date->format('D, M d, Y') }}
                            @endif
                        @endif
                    </div>
                    <p class="mb-0">
                        Payment status:
                        <span class="badge {{ $booking->payment_status_color }}">{{ $booking->payment_status_label }}</span>
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Payment history for this booking</h3>
                    <div class="card-tools">
                        <span class="badge text-bg-light border">
                            Verified paid {{ $booking->currency }} {{ number_format($summary['paid'], 2) }}
                        </span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">#</th>
                                <th>Type</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th class="text-end">Amount</th>
                                <th>Status</th>
                                <th>Reported</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($bookingPayments as $historyPayment)
                                <tr class="{{ $historyPayment->id === $payment->id ? 'table-active' : '' }}">
                                    <td class="ps-4">
                                        #{{ $historyPayment->id }}
                                        @if ($historyPayment->id === $payment->id)
                                            <span class="badge text-bg-primary">This payment</span>
                                        @endif
                                    </td>
                                    <td><span class="badge {{ $historyPayment->type_color }}">{{ $historyPayment->type_label }}</span></td>
                                    <td>{{ ucwords(str_replace('_', ' ', $historyPayment->method ?? 'manual')) }}</td>
                                    <td class="small text-muted">{!! $historyPayment->reference ? e($historyPayment->reference) : '&mdash;' !!}</td>
                                    <td class="text-end fw-bold">{{ $historyPayment->amount_display }}</td>
                                    <td><span class="badge {{ $historyPayment->status_color }}">{{ $historyPayment->status_label }}</span></td>
                                    <td class="small text-muted">{{ $historyPayment->created_at->format('M d, Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-3">No payments recorded yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer small text-muted">
                    Pending payments are listed above but do not reduce the outstanding
                    balance until the agency verifies them.
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-info text-white"><h3 class="card-title">Payment Summary</h3></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr>
                                <td class="ps-3">Total</td>
                                <td class="text-end pe-3">{{ $booking->currency }} {{ number_format($summary['total'], 2) }}</td>
                            </tr>
                            <tr>
                                <td class="ps-3">Advance required</td>
                                <td class="text-end pe-3">{{ $booking->currency }} {{ number_format($summary['advance_required'], 2) }}</td>
                            </tr>
                            <tr>
                                <td class="ps-3">Verified paid</td>
                                <td class="text-end pe-3 text-success">{{ $booking->currency }} {{ number_format($summary['paid'], 2) }}</td>
                            </tr>
                            <tr class="table-active">
                                <td class="ps-3 fw-bold">Outstanding</td>
                                <td class="text-end pe-3 fw-bold">{{ $booking->currency }} {{ number_format($summary['due'], 2) }}</td>
                            </tr>
                            <tr>
                                <td class="ps-3">Status</td>
                                <td class="text-end pe-3"><span class="badge {{ $summary['status_color'] }}">{{ $summary['status_label'] }}</span></td>
                            </tr>
                            <tr>
                                <td class="ps-3">Remaining due by</td>
                                <td class="text-end pe-3">
                                    {{ $summary['timing_label'] }}
                                    @if ($summary['due_date'])
                                        <div class="small text-muted">{{ \Illuminate\Support\Carbon::parse($summary['due_date'])->format('M d, Y') }}</div>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Continue paying the same booking. The amount is recalculated on
                 every request and re-checked by the workflow service, which also
                 owns the ownership check, so no amount is trusted from here. --}}
            @if ($pendingPayment)
                <div class="alert alert-warning mb-3">
                    <h5 class="alert-heading">Payment awaiting verification</h5>
                    <p class="mb-2">
                        {{ $pendingPayment->amount_display }} reported via
                        {{ str_replace('_', ' ', $pendingPayment->method) }} is still pending.
                        The balance above only changes once the agency verifies it.
                    </p>
                    <a href="{{ route('bookings.payment', $booking->booking_reference) }}" class="btn btn-sm btn-outline-dark">
                        View payment status
                    </a>
                </div>
            @elseif ($isFullyPaid)
                <div class="alert alert-success mb-3" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i>
                    <strong>Fully Paid</strong> &mdash; no payment remaining for this booking.
                </div>
            @elseif ($isPayable)
                <a href="{{ route('bookings.payment', $booking->booking_reference) }}"
                    class="btn btn-success btn-block mb-2">
                    <i class="fa-solid fa-credit-card me-1"></i>
                    Pay Remaining {{ $booking->currency }} {{ number_format($outstanding, 2) }}
                </a>
                <div class="form-text">
                    You may pay the full remaining balance or any smaller amount of
                    {{ $booking->currency }} 0.01 or more.
                </div>
            @else
                <div class="alert alert-info mb-3" role="alert">
                    <i class="fa-solid fa-circle-info me-2"></i>
                    This booking is not open for payment yet. The payment form opens
                    once the agency confirms it.
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Questions?</h3>
                </div>
                <div class="card-body">
                    <div class="small text-muted">
                        Need help with this payment? Contact us on the
                        <a href="{{ route('contact.index') }}">contact page</a>, or view the
                        <a href="{{ route('bookings.show', $booking->booking_reference) }}">full booking</a>.
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
