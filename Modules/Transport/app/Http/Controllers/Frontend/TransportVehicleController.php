<?php

namespace Modules\Transport\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Transport\Models\TransportVehicle;

class TransportVehicleController extends Controller
{
    /**
     * Display the public transport listing with the active vehicles, featured
     * first, then ordered by sort order.
     */
    public function index(): View
    {
        $vehicles = TransportVehicle::query()
            ->active()
            ->with('destination')
            ->orderByDesc('featured')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($vehicles->isEmpty()) {
            $vehicles = collect(TransportVehicle::DEFAULTS)
                ->map(fn (array $item) => new TransportVehicle($item));
        }

        return view('frontend.transport', [
            'vehicles' => $vehicles,
            'brands' => $vehicles
                ->pluck('brand')
                ->filter()
                ->unique()
                ->values(),
            'models' => $vehicles
                ->pluck('model')
                ->filter()
                ->unique()
                ->values(),
            'types' => ['car', 'jeep', 'van', 'bus', 'bike'],
            'typeLabels' => TransportVehicle::TYPE_LABELS,
            'priceOptions' => [5000, 10000, 15000, 25000],
            'featured' => $vehicles->where('featured', true)->values(),
        ]);
    }

    /**
     * Display a single active vehicle.
     */
    public function show(TransportVehicle $vehicle): View
    {
        abort_if(! $vehicle->is_active, 404);

        $vehicle->loadMissing('destination');

        return view('frontend.transport-detail', ['vehicle' => $vehicle]);
    }
}
