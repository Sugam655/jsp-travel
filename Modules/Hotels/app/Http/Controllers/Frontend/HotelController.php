<?php

namespace Modules\Hotels\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Hotels\Models\Hotel;

class HotelController extends Controller
{
    /**
     * Display the public hotels listing with the active hotels, featured
     * first, then ordered by sort order.
     *
     * The optional query string narrows the listing on the server: `location`
     * matches the hotel location or linked destination name, `rating` matches
     * the star rating exactly, and `max_price` caps the nightly price.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'location' => ['nullable', 'string', 'max:255'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'max_price' => ['nullable', 'integer', 'min:0'],
        ]);

        $hotels = Hotel::query()
            ->active()
            ->with('destination')
            ->when(
                filled($filters['location'] ?? null),
                fn ($query) => $query->where(function ($query) use ($filters) {
                    $query->where('location', $filters['location'])
                        ->orWhereHas('destination', fn ($query) => $query->where('name', $filters['location']));
                })
            )
            ->when(
                filled($filters['rating'] ?? null),
                fn ($query) => $query->where('rating', $filters['rating'])
            )
            ->when(
                filled($filters['max_price'] ?? null),
                fn ($query) => $query->where('price', '<=', $filters['max_price'])
            )
            ->orderByDesc('featured')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($hotels->isEmpty() && $this->hasNoFilters($filters)) {
            $hotels = collect(Hotel::DEFAULTS)->map(fn (array $item) => new Hotel($item));
        }

        return view('frontend.hotel', [
            'hotels' => $hotels,
            'filters' => $filters,
            'locations' => Hotel::query()
                ->active()
                ->pluck('location')
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

    /**
     * Whether the visitor supplied any listing filter.
     *
     * @param  array<string, mixed>  $filters
     */
    private function hasNoFilters(array $filters): bool
    {
        foreach (['location', 'rating', 'max_price'] as $key) {
            if (filled($filters[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }
}
