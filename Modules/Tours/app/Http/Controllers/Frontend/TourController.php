<?php

namespace Modules\Tours\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Tours\Models\Tour;

class TourController extends Controller
{
    /**
     * Display the public tours/packages listing with the active tours,
     * featured first, then ordered by sort order.
     */
    public function index(): View
    {
        $tours = Tour::query()
            ->active()
            ->with('destination')
            ->orderByDesc('featured')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($tours->isEmpty()) {
            $tours = collect(Tour::DEFAULTS)->map(fn (array $item) => new Tour($item));
        }

        return view('frontend.tours', ['tours' => $tours]);
    }

    /**
     * Display a single active tour package.
     */
    public function show(Tour $tour): View
    {
        abort_if(! $tour->is_active, 404);

        $tour->loadMissing('destination');

        $relatedTours = Tour::query()
            ->active()
            ->whereKeyNot($tour->getKey())
            ->orderByDesc('featured')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(3)
            ->get();

        return view('frontend.tour-detail', [
            'tour' => $tour,
            'relatedTours' => $relatedTours,
        ]);
    }
}
