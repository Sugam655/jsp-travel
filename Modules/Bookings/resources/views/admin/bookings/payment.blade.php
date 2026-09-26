@extends('adminlte::page')

@section('title', 'Payment #'.$payment->id)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h1>Payment #{{ $payment->id }}</h1>
            <p class="text-muted mb-0">{{ $payment->amount_display }} reported via {{ $methodLabel }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.bookings.payments.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Payments
            </a>
            <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-outline-secondary">
                Booking {{ $booking->booking_reference }}
            </a>
        </div>
    </div>
@stop

@section('content')
    @include('home::admin.includes.flash-toast')
    @include('home::admin.includes.errors-alert')

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Payment evidence</h3>
                    <div class="card-tools">
                        <span class="badge {{ $payment->status_color }}">{{ $payment->status_label }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">Amount reported</dt>
                        <dd class="col-sm-9">{{ $payment->amount_display }}</dd>
                        <dt class="col-sm-3">Payment type</dt>
                        <dd class="col-sm-9"><span class="badge {{ $payment->type_color }}">{{ $payment->type_label }}</span></dd>
                        <dt class="col-sm-3">Method</dt>
                        <dd class="col-sm-9">{{ $methodLabel }}</dd>
                        <dt class="col-sm-3">Reference</dt>
                        <dd class="col-sm-9">{!! $payment->reference ? e($payment->reference) : '&mdash;' !!}</dd>
                        <dt class="col-sm-3">Reported</dt>
                        <dd class="col-sm-9">{{ $payment->created_at->format('M d, Y H:i') }}</dd>
                        <dt class="col-sm-3">Recorded by</dt>
                        <dd class="col-sm-9">{{ $payment->recorder?->name ?? $booking->name }}</dd>
                        <dt class="col-sm-3">Verified</dt>
                        <dd class="col-sm-9">
                            @if ($payment->verified_at)
                                {{ $payment->verified_at->format('M d, Y H:i') }} by {{ $payment->verifier?->name ?? 'Admin' }}
                            @else
                                <span class="text-muted">Awaiting verification</span>
                            @endif
                        </dd>
                    </dl>

                    @if ($payment->note)
                        <hr>
                        <h4>Notes</h4>
                        <div class="alert alert-secondary mb-0" style="white-space: pre-wrap;">{{ $payment->note }}</div>
                    @endif

                    @if ($payment->receipt_path)
                        <hr>
                        <a href="{{ route('admin.payments.receipt', $payment) }}" class="btn btn-outline-primary">
                            <i class="fa-solid fa-file-arrow-down me-1"></i> Download receipt
                        </a>
                    @endif

                    @if ($payment->gateway_response)
                        <hr>
                        <h4>Gateway response</h4>
                        <pre class="bg-light p-3 rounded">{{ json_encode($payment->gateway_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    @endif
                </div>
            </div>

            @if ($payment->status === 'pending')
                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title">Verification actions</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.payments.verify', $payment) }}" method="POST" enctype="multipart/form-data" class="mb-4">
                            @csrf
                            <h4>Verify payment</h4>
                            <p class="text-muted small">Correct the amount only when the evidence supports a different value. The verified amount cannot exceed the outstanding balance.</p>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label" for="verify-amount">Verified amount</label>
                                    <input type="number" name="amount" id="verify-amount" step="0.01" min="0.01" max="{{ $summary['due'] }}" value="{{ old('amount', $payment->amount) }}" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="verify-reference">Reference</label>
                                    <input type="text" name="reference" id="verify-reference" value="{{ old('reference', $payment->reference) }}" maxlength="255" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="verify-receipt">Receipt</label>
                                    <input type="file" name="receipt" id="verify-receipt" accept=".jpg,.jpeg,.png,.pdf" class="form-control">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="verify-note">Verification note</label>
                                    <textarea name="note" id="verify-note" rows="3" maxlength="1000" class="form-control" placeholder="Optional note for the audit trail"></textarea>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success mt-3">
                                <i class="fa-solid fa-circle-check me-1"></i> Verify payment
                            </button>
                        </form>

                        <form action="{{ route('admin.payments.fail', $payment) }}" method="POST">
                            @csrf
                            <h4>Reject payment</h4>
                            <div class="mb-2">
                                <label class="form-label" for="reject-reason">Reason</label>
                                <textarea name="reason" id="reject-reason" rows="3" maxlength="1000" class="form-control" placeholder="Explain why the evidence cannot be verified"></textarea>
                            </div>
                            <button type="submit" class="btn btn-danger">
                                <i class="fa-solid fa-xmark me-1"></i> Reject payment
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Payment context</h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">Customer</dt>
                        <dd class="col-7">
                            <strong>{{ $booking->name }}</strong>
                            <br><a href="mailto:{{ $booking->email }}">{{ $booking->email }}</a>
                            @if ($booking->phone)
                                <br><small class="text-muted">{{ $booking->phone }}</small>
                            @endif
                            @if ($booking->user)
                                <br><small class="text-muted">Account: {{ $booking->user->name }}</small>
                            @endif
                        </dd>
                        <dt class="col-5">Booking</dt>
                        <dd class="col-7">
                            <a href="{{ route('admin.bookings.show', $booking) }}">{{ $booking->booking_reference }}</a>
                        </dd>
                        <dt class="col-5">Service</dt>
                        <dd class="col-7">
                            @if ($booking->service_admin_url)
                                <a href="{{ $booking->service_admin_url }}" target="_blank">{{ $booking->service_title }}</a>
                            @else
                                {{ $booking->service_title }}
                            @endif
                            <br>
                            <span class="badge text-bg-light border">{{ $booking->booking_type_label }}</span>
                        </dd>
                    </dl>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fa-solid fa-users me-1"></i> View customer
                        </a>
                        @if ($payment->receipt_path)
                            <a href="{{ route('admin.payments.receipt', $payment) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="fa-solid fa-file-arrow-down me-1"></i> View receipt
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Booking balance</h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-7">Total</dt>
                        <dd class="col-5 text-end">{{ $booking->currency }} {{ number_format($summary['total'], 2) }}</dd>
                        <dt class="col-7">Advance required</dt>
                        <dd class="col-5 text-end">{{ $booking->currency }} {{ number_format($summary['advance_required'], 2) }}</dd>
                        <dt class="col-7">Verified paid</dt>
                        <dd class="col-5 text-end text-success">{{ $booking->currency }} {{ number_format($summary['paid'], 2) }}</dd>
                        <dt class="col-7">Required now</dt>
                        <dd class="col-5 text-end">{{ $booking->currency }} {{ number_format($summary['required_now'], 2) }}</dd>
                        <dt class="col-7">Remaining</dt>
                        <dd class="col-5 text-end">{{ $booking->currency }} {{ number_format($summary['remaining'], 2) }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Payment history</h3>
                </div>
                <div class="list-group list-group-flush">
                    @forelse ($paymentHistory as $historyPayment)
                        <a href="{{ route('admin.payments.show', $historyPayment) }}" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between gap-2">
                                <strong>#{{ $historyPayment->id }}</strong>
                                <span class="badge {{ $historyPayment->status_color }}">{{ $historyPayment->status_label }}</span>
                            </div>
                            <div class="small text-muted">{{ $historyPayment->created_at->format('M d, Y H:i') }} · {{ $historyPayment->amount_display }}</div>
                        </a>
                    @empty
                        <div class="list-group-item text-muted">No payment history.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@stop
