<?php

namespace App\Listeners;

use JeroenNoten\LaravelAdminLte\Events\BuildingMenu;

class ConfigureUserMenu
{
    /**
     * The sidebar items reserved for administrators. Admins keep everything,
     * while a signed-in customer only sees their own section of the panel.
     */
    private const ADMIN_SIDEBAR_KEYS = [
        'menu_search_sidebar',
        'header_main_nav',
        'admin_dashboard',
        'header_dash',
        'admin_about',
        'header_website_mgmt',
        'admin_home',
        'admin_tours',
        'admin_hotels',
        'admin_transport',
        'admin_bookings',
        'admin_contact',
        'header_system',
        'admin_user_management',
        'admin_reports',
        'admin_settings',
    ];

    /**
     * Rebuild the sidebar for regular customers.
     */
    public function handle(BuildingMenu $event): void
    {
        if (auth()->guest() || auth()->user()->isAdmin()) {
            return;
        }

        foreach (self::ADMIN_SIDEBAR_KEYS as $key) {
            $event->menu->remove($key);
        }

        $event->menu->add(
            ['key' => 'header_user_account', 'header' => 'MY ACCOUNT'],
            [
                'key' => 'user_dashboard',
                'text' => 'Dashboard',
                'route' => 'user.dashboard',
                'icon' => 'fas fa-gauge-high',
            ],
            [
                'key' => 'user_bookings',
                'text' => 'My Bookings',
                'route' => 'bookings.my',
                'icon' => 'fas fa-calendar-check',
            ],
            [
                'key' => 'user_payments',
                'text' => 'My Payments',
                'route' => 'payments.index',
                'icon' => 'fas fa-wallet',
            ],
            [
                'key' => 'user_book_now',
                'text' => 'Book Now',
                'route' => 'bookings.create',
                'icon' => 'fas fa-plus',
            ],
            [
                'key' => 'user_profile',
                'text' => 'My Profile',
                'route' => 'profile.edit',
                'icon' => 'fas fa-user',
            ]
        );
    }
}
