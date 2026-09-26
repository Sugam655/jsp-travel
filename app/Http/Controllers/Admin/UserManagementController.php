<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;
use Modules\Bookings\Models\Booking;

class UserManagementController extends Controller
{
    /**
     * Display every registered user for administrators. Only non-sensitive
     * profile fields are exposed; password hashes and tokens are never shown.
     */
    public function index(): View
    {
        $users = User::query()
            ->orderByDesc('created_at')
            ->get();

        $bookingCounts = Booking::query()
            ->selectRaw('user_id, COUNT(*) as total')
            ->whereIn('user_id', $users->pluck('id'))
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        return view('admin.users.index', [
            'users' => $users,
            'bookingCounts' => $bookingCounts,
            'heads' => [
                'ID',
                'Name',
                'Email',
                'Phone',
                'Address',
                'City',
                'Country',
                'Bookings',
                'Role',
                'Email Verified',
                'Registered',
                'Actions',
            ],
            'config' => [
                'order' => [[0, 'asc']],
                'pageLength' => 10,
                'lengthMenu' => [[5, 10, 25, 50], [5, 10, 25, 50]],
                'autoWidth' => false,
                'layout' => [
                    'topStart' => 'pageLength',
                    'topEnd' => 'search',
                    'bottomStart' => 'info',
                    'bottomEnd' => 'paging',
                ],
                'columnDefs' => [
                    ['type' => 'num', 'targets' => 0],
                    ['orderable' => false, 'targets' => [8, 9, 11]],
                ],
                'language' => [
                    'search' => 'Search:',
                    'lengthMenu' => 'Show _MENU_ entries',
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
                    'emptyTable' => 'No registered users yet.',
                ],
            ],
        ]);
    }

    /**
     * Display a single user account. This view is intentionally read-only:
     * a user edits their own details from their own profile page, so an
     * administrator can review an account here but never change it.
     */
    public function show(User $user): View
    {
        return view('admin.users.show', [
            'user' => $user,
            'bookingCount' => Booking::query()->where('user_id', $user->getKey())->count(),
            'latestBookings' => Booking::query()
                ->where('user_id', $user->getKey())
                ->latest('id')
                ->limit(5)
                ->get(),
        ]);
    }
}
