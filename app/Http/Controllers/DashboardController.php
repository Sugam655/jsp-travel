<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the admin dashboard with real data aggregated from the modules.
     */
    public function index(DashboardService $service): View
    {
        $range = $this->resolvedRange((int) request()->query('range', 30), 30);

        [$from, $to] = $this->rangeBounds($range);

        return view('dashboard', [
            'overview' => $service->overview(),
            'paymentStats' => $service->paymentStats(),
            'range' => $range,
            'bookingsSeries' => $service->series($from, $to),
            'bookingStatuses' => $service->bookingStatusCounts(),
            'messageStatuses' => $service->messageStatusCounts(),
            'mostBookedTours' => $service->mostBooked('tour'),
            'mostBookedHotels' => $service->mostBooked('hotel'),
            'vehicleTypes' => $service->vehicleTypeCounts(),
            'contentByDestination' => $service->contentByDestination(),
            'markers' => $service->destinationMarkers(),
            'activity' => $service->recentActivity(),
            'recentBookings' => $service->recentBookings(),
            'recentInquiries' => $service->recentInquiries(),
        ]);
    }

    /**
     * Return the JSON series used to refresh the bookings chart without a
     * full page reload.
     */
    public function chartData(Request $request, DashboardService $service): JsonResponse
    {
        $from = $request->query('from');
        $to = $request->query('to');

        if (! $from || ! $to) {
            $range = $this->resolvedRange((int) $request->query('range', 30), 30);
            [$from, $to] = $this->rangeBounds($range);
        }

        return response()->json($service->series((string) $from, (string) $to));
    }

    /**
     * The [from, to] date strings for a range expressed in days.
     *
     * @return array{0: string, 1: string}
     */
    protected function rangeBounds(int $days): array
    {
        $end = now();
        $start = now()->subDays($days - 1);

        return [$start->toDateString(), $end->toDateString()];
    }

    /**
     * A validated range value for the bookings chart.
     */
    protected function resolvedRange(int $requested, int $default): int
    {
        return in_array($requested, DashboardService::RANGES, true)
            ? $requested
            : $default;
    }
}
