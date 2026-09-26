<?php

namespace Modules\Tours\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Home\Models\HomeDestination;
use Modules\Tours\Models\Tour;

class TourController extends Controller
{
    /**
     * Display a listing of the tours.
     */
    public function index(): View
    {
        $tours = Tour::query()
            ->with('destination')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('tours::admin.tours.index', [
            'tours' => $tours,
            'heads' => [
                'Tour',
                'Destination',
                'Duration',
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
                    ['orderable' => false, 'targets' => [5, 6]],
                    ['orderable' => false, 'searchable' => false, 'targets' => 7],
                ],
                'language' => [
                    'search' => 'Search:',
                    'lengthMenu' => 'Show _MENU_ entries',
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
                    'emptyTable' => 'No tours yet. Click "Add Tour" to create the first one.',
                ],
            ],
        ]);
    }

    /**
     * Show the form for creating a new tour.
     */
    public function create(): View
    {
        return view('tours::admin.tours.create', [
            'destinations' => $this->destinationOptions(),
        ]);
    }

    /**
     * Store a newly created tour in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedInput($request);
        $validated['slug'] = $this->resolveSlug($request);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')
                ->store('tours', 'public');
        }

        Tour::query()->create($validated);

        return redirect()
            ->route('admin.tours.index')
            ->with('success', 'Tour created successfully.');
    }

    /**
     * Show the form for editing the specified tour.
     */
    public function edit(Tour $tour): View
    {
        return view('tours::admin.tours.edit', [
            'tour' => $tour,
            'destinations' => $this->destinationOptions(),
        ]);
    }

    /**
     * Update the specified tour in storage.
     */
    public function update(Request $request, Tour $tour): RedirectResponse
    {
        $validated = $this->validatedInput($request, $tour);
        $validated['slug'] = $this->resolveSlug($request, $tour);

        $previousImage = $tour->image;

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')
                ->store('tours', 'public');
        } else {
            unset($validated['image']);
        }

        $tour->fill($validated)->save();

        if (
            isset($validated['image'])
            && $previousImage
            && str_starts_with($previousImage, 'tours/')
            && Storage::disk('public')->exists($previousImage)
        ) {
            Storage::disk('public')->delete($previousImage);
        }

        return redirect()
            ->route('admin.tours.index')
            ->with('success', 'Tour updated successfully.');
    }

    /**
     * Remove the specified tour from storage.
     */
    public function destroy(Tour $tour): RedirectResponse
    {
        if (
            $tour->hasUploadedImage()
            && Storage::disk('public')->exists($tour->image)
        ) {
            Storage::disk('public')->delete($tour->image);
        }

        $tour->delete();

        return redirect()
            ->route('admin.tours.index')
            ->with('success', 'Tour deleted successfully.');
    }

    /**
     * Toggle the active status of the specified tour.
     */
    public function toggleActive(Tour $tour): RedirectResponse
    {
        $tour->update(['is_active' => ! $tour->is_active]);

        return redirect()
            ->route('admin.tours.index')
            ->with('success', $tour->is_active
                ? 'Tour status updated (active).'
                : 'Tour status updated (inactive).');
    }

    /**
     * Toggle the featured status of the specified tour.
     */
    public function toggleFeatured(Tour $tour): RedirectResponse
    {
        $tour->update(['featured' => ! $tour->featured]);

        return redirect()
            ->route('admin.tours.index')
            ->with('success', $tour->featured
                ? 'Tour marked as featured.'
                : 'Tour unmarked as featured.');
    }

    /**
     * The available destination options for the tour form.
     */
    private function destinationOptions()
    {
        return HomeDestination::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Validate the tour request input.
     *
     * @return array<string, mixed>
     */
    private function validatedInput(Request $request, ?Tour $tour = null): array
    {
        $uniqueSlug = Rule::unique('tours', 'slug');

        if ($tour) {
            $uniqueSlug->ignore($tour);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $uniqueSlug],
            'destination_id' => ['nullable', 'integer', 'exists:home_destinations,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'duration' => ['required', 'string', 'max:100'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'price' => ['required', 'numeric', 'min:0'],
            'old_price' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
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
    private function resolveSlug(Request $request, ?Tour $tour = null): string
    {
        $slug = $request->input('slug');

        if (blank($slug)) {
            $slug = Str::slug($request->input('title'));
        }

        $base = $slug;
        $i = 2;

        $query = Tour::query()->where('slug', $slug);

        if ($tour) {
            $query->whereKeyNot($tour->getKey());
        }

        while ($query->exists()) {
            $slug = $base.'-'.$i++;
            $query = Tour::query()->where('slug', $slug);

            if ($tour) {
                $query->whereKeyNot($tour->getKey());
            }
        }

        return $slug;
    }
}
