<?php

namespace Modules\Home\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Home\Models\HomeDestination;

class DestinationController extends Controller
{
    /**
     * Display a listing of the destinations.
     */
    public function index(): View
    {
        $destinations = HomeDestination::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('home::admin.destinations.index', [
            'destinations' => $destinations,
            'heads' => [
                'Destination',
                'Location',
                'Price',
                'Order',
                'Status',
                ['label' => 'Actions', 'no-export' => true],
            ],
            'config' => [
                'order' => [[3, 'asc'], [0, 'asc']],
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
                    ['orderable' => false, 'targets' => 4],
                    ['orderable' => false, 'searchable' => false, 'targets' => 5],
                ],
                'language' => [
                    'search' => 'Search:',
                    'lengthMenu' => 'Show _MENU_ entries',
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
                    'emptyTable' => 'No destinations yet. Click "Add Destination" to create the first one.',
                ],
            ],
        ]);
    }

    /**
     * Toggle the active status of the specified destination.
     */
    public function toggleActive(HomeDestination $destination): RedirectResponse
    {
        $destination->update(['is_active' => ! $destination->is_active]);

        return redirect()
            ->route('admin.home.destinations.index')
            ->with('success', $destination->is_active
                ? 'Destination activated successfully.'
                : 'Destination deactivated successfully.');
    }

    /**
     * Show the form for creating a new destination.
     */
    public function create(): View
    {
        return view('home::admin.destinations.create');
    }

    /**
     * Store a newly created destination in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedInput($request);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')
                ->store('home/destinations', 'public');
        }

        HomeDestination::query()->create($validated);

        return redirect()
            ->route('admin.home.destinations.index')
            ->with('success', 'Destination created successfully.');
    }

    /**
     * Show the form for editing the specified destination.
     */
    public function edit(HomeDestination $destination): View
    {
        return view('home::admin.destinations.edit', [
            'destination' => $destination,
        ]);
    }

    /**
     * Update the specified destination in storage.
     */
    public function update(Request $request, HomeDestination $destination): RedirectResponse
    {
        $validated = $this->validatedInput($request);

        $previousImage = $destination->image;

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')
                ->store('home/destinations', 'public');
        } else {
            unset($validated['image']);
        }

        $destination->fill($validated)->save();

        if (
            isset($validated['image'])
            && $previousImage
            && ! str_starts_with($previousImage, 'http://')
            && ! str_starts_with($previousImage, 'https://')
            && ! str_starts_with($previousImage, '//')
            && Storage::disk('public')->exists($previousImage)
        ) {
            Storage::disk('public')->delete($previousImage);
        }

        return redirect()
            ->route('admin.home.destinations.index')
            ->with('success', 'Destination updated successfully.');
    }

    /**
     * Remove the specified destination from storage.
     */
    public function destroy(HomeDestination $destination): RedirectResponse
    {
        if (
            $destination->hasUploadedImage()
            && Storage::disk('public')->exists($destination->image)
        ) {
            Storage::disk('public')->delete($destination->image);
        }

        $destination->delete();

        return redirect()
            ->route('admin.home.destinations.index')
            ->with('success', 'Destination deleted successfully.');
    }

    /**
     * Validate the destination request input.
     *
     * @return array<string, mixed>
     */
    private function validatedInput(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'badge' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'string', 'max:50'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
