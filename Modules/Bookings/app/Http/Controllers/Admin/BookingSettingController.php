<?php

namespace Modules\Bookings\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Bookings\Models\Booking;
use Modules\Bookings\Models\BookingSetting;
use Modules\Bookings\Services\PaymentCalculationService;

class BookingSettingController extends Controller
{
    /**
     * Show the booking settings form.
     */
    public function index(): View
    {
        return view('bookings::admin.bookings.settings', [
            'settings' => BookingSetting::DEFAULTS,
            'current' => collect(BookingSetting::DEFAULTS)->mapWithKeys(
                fn ($default, $key) => [$key => BookingSetting::getWithDefault($key)]
            ),
        ]);
    }

    /**
     * Update the booking settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'currency' => ['required', 'alpha', 'max:3'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'service_charge_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'booking_approval_required' => ['sometimes', 'boolean'],
            'payment_deadline_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'max_booking_horizon' => ['required', 'integer', 'min:1', 'max:730'],
            'max_hotel_stay' => ['required', 'integer', 'min:1', 'max:180'],
            'max_rental_days' => ['required', 'integer', 'min:1', 'max:180'],
            'rental_price_unit_limit_days' => ['required', 'integer', 'min:1', 'max:180'],
            'cancellation_policy' => ['required', 'array', 'min:1'],
            'cancellation_policy.*.days' => ['required', 'integer', 'min:0'],
            'cancellation_policy.*.refund' => ['required', 'integer', 'min:0', 'max:100'],
            'payment_methods' => ['required', 'array', 'min:1'],
            'payment_methods.*' => ['required', 'string', 'distinct', 'max:60'],
            'payment_overpayment_policy' => ['sometimes', Rule::in(PaymentCalculationService::OVERPAYMENT_POLICIES)],
            'payment_rules' => ['required', 'array'],
            'payment_rules.*' => ['sometimes', 'array'],
            'payment_rules.*.payment_mode' => ['required_with:payment_rules.tour', Rule::in(PaymentCalculationService::MODES)],
            'payment_rules.*.advance_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'payment_rules.*.advance_fixed_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_rules.*.remaining_due_timing' => ['required_with:payment_rules.tour', Rule::in(PaymentCalculationService::TIMINGS)],
            'payment_rules.*.custom_deadline_days' => ['nullable', 'integer', 'min:0', 'max:365'],
        ]);

        BookingSetting::set('currency', strtoupper((string) $validated['currency']));
        BookingSetting::set('tax_rate', (float) $validated['tax_rate']);
        BookingSetting::set('service_charge_rate', (float) $validated['service_charge_rate']);
        BookingSetting::set('booking_approval_required', (bool) ($validated['booking_approval_required'] ?? false));
        BookingSetting::set('payment_deadline_hours', (int) $validated['payment_deadline_hours']);
        BookingSetting::set('max_booking_horizon', (int) $validated['max_booking_horizon']);
        BookingSetting::set('max_hotel_stay', (int) $validated['max_hotel_stay']);
        BookingSetting::set('max_rental_days', (int) $validated['max_rental_days']);
        BookingSetting::set('rental_price_unit_limit_days', (int) $validated['rental_price_unit_limit_days']);
        BookingSetting::set('cancellation_policy', collect($validated['cancellation_policy'])
            ->values()
            ->map(fn ($tier) => ['days' => (int) $tier['days'], 'refund' => (int) $tier['refund']])
            ->all());

        if (isset($validated['payment_overpayment_policy'])) {
            BookingSetting::set('payment_overpayment_policy', $validated['payment_overpayment_policy']);
        }

        $methods = $request->input('payment_methods', []);
        $allMethods = BookingSetting::DEFAULTS['payment_methods'];
        BookingSetting::set('payment_methods', collect($allMethods)
            ->filter(fn ($method) => in_array($method['method'], $methods, true))
            ->values()
            ->all());

        if ($request->has('payment_rules') && is_array($request->input('payment_rules'))) {
            BookingSetting::set(
                'payment_rules',
                $this->normalisePaymentRules((array) $request->input('payment_rules')),
                'payments'
            );
        }

        return redirect()
            ->route('admin.bookings.settings.index')
            ->with('success', 'Booking settings updated and recalculations will use the new values.');
    }

    /**
     * Merge the submitted rules with the shipped defaults so every service
     * type always has a complete, valid configuration.
     *
     * @param  array<string, mixed>  $submitted
     * @return array<string, mixed>
     */
    private function normalisePaymentRules(array $submitted): array
    {
        $defaults = BookingSetting::DEFAULTS['payment_rules'];

        return collect(Booking::TYPES)->mapWithKeys(function (string $type) use ($submitted, $defaults) {
            $base = $defaults[$type] ?? [
                'payment_mode' => 'percentage_advance',
                'advance_percentage' => 30,
                'advance_fixed_amount' => 0,
                'remaining_due_timing' => 'before_service',
                'custom_deadline_days' => 7,
            ];
            $raw = $submitted[$type] ?? [];

            return [$type => [
                'payment_mode' => in_array($raw['payment_mode'] ?? null, PaymentCalculationService::MODES, true)
                    ? $raw['payment_mode']
                    : $base['payment_mode'],
                'advance_percentage' => isset($raw['advance_percentage'])
                    ? max(0, min(100, (float) $raw['advance_percentage']))
                    : $base['advance_percentage'],
                'advance_fixed_amount' => isset($raw['advance_fixed_amount'])
                    ? max(0, (float) $raw['advance_fixed_amount'])
                    : $base['advance_fixed_amount'],
                'remaining_due_timing' => in_array($raw['remaining_due_timing'] ?? null, PaymentCalculationService::TIMINGS, true)
                    ? $raw['remaining_due_timing']
                    : $base['remaining_due_timing'],
                'custom_deadline_days' => isset($raw['custom_deadline_days'])
                    ? max(0, min(365, (int) $raw['custom_deadline_days']))
                    : $base['custom_deadline_days'],
            ]];
        })->all();
    }
}
