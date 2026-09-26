<?php

namespace Modules\Hotels\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Hotels\Models\Hotel;

class HotelController extends Controller
{
    /**
     * Display the public hotels listing with the active hotels, featured
     * first, then ordered by sort order.
     */
    public function index(): View
    {
        $hotels = Hotel::query()
            ->active()
            ->with('destination')
            ->orderByDesc('featured')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($hotels->isEmpty()) {
            $hotels = collect(Hotel::DEFAULTS)->map(fn (array $item) => new Hotel($item));
        }

        return view('frontend.hotel', [
            'hotels' => $hotels,
            'locations' => $hotels
                ->map(fn ($hotel) => $hotel->location_label)
                ->filter()
                ->unique()
                ->values(),
            'priceOptions' => [5000, 10000, 25000, 50000],
        ]);
    }

    /**
     * Display a single active hotel.
     */
    public function show(Hotel $hotel): View
    {
        abort_if(! $hotel->is_active, 404);

        $hotel->loadMissing('destination');

        return view('frontend.hotel-detail', ['hotel' => $hotel]);
    }
}
