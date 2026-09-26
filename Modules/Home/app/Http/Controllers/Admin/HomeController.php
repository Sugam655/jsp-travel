<?php

namespace Modules\Home\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Home\Models\HomeHero;

class HomeController extends Controller
{
    /**
     * Show the Hero management page.
     */
    public function index(): View
    {
        $hero = HomeHero::query()->latest('id')->first();

        return view('home::admin.index', [
            'hero' => $hero,
            'defaults' => HomeHero::DEFAULTS,
        ]);
    }

    /**
     * Update (or create) the Hero content.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'background_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'button_text' => ['nullable', 'string', 'max:255'],
            'button_url' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $hero = HomeHero::query()->latest('id')->first() ?? new HomeHero;

        $previousImage = $hero->background_image;

        if ($request->hasFile('background_image')) {
            $validated['background_image'] = $request->file('background_image')
                ->store('home/hero', 'public');
        } else {
            unset($validated['background_image']);
        }

        $validated['is_active'] = $request->boolean('is_active');

        $hero->fill($validated)->save();

        if (
            isset($validated['background_image'])
            && $previousImage
            && ! str_starts_with($previousImage, 'http://')
            && ! str_starts_with($previousImage, 'https://')
            && ! str_starts_with($previousImage, '//')
            && Storage::disk('public')->exists($previousImage)
        ) {
            Storage::disk('public')->delete($previousImage);
        }

        return redirect()
            ->route('admin.home.index')
            ->with('success', 'Hero section updated successfully.');
    }
}
