<?php

namespace App\Listeners;

use JeroenNoten\LaravelAdminLte\Events\BuildingMenu;

/**
 * Adapts the shared AdminLTE panel to the signed-in role.
 *
 * Both roles render the same panel, so the brand link and the sidebar are the
 * only things that differ. This runs on AdminLTE's menu-building event, which
 * happens once the routes are available and before the sidebar is printed.
 *
 * Menu visibility is only ever a convenience. The real separation is enforced
 * by the 'admin' middleware on every /admin route and by the ownership scoping
 * in the customer controllers.
 */
class ConfigureAdminLteMenu
{
    /**
     * The sidebar items reserved for administrators. Admins keep everything,
     * while a signed-in customer only sees their own section of the panel.
     */
    private const ADMIN_SIDEBAR_KEYS = [
        'menu_search_sidebar',
        'header_main_nav',
        'admin_dashboard',
        'header_booking_mgmt',
        'admin_bookings',
        'admin_payments',
        'header_settings',
        'admin_home',
        'admin_tours',
        'admin_hotels',
        'admin_transport',
        'admin_contact',
        'admin_user_management',
        'admin_settings',
    ];

    /**
     * Point the brand logo at the right dashboard and narrow the sidebar for
     * regular customers.
     */
    public function handle(BuildingMenu $event): void
    {
        $user = auth()->user();

        config([
            'adminlte.dashboard_url' => $user?->isAdmin()
                ? route('admin.dashboard', absolute: false)
                : route('user.dashboard', absolute: false),
        ]);

        if ($user === null || $user->isAdmin()) {
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
                'key' => 'user_notifications',
                'text' => 'Notifications',
                'route' => 'notifications.index',
                'icon' => 'fas fa-bell',
            ],
            [
                'key' => 'user_book_now',
                'text' => 'Book Now',
                'route' => 'booking.search',
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
