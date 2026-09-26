<?php

/*
|--------------------------------------------------------------------------
| Reference Coordinates
|--------------------------------------------------------------------------
|
| Real world latitude/longitude lookup for the destinations, towns and
| park areas used across the JSP Travel website. These are genuine
| coordinates for the actual places, used only as a fallback when a
| destination has not been given explicit coordinates in the database.
|
| Keys are normalised (lowercase, non-alphanumeric characters removed) so
| partial names such as "khaptad", "Khaptad National Park" or "Dhangadhi"
| all resolve to the same marker.
|
*/

return [
    'khaptad' => ['lat' => 29.2755, 'lng' => 81.1440],
    'badimalika' => ['lat' => 29.3436, 'lng' => 81.4228],
    'api' => ['lat' => 29.8497, 'lng' => 80.5331],
    'ramaroshan' => ['lat' => 28.8737, 'lng' => 81.5049],
    'darchula' => ['lat' => 29.8497, 'lng' => 80.5331],
    'achham' => ['lat' => 28.8737, 'lng' => 81.5049],
    'bajura' => ['lat' => 29.3436, 'lng' => 81.4228],
    'dhangadhi' => ['lat' => 28.6917, 'lng' => 80.5970],
    'mahendranagar' => ['lat' => 28.9551, 'lng' => 80.2693],
    'bhimdatta' => ['lat' => 28.9551, 'lng' => 80.2693],
    'attariya' => ['lat' => 28.7895, 'lng' => 80.5537],
    'kailali' => ['lat' => 28.6623, 'lng' => 80.8400],
    'kanchanpur' => ['lat' => 28.8770, 'lng' => 80.4510],
    'shuklaphanta' => ['lat' => 28.7600, 'lng' => 80.2270],
    'farwestern' => ['lat' => 28.9000, 'lng' => 80.9000],
];
