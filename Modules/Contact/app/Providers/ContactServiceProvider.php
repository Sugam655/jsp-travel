<?php

namespace Modules\Contact\Providers;

use Illuminate\Support\Facades\View;
use Modules\Contact\View\Composers\ContactSettingsComposer;
use Nwidart\Modules\Support\ModuleServiceProvider;

class ContactServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Contact';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'contact';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Boot the module providers and share contact settings with the footer.
     */
    public function boot(): void
    {
        parent::boot();

        View::composer('frontend.layouts.footer', ContactSettingsComposer::class);
    }
}
