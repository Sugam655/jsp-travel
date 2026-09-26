<?php

namespace Modules\Transport\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Home\Models\HomeDestination;
use Modules\Transport\Models\TransportVehicle;

class TransportVehicleController extends Controller
{
    /**
     * Display a listing of the transport vehicles.
     */
    public function index(): View
    {
        $vehicles = TransportVehicle::query()
            ->with('destination')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('transport::admin.vehicles.index', [
            'vehicles' => $vehicles,
            'heads' => [
                'Vehicle',
                'Type',
                'Destination',
                'Seats',
                'Price',
                'Order',
                'Status',
                'Availability',
                'Featured',
                ['label' => 'Actions', 'no-export' => true],
            ],
            'config' => [
                'order' => [[5, 'asc'], [0, 'asc']],
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
                    ['type' => 'num-fmt', 'targets' => 4],
                    ['orderable' => false, 'targets' => [1, 2, 3, 6, 7, 8]],
                    ['orderable' => false, 'searchable' => false, 'targets' => 9],
                ],
                'language' => [
                    'search' => 'Search:',
                    'lengthMenu' => 'Show _MENU_ entries',
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
                    'emptyTable' => 'No vehicles yet. Click "Add Vehicle" to create the first one.',
                ],
            ],
        ]);
    }

    /**
     * Show the form for creating a new vehicle.
     */
    public function create(): View
    {
        return view('transport::admin.vehicles.create', [
            'destinations' => $this->destinationOptions(),
        ]);
    }

    /**
     * Store a newly created vehicle in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedInput($request);
        $validated['slug'] = $this->resolveSlug($request);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')
                ->store('transport', 'public');
        }

        TransportVehicle::query()->create($validated);

        return redirect()
            ->route('admin.transport.index')
            ->with('success', 'Vehicle created successfully.');
    }

    /**
     * Show the form for editing the specified vehicle.
     */
    public function edit(TransportVehicle $vehicle): View
    {
        return view('transport::admin.vehicles.edit', [
            'vehicle' => $vehicle,
            'destinations' => $this->destinationOptions(),
        ]);
    }

    /**
     * Update the specified vehicle in storage.
     */
    public function update(Request $request, TransportVehicle $vehicle): RedirectResponse
    {
        $validated = $this->validatedInput($request, $vehicle);
        $validated['slug'] = $this->resolveSlug($request, $vehicle);

        $previousImage = $vehicle->image;

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')
                ->store('transport', 'public');
        } else {
            unset($validated['image']);
        }

        $vehicle->fill($validated)->save();

        if (
            isset($validated['image'])
            && $previousImage
            && str_starts_with($previousImage, 'transport/')
            && Storage::disk('public')->exists($previousImage)
        ) {
            Storage::disk('public')->delete($previousImage);
        }

        return redirect()
            ->route('admin.transport.index')
            ->with('success', 'Vehicle updated successfully.');
    }

    /**
     * Remove the specified vehicle from storage.
     */
    public function destroy(TransportVehicle $vehicle): RedirectResponse
    {
        if (
            $vehicle->hasUploadedImage()
            && Storage::disk('public')->exists($vehicle->image)
        ) {
            Storage::disk('public')->delete($vehicle->image);
        }

        $vehicle->delete();

        return redirect()
            ->route('admin.transport.index')
            ->with('success', 'Vehicle deleted successfully.');
    }

    /**
     * Toggle the active status of the specified vehicle.
     */
    public function toggleActive(TransportVehicle $vehicle): RedirectResponse
    {
        $vehicle->update(['is_active' => ! $vehicle->is_active]);

        return redirect()
            ->route('admin.transport.index')
            ->with('success', $vehicle->is_active
                ? 'Vehicle status updated (active).'
                : 'Vehicle status updated (inactive).');
    }

    /**
     * Toggle the featured status of the specified vehicle.
     */
    public function toggleFeatured(TransportVehicle $vehicle): RedirectResponse
    {
        $vehicle->update(['featured' => ! $vehicle->featured]);

        return redirect()
            ->route('admin.transport.index')
            ->with('success', $vehicle->featured
                ? 'Vehicle marked as featured.'
                : 'Vehicle unmarked as featured.');
    }

    /**
     * Toggle the availability of the specified vehicle.
     */
    public function toggleAvailability(TransportVehicle $vehicle): RedirectResponse
    {
        $vehicle->update(['availability' => ! $vehicle->availability]);

        return redirect()
            ->route('admin.transport.index')
            ->with('success', $vehicle->availability
                ? 'Vehicle marked as available.'
                : 'Vehicle marked as unavailable.');
    }

    /**
     * The available destination options for the vehicle form.
     */
    private function destinationOptions()
    {
        return HomeDestination::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Validate the vehicle request input.
     *
     * @return array<string, mixed>
     */
    private function validatedInput(Request $request, ?TransportVehicle $vehicle = null): array
    {
        $uniqueSlug = Rule::unique('transport_vehicles', 'slug');

        if ($vehicle) {
            $uniqueSlug->ignore($vehicle);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $uniqueSlug],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'vehicle_type' => ['required', Rule::in(TransportVehicle::TYPES)],
            'destination_id' => ['nullable', 'integer', 'exists:home_destinations,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'seating_capacity' => ['nullable', 'integer', 'between:1,120'],
            'price' => ['required', 'numeric', 'min:0'],
            'price_unit' => ['required', Rule::in(TransportVehicle::PRICE_UNITS)],
            'year' => ['nullable', 'integer', 'between:1990,2026'],
            'transmission' => ['nullable', 'string', 'max:50'],
            'short_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'features' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'featured' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'availability' => ['boolean'],
        ]);

        $validated['destination_id'] = $validated['destination_id'] ?? null;
        $validated['featured'] = $request->boolean('featured');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['availability'] = $request->boolean('availability');

        return $validated;
    }

    /**
     * Resolve a unique slug, auto-generating it from the name when blank.
     */
    private function resolveSlug(Request $request, ?TransportVehicle $vehicle = null): string
    {
        $slug = $request->input('slug');

        if (blank($slug)) {
            $slug = Str::slug($request->input('name'));
        }

        $base = $slug;
        $i = 2;

        $query = TransportVehicle::query()->where('slug', $slug);

        if ($vehicle) {
            $query->whereKeyNot($vehicle->getKey());
        }

        while ($query->exists()) {
            $slug = $base.'-'.$i++;
            $query = TransportVehicle::query()->where('slug', $slug);

            if ($vehicle) {
                $query->whereKeyNot($vehicle->getKey());
            }
        }

        return $slug;
    }
}
