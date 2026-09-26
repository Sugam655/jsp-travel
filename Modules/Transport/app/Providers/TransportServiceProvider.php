<?php

namespace Modules\Transport\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class TransportServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Transport';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'transport';

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
