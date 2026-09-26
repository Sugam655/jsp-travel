<?php

namespace App\Support;

class Coordinates
{
    /**
     * Resolve coordinates for a place name using the reference lookup,
     * returning an array of ['lat', 'lng'] or null when unknown.
     *
     * @return array{lat: float, lng: float}|null
     */
    public static function fromName(string $name): ?array
    {
        $name = static::normalise($name);

        if ($name === '') {
            return null;
        }

        $reference = require config_path('coordinates.php');

        foreach ($reference as $key => $coordinates) {
            if (str_contains($name, $key)) {
                return $coordinates;
            }
        }

        return null;
    }

    /**
     * Normalise a place name for lookup matching.
     */
    protected static function normalise(string $name): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower($name));
    }
}
