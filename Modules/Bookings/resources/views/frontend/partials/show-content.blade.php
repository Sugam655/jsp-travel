@if(! ($inAdminLte ?? false))
<section class="contacts reach-contact">
        <div class="container">
            <div class="reach-topbar">
                <div>
                    <span class="reach-label">Your Reservation</span>
                    <h2 class="reach-heading">{{ $booking->booking_reference }}</h2>
                </div>
                <p class="reach-subtext">Here is the status and full price breakdown of your booking.</p>
                <div class="mt-2">
                    <a href="{{ route('bookings.my') }}" class="btn btn-sm btn-outline-primary">
                        <i class="fa-solid fa-list me-1"></i> View My Bookings
                    </a>
                </div>
            </div>
@endif

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('just_booked'))
                <div class="alert alert-success border-0 shadow-sm p-4">
                    <div class="d-flex align-items-start gap-3">
                        <div class="fs-1 lh-1"><i class="fa-solid fa-circle-check text-success"></i></div>
                        <div>
                            <h4 class="mb-1" style="color: #155724;">Booking request received!</h4>
                            <p class="mb-0" style="color: #155724;">
                                Your <strong>{{ $booking->service_title }}</strong> booking
                                (<strong>{{ $booking->booking_reference }}</strong>) has been received and will be
                                confirmed shortly. Your service is held and we will notify you of the next step.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            @if ($errors->has('change_request'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2"></i>{{ $errors->first('change_request') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @php
                $plan = $summary ?? (new \Modules\Bookings\Services\PaymentCalculationService)->summaryFor($booking);
                $outstanding = (float) $plan['remaining'];
                $hasPendingEvidence = $booking->payments()->where('status', 'pending')->exists();
                $isPayable = in_array($booking->status, ['confirmed', 'payment_pending'], true);
            @endphp

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 py-2">Payment summary</h5>
                            <span class="badge text-bg-light border">{{ $plan['status_label'] }}</span>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-sm-6 col-lg-3">
                                    <div class="text-muted small">Total</div>
                                    <div class="fs-5 fw-bold">{{ $booking->currency }} {{ number_format($plan['total'], 2) }}</div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="text-muted small">Verified paid</div>
                                    <div class="fs-5 fw-bold text-success">{{ $booking->currency }} {{ number_format($plan['paid'], 2) }}</div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="text-muted small">Remaining</div>
                                    <div class="fs-5 fw-bold {{ $outstanding > 0.005 ? 'text-primary' : 'text-secondary' }}">
                                        {{ $booking->currency }} {{ number_format($outstanding, 2) }}
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="text-muted small">Due date</div>
                                    <div class="fs-6 fw-semibold">
                                        {{ $plan['due_date'] ? \Illuminate\Support\Carbon::parse($plan['due_date'])->format('M d, Y') : '&mdash;' }}
                                    </div>
                                </div>
                            </div>

                            <hr>

                            @if ($isPayable && $hasPendingEvidence)
                                <div class="alert alert-warning mb-0" role="alert">
                                    <i class="fa-solid fa-circle-exclamation me-2"></i>
                                    You have a payment awaiting verification. The balance updates only after the agency verifies it.
                                </div>
                            @elseif ($isPayable && $outstanding > 0.005)
                                <a href="{{ route('bookings.payment', $booking->booking_reference) }}" class="btn btn-success">
                                    <i class="fa-solid fa-credit-card me-1"></i>
                                    Pay Remaining {{ $booking->currency }} {{ number_format($outstanding, 2) }}
                                </a>
                                <div class="form-text mt-2">
                                    You may pay the full remaining balance or any smaller amount of {{ $booking->currency }} 0.01 or more.
                                </div>
                            @elseif ($outstanding <= 0.005)
                                <div class="alert alert-success mb-0" role="alert">
                                    <i class="fa-solid fa-circle-check me-2"></i>
                                    <strong>Fully Paid</strong> &mdash; no payment remaining for this booking.
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                                <span class="badge {{ $booking->status_color }} px-3 py-2 fs-6">{{ $booking->status_label }}</span>
                                <span class="badge text-bg-light border">Payment: {{ $booking->payment_status_label }}</span>
                            </div>
                            <h4>{{ $booking->service_title }}</h4>
                            <div class="text-muted mb-3">
                                {{ $booking->booking_type_label }}
                                @if ($booking->start_date)
                                    <br>
                                    <i class="fa-regular fa-calendar me-1"></i>
                                    {{ $booking->start_date->format('D, M d, Y') }}
                                    @if ($booking->end_date)
                                        &ndash; {{ $booking->end_date->format('D, M d, Y') }}
                                    @endif
                                @endif
                                @if ($booking->travelers)
                                    <br><i class="fa-solid fa-user me-1"></i>{{ $booking->travelers }} traveler(s)
                                @endif
                            </div>

                            @if ($booking->message)
                                <div class="mt-3 small text-muted">
                                    <strong>Message:</strong> {{ $booking->message }}
                                </div>
                            @endif
                            <hr>
                            <h5 class="text-muted">Price breakdown</h5>
                            <table class="table table-sm mb-2" style="max-width: 420px;">
                                <tr>
                                    <td class="text-muted">Subtotal</td>
                                    <td class="text-end">{{ $booking->currency }} {{ number_format((float) $booking->base_price ?? 0, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Tax ({{ (float) $booking->tax_rate }}%)</td>
                                    <td class="text-end">{{ $booking->currency }} {{ number_format((float) $booking->tax_amount ?? 0, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Service charge ({{ (float) $booking->service_charge_rate }}%)</td>
                                    <td class="text-end">{{ $booking->currency }} {{ number_format((float) $booking->service_charge ?? 0, 2) }}</td>
                                </tr>
                                <tr class="table-active">
                                    <td><strong>Total</strong></td>
                                    <td class="text-end"><strong>{!! $booking->amount_display !!}</strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Advance required</td>
                                    <td class="text-end">{{ $booking->advance_display }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Verified paid</td>
                                    <td class="text-end text-success">{{ $booking->paid_display }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Outstanding</td>
                                    <td class="text-end">{{ $booking->due_display }}</td>
                                </tr>
                                @if ($booking->payment_due_date)
                                    <tr>
                                        <td class="text-muted">Remaining payment due</td>
                                        <td class="text-end">{{ $booking->payment_due_date->format('M d, Y') }}</td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    @php
                        $payments = $booking->payments()->get();
                    @endphp
                    @if ($payments->isNotEmpty())
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 py-2">Payment history</h5>
                                <a href="{{ route('payments.index') }}" class="small">View all payments</a>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Method</th>
                                            <th>Type</th>
                                            <th>Reference</th>
                                            <th class="text-end">Amount</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($payments as $payment)
                                            <tr>
                                                <td>{{ $payment->created_at->format('M d, Y H:i') }}</td>
                                                <td>{{ ucwords(str_replace('_', ' ', $payment->method ?? 'manual')) }}</td>
                                                <td>
                                                    <a href="{{ route('payments.show', $payment) }}" class="text-decoration-none">
                                                        <span class="badge {{ $payment->type_color }}">{{ $payment->type_label }}</span>
                                                    </a>
                                                </td>
                                                <td>{!! $payment->reference ? e($payment->reference) : '&mdash;' !!}</td>
                                                <td class="text-end">{{ $payment->amount_display }}</td>
                                                <td>
                                                    <span class="badge {{ $payment->status_color }}">{{ $payment->status_label }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @php
                        $changeRequests = $booking->changeRequests()->get();
                    @endphp
                    @if ($changeRequests->isNotEmpty())
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0 py-2">Your change requests</h5>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-unstyled px-4 py-3 mb-0">
                                    @foreach ($changeRequests as $changeRequest)
                                        <li id="change-request-{{ $changeRequest->id }}" class="py-2 border-bottom">
                                            <div class="d-flex justify-content-between align-items-start gap-2">
                                                <div>
                                                    <span class="badge text-bg-light border">{{ $changeRequest->type_label }}</span>
                                                    <div class="small text-muted mt-1">
                                                        @forelse (($changeRequest->requested ?? []) as $key => $value)
                                                            <span class="me-2">{{ \Illuminate\Support\Str::of($key)->replace('_', ' ')->title() }}: <strong>{{ $value }}</strong></span>
                                                        @empty
                                                            <span>&mdash;</span>
                                                        @endforelse
                                                    </div>
                                                    @if ($changeRequest->reason)
                                                        <div class="small text-muted mt-1">{{ $changeRequest->reason }}</div>
                                                    @endif
                                                    @if ($changeRequest->response_note)
                                                        <div class="small mt-1"><strong>Our response:</strong> {{ $changeRequest->response_note }}</div>
                                                    @endif
                                                    <div class="small text-muted mt-1">
                                                        Requested {{ $changeRequest->created_at->format('M d, Y H:i') }}
                                                        @if ($changeRequest->reviewed_at)
                                                            &middot; Reviewed {{ $changeRequest->reviewed_at->format('M d, Y H:i') }}
                                                        @endif
                                                    </div>
                                                </div>
                                                <span class="badge {{ $changeRequest->status_color }}">{{ $changeRequest->status_label }}</span>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0 py-2">Booking timeline</h5>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-unstyled px-4 py-3 mb-0">
                                @forelse ($booking->history()->take(30)->get() as $entry)
                                    <li class="d-flex gap-3 py-2 border-bottom">
                                        <div class="text-muted small" style="min-width: 100px;">
                                            {{ $entry->created_at->format('M d') }}<br>{{ $entry->created_at->format('H:i') }}
                                        </div>
                                        <div>
                                            <span class="badge text-bg-light border">{{ $entry->action_label }}</span>
                                            @if ($entry->note)
                                                <div class="text-muted small mt-1">{{ $entry->note }}</div>
                                            @endif
                                        </div>
                                    </li>
                                @empty
                                    <li class="text-muted py-3">No timeline entries yet.</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0 py-2">Booked by</h5>
                        </div>
                        <div class="card-body">
                            <div class="fw-semibold">{{ $booking->name }}</div>
                            <div class="text-muted small">{{ $booking->email }}</div>
                            @if ($booking->phone)
                                <div class="text-muted small">{{ $booking->phone }}</div>
                            @endif
                            @if ($booking->address)
                                <div class="text-muted small">{{ $booking->address }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="mb-3">What you can do</h5>

                            @if ($isPayable && $hasPendingEvidence)
                                <a href="{{ route('bookings.payment', $booking->booking_reference) }}"
                                    class="btn btn-warning w-100 mb-2">
                                    <i class="fa-solid fa-clock me-1"></i> View payment status
                                </a>
                            @elseif ($isPayable && $outstanding > 0.005)
                                <a href="{{ route('bookings.payment', $booking->booking_reference) }}"
                                    class="btn btn-success w-100 mb-2">
                                    <i class="fa-solid fa-credit-card me-1"></i>
                                    Pay Remaining {{ $booking->currency }} {{ number_format($outstanding, 2) }}
                                </a>
                            @elseif ($outstanding <= 0.005)
                                <div class="alert alert-success mb-2" role="alert">
                                    <i class="fa-solid fa-circle-check me-1"></i> Fully Paid
                                </div>
                            @elseif (in_array($booking->status, ['paid', 'completed', 'cancelled', 'rejected', 'expired'], true) === false)
                                <a href="{{ route('bookings.payment', $booking->booking_reference) }}"
                                    class="btn btn-outline-primary w-100 mb-2">
                                    <i class="fa-solid fa-receipt me-1"></i> View payment summary
                                </a>
                            @endif

                            @if ($booking->isCustomerCancellable())
                                <a href="{{ route('bookings.cancel.preview', $booking->booking_reference) }}"
                                    class="btn btn-outline-danger w-100 mb-2">
                                    <i class="fa-solid fa-ban me-1"></i> Cancel booking
                                </a>
                            @endif

                            @if ($booking->canBeChanged())
                                <button type="button" class="btn btn-outline-primary w-100"
                                    data-bs-toggle="modal" data-bs-target="#changeRequestModal">
                                    <i class="fa-solid fa-arrows-rotate me-1"></i> Request a change
                                </button>
                            @endif

                            <hr>
                            <div class="text-muted small">
                                <strong>Questions?</strong><br>
                                Contact us on the
                                <a href="{{ route('contact.index') }}">contact page</a>.
                                @if ($booking->expires_at && in_array($booking->status, ['pending', 'payment_pending'], true))
                                    <div class="mt-2 text-danger">
                                        This reservation holds your service until {{ $booking->expires_at->format('M d, H:i') }}.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if ($booking->policy_snapshot)
                        <div class="card shadow-sm">
                            <div class="card-header bg-light">
                                <h5 class="mb-0 py-2">Cancellation policy</h5>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-sm mb-0">
                                    <thead><tr><th>Before</th><th>Refund</th></tr></thead>
                                    <tbody>
                                        @foreach (($booking->policy_snapshot['cancellation_policy'] ?? []) as $tier)
                                            <tr>
                                                <td>{{ $tier['days'] }}d before service</td>
                                                <td>{{ $tier['refund'] }}%</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
    @if(! ($inAdminLte ?? false))
        </div>
    </section>
    @endif

    @if ($booking->canBeChanged())
        <div class="modal fade" id="changeRequestModal" tabindex="-1">
            <form action="{{ route('bookings.change-request', $booking->booking_reference) }}" method="POST">
                @csrf
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Request a change</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label" style="color: #6b7280;">Type of change</label>
                                    <select name="type" class="form-select" required>
                                        <option value="dates">Dates</option>
                                        <option value="travelers">Number of travelers</option>
                                        <option value="service">Service / vehicle / room</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                @if ($booking->start_date)
                                    <div class="col-md-4">
                                        <label class="form-label" style="color: #6b7280;">New start date</label>
                                        <input type="date" name="requested[start_date]" class="form-control" value="{{ $booking->start_date->format('Y-m-d') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" style="color: #6b7280;">New end date</label>
                                        <input type="date" name="requested[end_date]" class="form-control" value="{{ $booking->end_date?->format('Y-m-d') }}">
                                    </div>
                                @endif
                                <div class="col-md-6">
                                    <label class="form-label" style="color: #6b7280;">New number of travelers</label>
                                    <input type="number" min="1" name="requested[travelers]" class="form-control"
                                        value="{{ $booking->travelers }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" style="color: #6b7280;">New service (optional)</label>
                                    <input type="text" name="requested[service_title]" class="form-control"
                                        value="{{ $booking->service_title }}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" style="color: #6b7280;">Why do you need this change?</label>
                                    <textarea name="reason" class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Submit change request</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    @endif