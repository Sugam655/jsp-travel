<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * The in-memory database the suite is expected to run against.
     */
    private const TEST_DATABASE = ':memory:';

    /**
     * Boot the application and refuse to run against anything but the
     * isolated test environment.
     *
     * A cached configuration file (bootstrap/cache/config.php) makes Laravel
     * skip every environment file, so the suite would silently inherit the
     * developer's real APP_ENV and database. RefreshDatabase would then wipe
     * the development data, so fail loudly instead.
     */
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        $this->assertIsolatedTestEnvironment($app);

        return $app;
    }

    /**
     * Ensure the booted application is safe for RefreshDatabase to migrate.
     */
    private function assertIsolatedTestEnvironment(Application $app): void
    {
        if (! $app->runningUnitTests()) {
            throw new RuntimeException(
                'Refusing to run tests: the application environment is "'
                .$app->environment().'" instead of "testing". A cached config file is almost certainly '
                .'the cause. Run "php artisan config:clear" (and "php artisan route:clear") and retry.'
            );
        }

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== self::TEST_DATABASE) {
            throw new RuntimeException(
                'Refusing to run tests: the default database connection is "'
                .config('database.default').'" ("'.config('database.connections.sqlite.database').'") '
                .'instead of the in-memory SQLite database. Running the suite would destroy the '
                .'development data. Run "php artisan config:clear" and retry.'
            );
        }
    }
}
