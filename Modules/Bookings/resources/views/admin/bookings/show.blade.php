@extends('adminlte::page')

@section('title', 'Booking '.$booking->booking_reference)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h1>Booking {{ $booking->booking_reference }}</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.bookings.payments.index') }}" class="btn btn-outline-primary">
                <i class="fa-solid fa-money-bill-wave me-1"></i> Payments
            </a>
            <a href="{{ route('admin.bookings.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Bookings
            </a>
        </div>
    </div>
@stop

@section('content')
    @include('home::admin.includes.flash-toast')
    @include('home::admin.includes.errors-alert')

    @php
        $actions = $booking->adminActions();
        $payments = $booking->payments()->with(['recorder', 'verifier'])->get();
        $pendingPaymentForAction = $payments->firstWhere('status', 'pending');
        $refunds = $booking->refunds()->get();
        $history = $booking->history()->take(40)->get();
        $changeRequests = $booking->changeRequests()->get();
        $paySummary = (new \Modules\Bookings\Services\PaymentCalculationService)->summaryFor($booking);
    @endphp

    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-3 d-flex flex-wrap align-items-center gap-3">
                    <span class="badge {{ $booking->status_color }} px-3 py-2 fs-6">{{ $booking->status_label }}</span>
                    <span class="badge text-bg-light border px-3 py-2">
                        Payment: <span class="{{ $booking->payment_status_color }}">{{ $booking->payment_status_label }}</span>
                    </span>
                    <span class="text-muted small">
                        <i class="fa-solid fa-clock me-1"></i>Requested {{ $booking->created_at->format('M d, Y H:i') }}
                        &middot; Source: {{ $booking->source_label }}
                    </span>
                    @if (in_array($booking->status, ['pending', 'payment_pending'], true) && $booking->expires_at)
                        <span class="text-danger small">
                            <i class="fa-solid fa-hourglass-half me-1"></i>Holds until {{ $booking->expires_at->format('M d, H:i') }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            @if ($actions)
                <div class="card">
                    <div class="card-header bg-info">
                        <h3 class="card-title"><i class="fa-solid fa-sliders me-1"></i> Contextual Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($actions as $action)
                                <button type="button" class="btn {{ $action['color'] }}"
                                    data-bs-toggle="modal"
                                    data-bs-target="#action-{{ $action['action'] }}">
                                    <i class="{{ $action['icon'] }} me-1"></i> {{ $action['label'] }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">{{ $booking->service_title }}</h3>
                    <div class="card-tools">
                        <span class="badge text-bg-light border">{{ $booking->booking_type_label }}</span>
                    </div>
                </div>
                <div class="card-body">
                    @if ($service && ($service->image_url ?? null))
                        <div class="mb-3">
                            <img src="{{ $service->image_url }}" alt="{{ $booking->service_title }}"
                                class="img-fluid rounded" style="max-height: 220px; width: 100%; object-fit: cover;">
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <strong>Customer</strong>
                            <p class="mb-0">{{ $booking->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>Email</strong>
                            <p class="mb-0"><a href="mailto:{{ $booking->email }}">{{ $booking->email }}</a></p>
                        </div>
                        <div class="col-md-6">
                            <strong>Phone</strong>
                            <p class="mb-0">{!! $booking->phone ? e($booking->phone) : '&mdash;' !!}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>Address</strong>
                            <p class="mb-0">{!! $booking->address ? e($booking->address) : '&mdash;' !!}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>Travelers / Guests</strong>
                            <p class="mb-0">{{ $booking->travelers ?? '&mdash;' }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>Travel Dates</strong>
                            <p class="mb-0">
                                @if ($booking->start_date)
                                    {{ $booking->start_date->format('M d, Y') }}
                                    @if ($booking->end_date)
                                        &ndash; {{ $booking->end_date->format('M d, Y') }}
                                    @endif
                                @else
                                    &mdash;
                                @endif
                            </p>
                        </div>

                        @if ($booking->policy_accepted_at)
                            <div class="col-md-6">
                                <strong>Cancellation Policy Accepted</strong>
                                <p class="mb-0">{{ $booking->policy_accepted_at->format('M d, Y H:i') }}</p>
                            </div>
                        @endif

                        @if ($booking->service_admin_url)
                            <div class="col-md-6">
                                <strong>Related Service</strong>
                                <p class="mb-0">
                                    <a href="{{ $booking->service_admin_url }}" target="_blank">
                                        View / Edit {{ $booking->service_title }}
                                    </a>
                                </p>
                            </div>
                        @endif

                        @if ($booking->cancelled_at)
                            <div class="col-md-6">
                                <strong>Cancelled</strong>
                                <p class="mb-0 text-danger">
                                    {{ $booking->cancelled_at->format('M d, Y H:i') }}
                                    @if ($booking->cancelled_by == 'customer')
                                        (by customer)
                                    @else
                                        (by admin)
                                    @endif
                                </p>
                            </div>
                        @endif
                        @if ($booking->cancelled_reason)
                            <div class="col-12">
                                <strong>Cancellation Reason</strong>
                                <p class="mb-0" style="white-space: pre-wrap;">{{ $booking->cancelled_reason }}</p>
                            </div>
                        @endif
                    </div>

                    @if ($booking->message)
                        <hr>
                        <h5 class="text-muted">Requirements / Message</h5>
                        <p style="white-space: pre-wrap;">{{ $booking->message }}</p>
                    @endif
                </div>
            </div>

            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Price Summary</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <tbody>
                            <tr>
                                <td class="ps-4">Unit price</td>
                                <td class="text-end pe-4">{{ $booking->currency }} {{ number_format((float) ($booking->base_price && $booking->quantity ? $booking->base_price / max(1, $booking->quantity) : $booking->base_price ?? 0), 2) }}</td>
                            </tr>
                            <tr>
                                <td class="ps-4">Quantity ({{ $booking->quantity ?? 1 }})</td>
                                <td class="text-end pe-4">{{ $booking->currency }} {{ number_format((float) $booking->base_price ?? 0, 2) }}</td>
                            </tr>
                            @if ($booking->has_discount)
                                <tr>
                                    <td class="ps-4">{{ $booking->discount_display }}</td>
                                    <td class="text-end pe-4 text-danger">&minus;{{ $booking->currency }} {{ number_format((float) $booking->discount, 2) }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td class="ps-4">Tax ({{ (float) $booking->tax_rate }}%)</td>
                                <td class="text-end pe-4">{{ $booking->currency }} {{ number_format((float) $booking->tax_amount ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="ps-4">Service charge ({{ (float) $booking->service_charge_rate }}%)</td>
                                <td class="text-end pe-4">{{ $booking->currency }} {{ number_format((float) $booking->service_charge ?? 0, 2) }}</td>
                            </tr>
                            <tr class="bg-light">
                                <td class="ps-4 fw-bold">Total</td>
                                <td class="text-end pe-4 fw-bold">{{ $booking->amount_display }}</td>
                            </tr>
                            <tr>
                                <td class="ps-4">Advance required</td>
                                <td class="text-end pe-4">{{ $booking->advance_display }}</td>
                            </tr>
                            <tr>
                                <td class="ps-4">Remaining due by</td>
                                <td class="text-end pe-4">{!! $booking->payment_due_display !!}</td>
                            </tr>
                            <tr>
                                <td class="ps-4">Required now</td>
                                <td class="text-end pe-4">{{ $booking->currency }} {{ number_format($paySummary['required_now'], 2) }}</td>
                            </tr>
                            <tr>
                                <td class="ps-4">Verified paid</td>
                                <td class="text-end pe-4 text-success">{{ $booking->paid_display }}</td>
                            </tr>
                            <tr>
                                <td class="ps-4">Outstanding</td>
                                <td class="text-end pe-4">{{ $booking->due_display }}</td>
                            </tr>
                            @if ($booking->cancellation_fee !== null)
                                <tr>
                                    <td class="ps-4">Cancellation fee</td>
                                    <td class="text-end pe-4 text-danger">{{ $booking->currency }} {{ number_format((float) $booking->cancellation_fee, 2) }}</td>
                                </tr>
                            @endif
                            @if ($booking->refund_amount !== null)
                                <tr>
                                    <td class="ps-4">Refund amount</td>
                                    <td class="text-end pe-4 text-success">{{ $booking->currency }} {{ number_format((float) $booking->refund_amount, 2) }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($payments->isNotEmpty())
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Payment Ledger</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Method</th>
                                    <th>Type</th>
                                    <th>Reference</th>
                                    <th>Evidence</th>
                                    <th>Recorded / Verified</th>
                                    <th class="text-end">Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($payments as $payment)
                                    <tr>
                                        <td>{{ $payment->created_at->format('M d, H:i') }}</td>
                                        <td>
                                            <a href="{{ route('admin.payments.show', $payment) }}">{{ ucwords(str_replace('_', ' ', $payment->method ?? 'manual')) }}</a>
                                        </td>
                                        <td><span class="badge {{ $payment->type_color }}">{{ $payment->type_label }}</span></td>
                                        <td>{!! $payment->reference ? e($payment->reference) : '&mdash;' !!}</td>
                                        <td>
                                            @if ($payment->receipt_path)
                                                <a href="{{ route('admin.payments.receipt', $payment) }}" class="btn btn-sm btn-outline-primary">Receipt</a>
                                            @endif
                                            @if ($payment->note)
                                                <span class="d-block small text-muted" style="white-space: pre-wrap;">{{ $payment->note }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($payment->recorder)
                                                <span class="d-block small">
                                                    <span class="text-muted">Recorded by</span>
                                                    <strong>{{ $payment->recorder->name }}</strong>
                                                </span>
                                            @endif
                                            @if ($payment->verifier)
                                                <span class="d-block small">
                                                    <span class="text-muted">Verified by</span>
                                                    <strong>{{ $payment->verifier->name }}</strong>
                                                    @if ($payment->verified_at)
                                                        <span class="text-muted">{{ $payment->verified_at->format('M d, Y') }}</span>
                                                    @endif
                                                </span>
                                            @else
                                                <span class="d-block small text-muted">Not verified</span>
                                            @endif
                                        </td>
                                        <td class="text-end">{{ $payment->amount_display }}</td>
                                        <td>
                                            <span class="badge {{ $payment->status_color }}">{{ $payment->status_label }}</span>
                                            @if ($payment->status === 'pending')
                                                <a href="{{ route('admin.payments.show', $payment) }}" class="btn btn-sm btn-success ms-1">
                                                    <i class="fa-solid fa-check me-1"></i>Review
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if ($refunds->isNotEmpty())
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Refunds</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Created</th>
                                    <th class="text-end">Amount</th>
                                    <th>Status</th>
                                    <th>Processed</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($refunds as $refund)
                                    <tr>
                                        <td>{{ $refund->created_at->format('M d, H:i') }}</td>
                                        <td class="text-end">{{ $refund->amount_display }}</td>
                                        <td>
                                            <span class="badge {{ $refund->status_color }}">{{ $refund->status_label }}</span>
                                        </td>
                                        <td>{!! $refund->processed_at ? $refund->processed_at->format('M d, Y') : '&mdash;' !!}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if ($changeRequests->isNotEmpty())
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Change Requests</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Requested</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($changeRequests as $changeRequest)
                                    <tr>
                                        <td>{{ $changeRequest->type_label }}</td>
                                        <td>
                                            <small>
                                                @foreach (($changeRequest->requested ?? []) as $key => $value)
                                                    <span class="badge text-bg-light border me-1">{{ $key }}: {{ $value }}</span>
                                                @endforeach
                                                @if ($changeRequest->reason)
                                                    <div class="text-muted">{{ $changeRequest->reason }}</div>
                                                @endif
                                            </small>
                                        </td>
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
                                                    data-bs-toggle="modal" data-bs-target="#reject-change-{{ $changeRequest->id }}">
                                                    <i class="fa-solid fa-xmark me-1"></i>Reject
                                                </button>
                                                <div class="modal fade" id="reject-change-{{ $changeRequest->id }}" tabindex="-1">
                                                    <form action="{{ route('admin.bookings.change-requests.reject', $changeRequest) }}"
                                                        method="POST" class="modal-dialog modal-dialog-centered">
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
                                                                <button type="submit" class="btn btn-danger">Reject request</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa-solid fa-timeline me-1"></i> Booking Timeline</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="list-unstyled px-4 py-3 mb-0">
                        @forelse ($history as $entry)
                            <li class="d-flex gap-3 py-2 border-bottom">
                                <div class="text-center text-muted" style="min-width: 120px;">
                                    <small>{{ $entry->created_at->format('M d') }}<br>{{ $entry->created_at->format('H:i') }}</small>
                                </div>
                                <div class="flex-grow-1">
                                    <span class="badge text-bg-light border">{{ $entry->action_label }}</span>
                                    @if ($entry->from_status !== $entry->to_status && $entry->from_status !== null)
                                        <span class="text-muted small ms-1">
                                            {{ \Illuminate\Support\Str::of($entry->from_status)->replace('_', ' ')->title() }}
                                            &rarr; {{ \Illuminate\Support\Str::of($entry->to_status)->replace('_', ' ')->title() }}
                                        </span>
                                    @endif
                                    @if ($entry->meta)
                                        <small class="text-muted d-block mt-1">
                                            @foreach ($entry->meta as $key => $value)
                                                <span class="me-2">{{ $key }}: {{ is_array($value) ? json_encode($value) : $value }}</span>
                                            @endforeach
                                        </small>
                                    @endif
                                    @if ($entry->note)
                                        <small class="text-muted d-block mt-1">{{ $entry->note }}</small>
                                    @endif
                                </div>
                            </li>
                        @empty
                            <li class="text-muted text-center py-3">No timeline entries yet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Admin Note</h3></div>
                <form action="{{ route('admin.bookings.note', $booking) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="card-body">
                        <textarea name="admin_note" rows="4" class="form-control"
                            placeholder="Internal notes visible only in the admin panel.">{{ $booking->admin_note }}</textarea>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary btn-sm">Save Note</button>
                    </div>
                </form>
            </div>

            @if ($booking->policy_snapshot)
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Cancellation Policy (applied)</h3></div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr><th>Before</th><th>Refund</th></tr>
                            </thead>
                            <tbody>
                                @foreach (($booking->policy_snapshot['cancellation_policy'] ?? []) as $tier)
                                    <tr>
                                        <td>{{ $tier['days'] }}d before</td>
                                        <td>{{ $tier['refund'] }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-header bg-info text-white"><h3 class="card-title">Payment Summary</h3></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr>
                                <td class="ps-3">Total</td>
                                <td class="text-end pe-3">{{ $booking->currency }} {{ number_format($paySummary['total'], 2) }}</td>
                            </tr>
                            <tr>
                                <td class="ps-3">Advance required</td>
                                <td class="text-end pe-3">{{ $booking->currency }} {{ number_format($paySummary['advance_required'], 2) }}</td>
                            </tr>
                            <tr>
                                <td class="ps-3">Advance remaining</td>
                                <td class="text-end pe-3">{{ $booking->currency }} {{ number_format($paySummary['advance_remaining'], 2) }}</td>
                            </tr>
                            <tr>
                                <td class="ps-3">Verified paid</td>
                                <td class="text-end pe-3 text-success">{{ $booking->currency }} {{ number_format($paySummary['paid'], 2) }}</td>
                            </tr>
                            <tr class="table-active">
                                <td class="ps-3 fw-bold">Outstanding</td>
                                <td class="text-end pe-3 fw-bold">{{ $booking->currency }} {{ number_format($paySummary['due'], 2) }}</td>
                            </tr>
                            @if ($paySummary['overpayment'] > 0)
                                <tr>
                                    <td class="ps-3">Overpayment</td>
                                    <td class="text-end pe-3 text-warning fw-bold">{{ $booking->currency }} {{ number_format($paySummary['overpayment'], 2) }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td class="ps-3">Status</td>
                                <td class="text-end pe-3">
                                    <span class="badge {{ $paySummary['status_color'] }}">{{ $paySummary['status_label'] }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="ps-3">Mode</td>
                                <td class="text-end pe-3">{{ $paySummary['mode_label'] }}
                                    @if (! $paySummary['configured'])
                                        <span class="badge text-bg-warning ms-1" title="No rules configured for this service type; using the default.">default</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="ps-3">Remaining due</td>
                                <td class="text-end pe-3">
                                    {{ $paySummary['timing_label'] }}
                                    @if ($paySummary['due_date'])
                                        <div class="small text-muted">{{ \Illuminate\Support\Carbon::parse($paySummary['due_date'])->format('M d, Y') }}</div>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Cancellation Quote</h3></div>
                <div class="card-body">
                    @if ($cancellation_quote['eligible'] ?? false)
                        <p class="mb-1">
                            Refund <strong class="text-success">{{ $booking->currency }} {{ number_format((float) $cancellation_quote['refund_amount'], 2) }}</strong>
                            ({{ $cancellation_quote['refund_pct'] }}% of total)
                        </p>
                        <p class="mb-0">
                            Fee <strong class="text-danger">{{ $booking->currency }} {{ number_format((float) $cancellation_quote['fee_amount'], 2) }}</strong>
                            ({{ $cancellation_quote['fee_pct'] }}% of total)
                        </p>
                    @else
                        <p class="text-muted mb-0">{{ $cancellation_quote['reason'] ?? 'Cancellation is not available.' }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @foreach ($actions as $action)
        @php
            $showForm = in_array($action['action'], ['confirm', 'reject', 'request-payment', 'mark-paid', 'cancel', 'process-refund', 'set-price', 'complete'], true);
        @endphp
        @if ($showForm)
            <div class="modal fade" id="action-{{ $action['action'] }}" tabindex="-1">
                <form action="{{ route('admin.bookings.' . $action['action'], $booking) }}" method="POST"
                    @if ($action['action'] === 'mark-paid') enctype="multipart/form-data" @endif
                    class="modal-dialog modal-dialog-centered">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ $action['label'] }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            @if ($action['action'] === 'confirm')
                                <div class="form-group">
                                    <label>Confirmation note (optional)</label>
                                    <textarea name="note" class="form-control" rows="2"></textarea>
                                </div>
                                <div class="alert alert-info mb-0 mt-2">
                                    Confirms availability. Total: {!! $booking->amount_display !!}.
                                </div>
                            @elseif ($action['action'] === 'reject')
                                <div class="form-group">
                                    <label>Reason for rejection <span class="text-danger">*</span></label>
                                    <textarea name="reason" class="form-control" rows="3" required></textarea>
                                </div>
                            @elseif ($action['action'] === 'request-payment')
                                <div class="alert alert-info mb-0">
                                    The customer will be notified and can submit payment evidence for
                                    {{ $booking->due_display }}.
                                </div>
                            @elseif ($action['action'] === 'mark-paid')
                                <div class="form-group">
                                    <label>Amount received {{ $booking->currency }} <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0.01" name="amount"
                                        class="form-control" value="{{ $pendingPaymentForAction?->amount ?? ($booking->dueAmount() > 0 ? number_format($booking->dueAmount(), 2, '.', '') : '') }}" required>
                                </div>
                                <div class="form-group">
                                    <label>Payment method</label>
                                    <select name="method" class="form-control">
                                        @foreach ($paymentMethods as $entry)
                                            @php($entryMethod = is_array($entry) ? ($entry['method'] ?? null) : $entry)
                                            @if ($entryMethod)
                                                <option value="{{ $entryMethod }}" @selected($entryMethod === ($pendingPaymentForAction?->method ?? 'cash'))>
                                                    {{ is_array($entry) ? ($entry['label'] ?? $entryMethod) : $entryMethod }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Transaction reference</label>
                                    <input type="text" name="reference" value="{{ $pendingPaymentForAction?->reference }}" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Verification note</label>
                                    <textarea name="note" rows="3" maxlength="1000" class="form-control"></textarea>
                                </div>
                                <div class="form-group mb-0">
                                    <label>Receipt (optional)</label>
                                    <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf" class="form-control">
                                </div>
                            @elseif ($action['action'] === 'complete')
                                <div class="form-group">
                                    <label>Completion note (optional)</label>
                                    <textarea name="note" class="form-control" rows="2"></textarea>
                                </div>
                            @elseif ($action['action'] === 'cancel')
                                <div class="form-group">
                                    <label>Reason for cancellation</label>
                                    <textarea name="reason" class="form-control" rows="3"></textarea>
                                </div>
                                <div class="alert alert-warning mb-0 mt-2">
                                    The cancellation policy will be applied and any refund calculated automatically.
                                </div>
                            @elseif ($action['action'] === 'process-refund')
                                <div class="form-group">
                                    <label>Refund method</label>
                                    <input type="text" name="method" class="form-control"
                                        placeholder="bank_transfer / esewa / khalti / cash">
                                </div>
                                <div class="form-group">
                                    <label>Reference</label>
                                    <input type="text" name="reference" class="form-control">
                                </div>
                                <div class="form-group mb-0">
                                    <label>Note</label>
                                    <textarea name="note" class="form-control" rows="2"></textarea>
                                </div>
                            @elseif ($action['action'] === 'set-price')
                                <div class="form-group">
                                    <label>Base price {{ $booking->currency }} <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="1" name="base_price"
                                        class="form-control" required>
                                </div>
                                <div class="form-group mb-0">
                                    <label>Note</label>
                                    <textarea name="note" class="form-control" rows="2"></textarea>
                                </div>
                                <div class="alert alert-info mb-0 mt-2">
                                    Taxes and service charges are recalculated automatically.
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn {{ $action['color'] }}">
                                <i class="{{ $action['icon'] }} me-1"></i>{{ $action['label'] }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        @endif
    @endforeach
@stop