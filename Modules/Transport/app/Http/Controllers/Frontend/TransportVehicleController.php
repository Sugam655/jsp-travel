<?php

namespace Modules\Transport\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Transport\Models\TransportVehicle;

class TransportVehicleController extends Controller
{
    /**
     * Display the public transport listing with the active vehicles, featured
     * first, then ordered by sort order.
     *
     * The optional query string narrows the listing on the server: `brand`,
     * `model` and `vehicle_type` match exactly, and `max_price` caps the
     * rate. The filter options themselves always come from every active
     * vehicle so the controls stay complete while a filter is applied.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'vehicle_type' => ['nullable', 'string', 'max:30'],
            'max_price' => ['nullable', 'integer', 'min:0'],
        ]);

        $activeVehicles = TransportVehicle::query()->active();

        $vehicles = (clone $activeVehicles)
            ->with('destination')
            ->when(
                filled($filters['brand'] ?? null),
                fn ($query) => $query->where('brand', $filters['brand'])
            )
            ->when(
                filled($filters['model'] ?? null),
                fn ($query) => $query->where('model', $filters['model'])
            )
            ->when(
                filled($filters['vehicle_type'] ?? null),
                fn ($query) => $query->where('vehicle_type', $filters['vehicle_type'])
            )
            ->when(
                filled($filters['max_price'] ?? null),
                fn ($query) => $query->where('price', '<=', $filters['max_price'])
            )
            ->orderByDesc('featured')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($vehicles->isEmpty() && $this->hasNoFilters($filters)) {
            $vehicles = collect(TransportVehicle::DEFAULTS)
                ->map(fn (array $item) => new TransportVehicle($item));
        }

        return view('frontend.transport', [
            'vehicles' => $vehicles,
            'filters' => $filters,
            'brands' => (clone $activeVehicles)->pluck('brand')->filter()->unique()->values(),
            'models' => (clone $activeVehicles)->pluck('model')->filter()->unique()->values(),
            'types' => ['car', 'jeep', 'van', 'bus', 'bike'],
            'typeLabels' => TransportVehicle::TYPE_LABELS,
            'priceOptions' => [5000, 10000, 15000, 25000],
            'featured' => TransportVehicle::query()
                ->active()
                ->where('featured', true)
                ->orderBy('sort_order')
                ->get(),
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

    /**
     * Whether the visitor supplied any listing filter.
     *
     * @param  array<string, mixed>  $filters
     */
    private function hasNoFilters(array $filters): bool
    {
        foreach (['brand', 'model', 'vehicle_type', 'max_price'] as $key) {
            if (filled($filters[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }
}
