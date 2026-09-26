<?php

namespace Modules\Home\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Home\Models\HomeDestination;
use Modules\Home\Models\HomeHero;
use Modules\Home\Models\HomeService;
use Modules\Home\Models\HomeStory;
use Modules\Home\Models\HomeWhyChooseUs;
use Modules\Tours\Models\Tour;

class HomeController extends Controller
{
    /**
     * Display the frontend homepage with its active Hero, destinations,
     * Why Choose Us, stories and services content.
     */
    public function index(): View
    {
        $hero = HomeHero::query()
            ->where('is_active', true)
            ->latest('id')
            ->first();

        $destinations = HomeDestination::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($destinations->isEmpty()) {
            $destinations = collect(HomeDestination::DEFAULTS)
                ->map(fn (array $item) => new HomeDestination($item));
        }

        $whyChooseUs = HomeWhyChooseUs::query()
            ->where('is_active', true)
            ->latest('id')
            ->first();

        $stories = HomeStory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($stories->isEmpty()) {
            $stories = collect(HomeStory::DEFAULTS)
                ->map(fn (array $item) => new HomeStory($item));
        }

        $services = HomeService::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($services->isEmpty()) {
            $services = collect(HomeService::DEFAULTS)
                ->map(fn (array $item) => new HomeService($item));
        }

        return view('welcome', [
            'hero' => $hero,
            'defaults' => HomeHero::DEFAULTS,
            'destinations' => $destinations,
            'whyChooseUs' => $whyChooseUs,
            'whyChooseUsDefaults' => HomeWhyChooseUs::DEFAULTS,
            'stories' => $stories,
            'services' => $services,
        ]);
    }

    /**
     * Display every active destination on the dedicated destinations page.
     */
    public function destinations(): View
    {
        $destinations = HomeDestination::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($destinations->isEmpty()) {
            $destinations = collect(HomeDestination::DEFAULTS)
                ->map(fn (array $item) => new HomeDestination($item));
        }

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

        return view('frontend.destinations', [
            'destinations' => $destinations,
            'tours' => $tours,
        ]);
    }
}
