<?php

namespace Modules\Contact\View\Composers;

use Illuminate\View\View;
use Modules\Contact\Models\ContactSetting;

class ContactSettingsComposer
{
    /**
     * Share the active contact settings with the shared frontend footer.
     */
    public function compose(View $view): void
    {
        $settings = ContactSetting::query()->latest('id')->first();

        $values = array_merge(
            ContactSetting::DEFAULTS,
            $settings ? array_filter($settings->only(array_keys(ContactSetting::DEFAULTS)), fn ($value) => $value !== null) : []
        );

        $values['contact_image_url'] = $settings?->contact_image_url ?? $values['contact_image'];

        $view->with('contactSummary', $values);
    }
}
