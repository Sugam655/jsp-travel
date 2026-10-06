<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Largest Party Per Booking
    |--------------------------------------------------------------------------
    |
    | The ceiling a single booking request may carry. It bounds the guest,
    | passenger and traveller counters so the frontend cannot offer a party the
    | booking form will refuse, while the per-service capacity rules (a vehicle's
    | seat count, a tour's available seats) still apply on top of it.
    |
    */

    'max_travelers' => (int) env('BOOKING_MAX_TRAVELERS', 100),

];
