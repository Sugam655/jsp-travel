<?php

namespace Modules\Contact\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Contact\Models\ContactMessage;
use Modules\Contact\Models\ContactSetting;

class ContactController extends Controller
{
    /**
     * Display the frontend Contact page.
     */
    public function index(): View
    {
        $settings = ContactSetting::query()->latest('id')->first();

        return view('contact::frontend.contact', $this->contactData($settings));
    }

    /**
     * Store a new contact message from the frontend form.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        ContactMessage::query()->create($validated + ['status' => 'new']);

        return redirect()
            ->route('contact.index')
            ->with('success', 'Thank you for contacting us. We have received your message and will get back to you soon.');
    }

    /**
     * Merge persisted settings over the defaults for safe rendering.
     *
     * @return array<string, mixed>
     */
    protected function contactData(?ContactSetting $settings): array
    {
        $values = array_merge(
            ContactSetting::DEFAULTS,
            $settings ? array_filter($settings->only(array_keys(ContactSetting::DEFAULTS)), fn ($value) => $value !== null) : []
        );

        $values['contact_image_url'] = $settings?->contact_image_url ?? $values['contact_image'];

        return [
            'settings' => $settings,
            'defaults' => ContactSetting::DEFAULTS,
            'contact' => $values,
        ];
    }
}
