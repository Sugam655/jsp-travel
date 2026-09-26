<?php

namespace Modules\Contact\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Contact\Models\ContactSetting;

class ContactController extends Controller
{
    /**
     * Show the Contact Settings management page.
     */
    public function index(): View
    {
        $settings = ContactSetting::query()->latest('id')->first();

        return view('contact::admin.settings', [
            'settings' => $settings,
            'defaults' => ContactSetting::DEFAULTS,
        ]);
    }

    /**
     * Update (or create) the Contact Settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'page_title' => ['required', 'string', 'max:255'],
            'page_subtitle' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'alternate_phone' => ['nullable', 'string', 'max:50'],
            'landline' => ['nullable', 'string', 'max:50'],
            'whatsapp_number' => ['nullable', 'string', 'max:50'],
            'viber' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'alternate_email' => ['nullable', 'email', 'max:255'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'md_name' => ['nullable', 'string', 'max:255'],
            'opening_hours' => ['nullable', 'string', 'max:255'],
            'map_url' => ['nullable', 'url', 'max:1000'],
            'facebook_url' => ['nullable', 'url', 'max:1000'],
            'instagram_url' => ['nullable', 'url', 'max:1000'],
            'youtube_url' => ['nullable', 'url', 'max:1000'],
            'twitter_url' => ['nullable', 'url', 'max:1000'],
            'contact_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_active' => ['boolean'],
        ]);

        $settings = ContactSetting::query()->latest('id')->first() ?? new ContactSetting;

        $previousImage = $settings->contact_image;

        if ($request->hasFile('contact_image')) {
            $validated['contact_image'] = $request->file('contact_image')
                ->store('contact', 'public');
        } else {
            unset($validated['contact_image']);
        }

        $validated['is_active'] = $request->boolean('is_active');

        $settings->fill($validated)->save();

        if (
            isset($validated['contact_image'])
            && $previousImage
            && ! str_starts_with($previousImage, 'http://')
            && ! str_starts_with($previousImage, 'https://')
            && ! str_starts_with($previousImage, '//')
            && Storage::disk('public')->exists($previousImage)
        ) {
            Storage::disk('public')->delete($previousImage);
        }

        return redirect()
            ->route('admin.contact.index')
            ->with('success', 'Contact settings updated successfully.');
    }
}
