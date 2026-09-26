<?php

namespace Modules\Hotels\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class HotelsServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Hotels';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'hotels';

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
