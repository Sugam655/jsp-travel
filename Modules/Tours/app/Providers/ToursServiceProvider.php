<?php

namespace Modules\Tours\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class ToursServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Tours';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'tours';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
