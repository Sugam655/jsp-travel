<?php

namespace Modules\Home\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Home\Models\HomeService;

class ServiceController extends Controller
{
    /**
     * Display a listing of the services.
     */
    public function index(): View
    {
        $services = HomeService::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('home::admin.services.index', [
            'services' => $services,
            'heads' => [
                'Service',
                'Button',
                'Order',
                'Status',
                ['label' => 'Actions', 'no-export' => true],
            ],
            'config' => [
                'order' => [[2, 'asc'], [0, 'asc']],
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
                    ['orderable' => false, 'targets' => 3],
                    ['orderable' => false, 'searchable' => false, 'targets' => 4],
                ],
                'language' => [
                    'search' => 'Search:',
                    'lengthMenu' => 'Show _MENU_ entries',
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
                    'emptyTable' => 'No services yet. Click "Add Service" to create the first one.',
                ],
            ],
        ]);
    }

    /**
     * Toggle the active status of the specified service.
     */
    public function toggleActive(HomeService $service): RedirectResponse
    {
        $service->update(['is_active' => ! $service->is_active]);

        return redirect()
            ->route('admin.home.services.index')
            ->with('success', $service->is_active
                ? 'Service activated successfully.'
                : 'Service deactivated successfully.');
    }

    /**
     * Show the form for creating a new service.
     */
    public function create(): View
    {
        return view('home::admin.services.create');
    }

    /**
     * Store a newly created service in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedInput($request);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')
                ->store('home/services', 'public');
        }

        HomeService::query()->create($validated);

        return redirect()
            ->route('admin.home.services.index')
            ->with('success', 'Service created successfully.');
    }

    /**
     * Show the form for editing the specified service.
     */
    public function edit(HomeService $service): View
    {
        return view('home::admin.services.edit', [
            'service' => $service,
        ]);
    }

    /**
     * Update the specified service in storage.
     */
    public function update(Request $request, HomeService $service): RedirectResponse
    {
        $validated = $this->validatedInput($request);

        $previousImage = $service->image;

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')
                ->store('home/services', 'public');
        } else {
            unset($validated['image']);
        }

        $service->fill($validated)->save();

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
            ->route('admin.home.services.index')
            ->with('success', 'Service updated successfully.');
    }

    /**
     * Remove the specified service from storage.
     */
    public function destroy(HomeService $service): RedirectResponse
    {
        if (
            $service->hasUploadedImage()
            && Storage::disk('public')->exists($service->image)
        ) {
            Storage::disk('public')->delete($service->image);
        }

        $service->delete();

        return redirect()
            ->route('admin.home.services.index')
            ->with('success', 'Service deleted successfully.');
    }

    /**
     * Validate the service request input.
     *
     * @return array<string, mixed>
     */
    private function validatedInput(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'button_url' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
