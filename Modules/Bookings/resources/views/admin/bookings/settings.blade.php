@extends('adminlte::page')

@section('title', 'Booking Settings')

@section('content_header')
    <h1>Booking Settings</h1>
@stop

@section('content')
    @include('home::admin.includes.flash-toast')
    @include('home::admin.includes.errors-alert')

    <div class="row">
        <div class="col-lg-8">
            <form action="{{ route('admin.bookings.settings.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="card">
                    <div class="card-header"><h3 class="card-title">Pricing &amp; Currency</h3></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="currency">Currency</label>
                                    <input type="text" id="currency" name="currency" maxlength="3"
                                        class="form-control @error('currency') is-invalid @enderror"
                                        value="{{ old('currency', $current['currency']) }}">
                                    @error('currency')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="tax_rate">Tax Rate (%)</label>
                                    <input type="number" step="0.01" id="tax_rate" name="tax_rate"
                                        class="form-control @error('tax_rate') is-invalid @enderror"
                                        value="{{ old('tax_rate', $current['tax_rate']) }}">
                                    @error('tax_rate')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="service_charge_rate">Service Charge (%)</label>
                                    <input type="number" step="0.01" id="service_charge_rate" name="service_charge_rate"
                                        class="form-control @error('service_charge_rate') is-invalid @enderror"
                                        value="{{ old('service_charge_rate', $current['service_charge_rate']) }}">
                                    @error('service_charge_rate')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">Booking Flow</h3></div>
                    <div class="card-body">
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" name="booking_approval_required"
                                    id="booking_approval_required" value="1"
                                    {{ (bool) $current['booking_approval_required'] ? 'checked' : '' }}>
                                <label class="custom-control-label" for="booking_approval_required">
                                    Require admin confirmation before payment is requested
                                </label>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="payment_deadline_hours">Payment/Confirmation deadline (hours)</label>
                                    <input type="number" id="payment_deadline_hours" name="payment_deadline_hours"
                                        class="form-control @error('payment_deadline_hours') is-invalid @enderror"
                                        value="{{ old('payment_deadline_hours', $current['payment_deadline_hours']) }}">
                                    @error('payment_deadline_hours')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="max_booking_horizon">Max advance booking (days)</label>
                                    <input type="number" id="max_booking_horizon" name="max_booking_horizon"
                                        class="form-control @error('max_booking_horizon') is-invalid @enderror"
                                        value="{{ old('max_booking_horizon', $current['max_booking_horizon']) }}">
                                    @error('max_booking_horizon')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="max_hotel_stay">Max hotel stay (nights)</label>
                                    <input type="number" id="max_hotel_stay" name="max_hotel_stay"
                                        class="form-control @error('max_hotel_stay') is-invalid @enderror"
                                        value="{{ old('max_hotel_stay', $current['max_hotel_stay']) }}">
                                    @error('max_hotel_stay')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="max_rental_days">Max vehicle rental (days)</label>
                                    <input type="number" id="max_rental_days" name="max_rental_days"
                                        class="form-control @error('max_rental_days') is-invalid @enderror"
                                        value="{{ old('max_rental_days', $current['max_rental_days']) }}">
                                    @error('max_rental_days')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="rental_price_unit_limit_days">Rental special-offer limit (days)</label>
                                    <input type="number" id="rental_price_unit_limit_days" name="rental_price_unit_limit_days"
                                        class="form-control @error('rental_price_unit_limit_days') is-invalid @enderror"
                                        value="{{ old('rental_price_unit_limit_days', $current['rental_price_unit_limit_days']) }}">
                                    @error('rental_price_unit_limit_days')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">Cancellation Policy</h3></div>
                    <div class="card-body">
                        <p class="text-muted">
                            Rows are ordered from the most generous to the least generous refund tier.
                            A refund % applies to the total when the customer cancels at least that many days before the service date.
                        </p>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th style="width: 60%">Cancel at least N days before service</th>
                                    <th style="width: 40%">Refund % of total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($current['cancellation_policy'] as $index => $tier)
                                    <tr>
                                        <td>
                                            <input type="number" min="0" class="form-control"
                                                name="cancellation_policy[{{ $index }}][days]"
                                                value="{{ old('cancellation_policy.'.$index.'.days', $tier['days']) }}">
                                        </td>
                                        <td>
                                            <input type="number" min="0" max="100" class="form-control"
                                                name="cancellation_policy[{{ $index }}][refund]"
                                                value="{{ old('cancellation_policy.'.$index.'.refund', $tier['refund']) }}">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">Per-Service Payment Rules</h3></div>
                    <div class="card-body">
                        <p class="text-muted">
                            The advance amount is calculated automatically from the booking total whenever a
                            booking is created or its price changes. The advance protects your inventory; the
                            remaining balance is due according to the timing below.
                        </p>
                        @foreach (['tour' => 'Tour Package', 'hotel' => 'Hotel Room', 'vehicle' => 'Vehicle Rental'] as $type => $label)
                            @php
                                $rules = $current['payment_rules'][$type] ?? [];
                            @endphp
                            <div class="border rounded p-3 mb-3">
                                <h6 class="mb-3">{{ $label }}</h6>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <div class="form-group mb-0">
                                            <label>Payment mode</label>
                                            <select name="payment_rules[{{ $type }}][payment_mode]"
                                                class="form-select @error('payment_rules.'.$type.'.payment_mode') is-invalid @enderror">
                                                @foreach (\Modules\Bookings\Services\PaymentCalculationService::MODE_LABELS as $value => $modeLabel)
                                                    <option value="{{ $value }}"
                                                        {{ old('payment_rules.'.$type.'.payment_mode', $rules['payment_mode'] ?? 'percentage_advance') === $value ? 'selected' : '' }}>
                                                        {{ $modeLabel }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group mb-0">
                                            <label>Advance (%)</label>
                                            <input type="number" step="0.01" min="0" max="100"
                                                name="payment_rules[{{ $type }}][advance_percentage]"
                                                class="form-control @error('payment_rules.'.$type.'.advance_percentage') is-invalid @enderror"
                                                value="{{ old('payment_rules.'.$type.'.advance_percentage', $rules['advance_percentage'] ?? 30) }}">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group mb-0">
                                            <label>Fixed advance amount ({{ $current['currency'] }})</label>
                                            <input type="number" step="0.01" min="0"
                                                name="payment_rules[{{ $type }}][advance_fixed_amount]"
                                                class="form-control @error('payment_rules.'.$type.'.advance_fixed_amount') is-invalid @enderror"
                                                value="{{ old('payment_rules.'.$type.'.advance_fixed_amount', $rules['advance_fixed_amount'] ?? 0) }}">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group mb-0">
                                            <label>Remaining due timing</label>
                                            <select name="payment_rules[{{ $type }}][remaining_due_timing]"
                                                class="form-select @error('payment_rules.'.$type.'.remaining_due_timing') is-invalid @enderror">
                                                @foreach (\Modules\Bookings\Services\PaymentCalculationService::TIMING_LABELS as $value => $timingLabel)
                                                    <option value="{{ $value }}"
                                                        {{ old('payment_rules.'.$type.'.remaining_due_timing', $rules['remaining_due_timing'] ?? 'before_service') === $value ? 'selected' : '' }}>
                                                        {{ $timingLabel }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group mb-0">
                                            <label>Custom deadline (days before service)</label>
                                            <input type="number" min="0" max="365"
                                                name="payment_rules[{{ $type }}][custom_deadline_days]"
                                                class="form-control @error('payment_rules.'.$type.'.custom_deadline_days') is-invalid @enderror"
                                                value="{{ old('payment_rules.'.$type.'.custom_deadline_days', $rules['custom_deadline_days'] ?? 7) }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">Overpayment Policy</h3></div>
                    <div class="card-body">
                        <div class="form-group mb-0">
                            <label for="payment_overpayment_policy">When reported evidence exceeds the outstanding balance</label>
                            <select id="payment_overpayment_policy" name="payment_overpayment_policy"
                                class="form-select @error('payment_overpayment_policy') is-invalid @enderror">
                                @foreach (\Modules\Bookings\Services\PaymentCalculationService::OVERPAYMENT_POLICY_LABELS as $value => $policyLabel)
                                    <option value="{{ $value }}" @selected(old('payment_overpayment_policy', $current['payment_overpayment_policy']) === $value)>
                                        {{ $policyLabel }}
                                    </option>
                                @endforeach
                            </select>
                            @error('payment_overpayment_policy')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                            <span class="form-text">Payment reports above the outstanding balance are rejected. The verified ledger is the source of truth for every balance.</span>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">Accepted Payment Methods</h3></div>
                    <div class="card-body">
                        @error('payment_methods')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
                        @foreach ($settings['payment_methods'] as $method)
                            <div class="form-group mb-2">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input"
                                        name="payment_methods[]" value="{{ $method['method'] }}"
                                        id="pm-{{ $method['method'] }}"
                                        {{ (collect($current['payment_methods'])->pluck('method')->contains($method['method'])) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="pm-{{ $method['method'] }}">
                                        <strong>{{ $method['label'] }}</strong>
                                        <span class="text-muted d-block small">{{ $method['details'] }}</span>
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save me-1"></i> Save Settings
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">How it works</h3></div>
                <div class="card-body text-muted small">
                    <p><strong>Booking flow:</strong> request &rarr; confirmation &rarr; payment evidence &rarr; verification &rarr; completed.</p>
                    <p>New bookings hold the service for {{ $current['payment_deadline_hours'] }} hours and expire if no action is taken.</p>
                    <p>Customer payment evidence, including cash, remains pending until staff verification.</p>
                    <p>The payment and cancellation policies displayed to customers are captured when their booking is made.</p>
                </div>
            </div>
        </div>
    </div>
@stop