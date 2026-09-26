<?php

namespace Modules\Hotels\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Home\Models\HomeDestination;
use Modules\Hotels\Models\Hotel;

class HotelController extends Controller
{
    /**
     * Display a listing of the hotels.
     */
    public function index(): View
    {
        $hotels = Hotel::query()
            ->with('destination')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('hotels::admin.hotels.index', [
            'hotels' => $hotels,
            'heads' => [
                'Hotel',
                'Destination',
                'Rating',
                'Price',
                'Order',
                'Status',
                'Featured',
                ['label' => 'Actions', 'no-export' => true],
            ],
            'config' => [
                'order' => [[4, 'asc'], [0, 'asc']],
                'pageLength' => 10,
                'lengthMenu' => [[5, 10, 25, 50], [5, 10, 25, 50]],
                'autoWidth' => false,
                'layout' => [
                    'topStart' => 'pageLength',
                    'topEnd' => 'search',
                    'bottomStart' => 'info',
                    'bottomEnd' => 'paging',
                ],
                'columnDefs' => [
                    ['type' => 'num-fmt', 'targets' => 3],
                    ['orderable' => false, 'targets' => [2, 5, 6]],
                    ['orderable' => false, 'searchable' => false, 'targets' => 7],
                ],
                'language' => [
                    'search' => 'Search:',
                    'lengthMenu' => 'Show _MENU_ entries',
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
                    'emptyTable' => 'No hotels yet. Click "Add Hotel" to create the first one.',
                ],
            ],
        ]);
    }

    /**
     * Show the form for creating a new hotel.
     */
    public function create(): View
    {
        return view('hotels::admin.hotels.create', [
            'destinations' => $this->destinationOptions(),
        ]);
    }

    /**
     * Store a newly created hotel in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedInput($request);
        $validated['slug'] = $this->resolveSlug($request);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')
                ->store('hotels', 'public');
        }

        Hotel::query()->create($validated);

        return redirect()
            ->route('admin.hotels.index')
            ->with('success', 'Hotel created successfully.');
    }

    /**
     * Show the form for editing the specified hotel.
     */
    public function edit(Hotel $hotel): View
    {
        return view('hotels::admin.hotels.edit', [
            'hotel' => $hotel,
            'destinations' => $this->destinationOptions(),
        ]);
    }

    /**
     * Update the specified hotel in storage.
     */
    public function update(Request $request, Hotel $hotel): RedirectResponse
    {
        $validated = $this->validatedInput($request, $hotel);
        $validated['slug'] = $this->resolveSlug($request, $hotel);

        $previousImage = $hotel->image;

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')
                ->store('hotels', 'public');
        } else {
            unset($validated['image']);
        }

        $hotel->fill($validated)->save();

        if (
            isset($validated['image'])
            && $previousImage
            && str_starts_with($previousImage, 'hotels/')
            && Storage::disk('public')->exists($previousImage)
        ) {
            Storage::disk('public')->delete($previousImage);
        }

        return redirect()
            ->route('admin.hotels.index')
            ->with('success', 'Hotel updated successfully.');
    }

    /**
     * Remove the specified hotel from storage.
     */
    public function destroy(Hotel $hotel): RedirectResponse
    {
        if (
            $hotel->hasUploadedImage()
            && Storage::disk('public')->exists($hotel->image)
        ) {
            Storage::disk('public')->delete($hotel->image);
        }

        $hotel->delete();

        return redirect()
            ->route('admin.hotels.index')
            ->with('success', 'Hotel deleted successfully.');
    }

    /**
     * Toggle the active status of the specified hotel.
     */
    public function toggleActive(Hotel $hotel): RedirectResponse
    {
        $hotel->update(['is_active' => ! $hotel->is_active]);

        return redirect()
            ->route('admin.hotels.index')
            ->with('success', $hotel->is_active
                ? 'Hotel status updated (active).'
                : 'Hotel status updated (inactive).');
    }

    /**
     * Toggle the featured status of the specified hotel.
     */
    public function toggleFeatured(Hotel $hotel): RedirectResponse
    {
        $hotel->update(['featured' => ! $hotel->featured]);

        return redirect()
            ->route('admin.hotels.index')
            ->with('success', $hotel->featured
                ? 'Hotel marked as featured.'
                : 'Hotel unmarked as featured.');
    }

    /**
     * The available destination options for the hotel form.
     */
    private function destinationOptions()
    {
        return HomeDestination::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Validate the hotel request input.
     *
     * @return array<string, mixed>
     */
    private function validatedInput(Request $request, ?Hotel $hotel = null): array
    {
        $uniqueSlug = Rule::unique('hotels', 'slug');

        if ($hotel) {
            $uniqueSlug->ignore($hotel);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $uniqueSlug],
            'destination_id' => ['nullable', 'integer', 'exists:home_destinations,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'price' => ['required', 'numeric', 'min:0'],
            'short_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'featured' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $validated['destination_id'] = $validated['destination_id'] ?? null;
        $validated['featured'] = $request->boolean('featured');
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    /**
     * Resolve a unique slug, auto-generating it from the title when blank.
     */
    private function resolveSlug(Request $request, ?Hotel $hotel = null): string
    {
        $slug = $request->input('slug');

        if (blank($slug)) {
            $slug = Str::slug($request->input('title'));
        }

        $base = $slug;
        $i = 2;

        $query = Hotel::query()->where('slug', $slug);

        if ($hotel) {
            $query->whereKeyNot($hotel->getKey());
        }

        while ($query->exists()) {
            $slug = $base.'-'.$i++;
            $query = Hotel::query()->where('slug', $slug);

            if ($hotel) {
                $query->whereKeyNot($hotel->getKey());
            }
        }

        return $slug;
    }
}
