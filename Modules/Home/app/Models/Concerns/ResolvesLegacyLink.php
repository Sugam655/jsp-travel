<?php

namespace Modules\Home\Models\Concerns;

use Illuminate\Support\Str;

trait ResolvesLegacyLink
{
    /**
     * Pages from the previous static frontend mapped to the route that
     * replaced them.
     *
     * @var array<string, string>
     */
    protected static array $legacyLinkRoutes = [
        'index.html' => 'home',
        'about.html' => 'about',
        'destinations.html' => 'destinations.index',
        'tours.html' => 'tours.index',
        'tour-detail.html' => 'tours.index',
        'hotel.html' => 'hotel',
        'hotel-detail.html' => 'hotel',
        'transport.html' => 'transport',
        'transport-detail.html' => 'transport',
        'contact.html' => 'contact.index',
        'booking.html' => 'booking.search',
    ];

    /**
     * Resolve a stored link value into a working application URL.
     *
     * Links saved by the previous static frontend (for example
     * "destinations.html") are mapped onto the route that replaced them, so
     * admin-managed content keeps working after the move to named routes.
     * External and anchor URLs are returned untouched, and anything
     * unresolvable falls back to the home page rather than a dead link.
     */
    public static function resolveLink(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return route('home');
        }

        if (Str::startsWith($value, ['http://', 'https://', '//', '#'])) {
            return $value;
        }

        $path = '/'.ltrim($value, '/');

        return isset(static::$legacyLinkRoutes[$path])
            ? route(static::$legacyLinkRoutes[$path])
            : route('home');
    }
}
