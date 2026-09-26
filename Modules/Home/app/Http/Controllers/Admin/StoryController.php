<?php

namespace Modules\Home\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Home\Models\HomeStory;

class StoryController extends Controller
{
    /**
     * Display a listing of the stories.
     */
    public function index(): View
    {
        $stories = HomeStory::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('home::admin.stories.index', [
            'stories' => $stories,
            'heads' => [
                'Author',
                'Trip',
                'Review',
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
                    'emptyTable' => 'No stories yet. Click "Add Story" to create the first one.',
                ],
            ],
        ]);
    }

    /**
     * Toggle the active status of the specified story.
     */
    public function toggleActive(HomeStory $story): RedirectResponse
    {
        $story->update(['is_active' => ! $story->is_active]);

        return redirect()
            ->route('admin.home.stories.index')
            ->with('success', $story->is_active
                ? 'Story activated successfully.'
                : 'Story deactivated successfully.');
    }

    /**
     * Show the form for creating a new story.
     */
    public function create(): View
    {
        return view('home::admin.stories.create');
    }

    /**
     * Store a newly created story in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedInput($request);

        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar')
                ->store('home/stories', 'public');
        }

        HomeStory::query()->create($validated);

        return redirect()
            ->route('admin.home.stories.index')
            ->with('success', 'Story created successfully.');
    }

    /**
     * Show the form for editing the specified story.
     */
    public function edit(HomeStory $story): View
    {
        return view('home::admin.stories.edit', [
            'story' => $story,
        ]);
    }

    /**
     * Update the specified story in storage.
     */
    public function update(Request $request, HomeStory $story): RedirectResponse
    {
        $validated = $this->validatedInput($request);

        $previousAvatar = $story->avatar;

        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar')
                ->store('home/stories', 'public');
        } else {
            unset($validated['avatar']);
        }

        $story->fill($validated)->save();

        if (
            isset($validated['avatar'])
            && $previousAvatar
            && ! str_starts_with($previousAvatar, 'http://')
            && ! str_starts_with($previousAvatar, 'https://')
            && ! str_starts_with($previousAvatar, '//')
            && Storage::disk('public')->exists($previousAvatar)
        ) {
            Storage::disk('public')->delete($previousAvatar);
        }

        return redirect()
            ->route('admin.home.stories.index')
            ->with('success', 'Story updated successfully.');
    }

    /**
     * Remove the specified story from storage.
     */
    public function destroy(HomeStory $story): RedirectResponse
    {
        if (
            $story->hasUploadedAvatar()
            && Storage::disk('public')->exists($story->avatar)
        ) {
            Storage::disk('public')->delete($story->avatar);
        }

        $story->delete();

        return redirect()
            ->route('admin.home.stories.index')
            ->with('success', 'Story deleted successfully.');
    }

    /**
     * Validate the story request input.
     *
     * @return array<string, mixed>
     */
    private function validatedInput(Request $request): array
    {
        $validated = $request->validate([
            'author_name' => ['required', 'string', 'max:255'],
            'trip' => ['nullable', 'string', 'max:255'],
            'review' => ['required', 'string'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
