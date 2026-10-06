<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Modules\Bookings\Models\Booking;
use Modules\Hotels\Models\Hotel;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['Bookings', 'Tours', 'Hotels', 'Transport'] as $module) {
        Artisan::call('module:migrate', ['module' => $module, '--force' => true]);
    }
    Artisan::call('module:seed', ['module' => 'Tours', '--force' => true]);
    Artisan::call('module:seed', ['module' => 'Hotels', '--force' => true]);
    Artisan::call('module:seed', ['module' => 'Transport', '--force' => true]);
});

/**
 * The JavaScript the browser actually loads. The booking widget logic lives in
 * resources/js/script.js and is copied to public/script.js, so assertions run
 * against the served copy - that is the one that can go stale.
 */
function servedScript(): string
{
    $script = file_get_contents(public_path('script.js'));

    expect($script)->toBe(file_get_contents(resource_path('js/script.js')));

    return $script;
}

function hotelConfirmationUrl(array $query = []): string
{
    return route('bookings.create', array_merge([
        'type' => 'hotel',
        'slug' => Hotel::query()->where('is_active', true)->firstOrFail()->slug,
    ], $query));
}

/**
 * The exact-service confirmation page, which is the only page that carries a
 * date form and the submit button.
 */
function confirmationHtml(): string
{
    return test()->get(hotelConfirmationUrl())->assertOk()->getContent();
}

/**
 * Book a hotel for a signed-in customer through the real endpoint, so the
 * notification under test is the one the workflow actually writes.
 */
function bookHotelFor(User $user, ?Hotel $hotel = null): Booking
{
    $hotel ??= Hotel::query()->firstOrFail();

    test()->actingAs($user)->post('/bookings', [
        'booking_type' => 'hotel',
        'service_id' => $hotel->id,
        'name' => 'Ux Traveller',
        'email' => $user->email,
        'phone' => '9800000000',
        'travelers' => 2,
        'start_date' => now()->addMonth()->toDateString(),
        'end_date' => now()->addMonth()->addDays(2)->toDateString(),
        'policy_accepted' => '1',
    ])->assertRedirect();

    return $user->bookings()->latest('id')->firstOrFail();
}

/*
|--------------------------------------------------------------------------
| Editable end date
|--------------------------------------------------------------------------
*/

test('every date field on the confirmation form is editable', function () {
    $html = confirmationHtml();

    preg_match_all('/<input\b[^>]*type="date"[^>]*>/i', $html, $matches);

    expect($matches[0])->not->toBeEmpty();

    foreach ($matches[0] as $input) {
        expect($input)->not->toMatch('/\sdisabled\b/i')
            ->and($input)->not->toMatch('/\breadonly\b/i');
    }
});

test('the booking script never disables the end date field', function () {
    $script = servedScript();

    expect($script)->not->toMatch('/end\w*\.disabled\s*=/')
        ->and($script)->not->toMatch('/end\w*\.readOnly\s*=/')
        ->and($script)->not->toMatch('/end\w*\.setAttribute\(\s*.disabled/i')
        ->and($script)->not->toMatch('/end\w*\.toggleAttribute\(\s*.disabled/i');
});

test('the end date keeps its own change handler so it can be revised repeatedly', function () {
    $script = servedScript();

    // One listener is attached to both pickers and neither is removed, so the
    // end date can be changed as often as the customer likes.
    expect($script)->toContain('[visibleStart, visibleEnd].forEach(function (input) {')
        ->and($script)->toContain('input.addEventListener("change", function () {')
        ->and($script)->toContain('scheduleQuote();')
        ->and(confirmationHtml())->toContain('data-sync="end_date"');
});

// Which controls are required on which booking type, and the pick-up/drop-off
// floor itself, are covered by tests/Feature/BookingDateLogicTest.php against
// the rendered markup rather than by matching JavaScript source here.

test('changing the start date raises the end date floor instead of freezing it', function () {
    $script = servedScript();

    // The floor follows the check-in, and drops back to today when it is cleared.
    expect($script)->toContain('endInput.dataset.todayFloor = endInput.min || "";')
        ->and($script)->toContain('floor && floor > todayFloor ? floor : todayFloor');
});

test('an end date before the start date is reported rather than rewritten onto it', function () {
    $script = servedScript();

    // Silently rewriting a check-out onto the check-in is what made the field
    // look stuck, so the value the customer chose is kept and the conflict is
    // written next to the field instead.
    expect($script)->not->toMatch('/end\w*\.value\s*=\s*start/')
        ->and($script)->toContain('setPeriodError(scope, "end_date", endError);')
        ->and($script)->toContain('node.classList.toggle("is-visible", Boolean(message));');
});

test('the search page keeps one date pair per booking type', function () {
    $search = $this->get('/book')->assertOk()->getContent();

    // A stay and a rental each need a start and an end; a tour needs only the
    // date it leaves on.
    foreach (['searchHotelStart', 'searchHotelEnd', 'searchVehicleStart', 'searchVehicleEnd', 'searchTourStart'] as $id) {
        expect($search)->toContain('id="'.$id.'"', false);
    }

    expect($search)->not->toContain('id="searchTourEnd"', false);
});

test('the confirmation page shows the period next to the chosen service and posts the canonical pair', function () {
    $html = confirmationHtml();

    expect($html)->toContain('id="start_date_field"', false)
        ->and($html)->toContain('id="end_date_field"', false)
        ->and($html)->toMatch('/name="start_date" id="start_date"/')
        ->and($html)->toMatch('/name="end_date" id="end_date"/')
        ->and($html)->toMatch('/name="travelers" id="travelers"/');
});

/*
|--------------------------------------------------------------------------
| The redundant block is gone, the required form is not
|--------------------------------------------------------------------------
*/

test('the booking pages no longer repeat the customer booking history', function () {
    $user = User::factory()->create();
    $booking = bookHotelFor($user);

    // Neither page carries a history block: the customer came here to book, not
    // to re-read what they already booked. The reference itself stays on the
    // page through the notification bell, which is the one place it belongs.
    foreach (['/book', hotelConfirmationUrl()] as $url) {
        $this->actingAs($user)->get($url)
            ->assertOk()
            ->assertDontSee('You have no bookings yet')
            ->assertDontSee('My Bookings</h3>')
            ->assertDontSee('View your bookings');
    }

    // Sanity check: removing the block changed no booking data, and the history
    // is still where the customer manages it.
    expect($booking->fresh()->booking_reference)->not->toBeEmpty();

    $this->actingAs($user)->get(route('bookings.my'))->assertOk()->assertSee($booking->booking_reference);
});

test('the live price summary survives on the confirmation page', function () {
    expect(confirmationHtml())
        ->toContain('id="fh-quoteSection"', false)
        ->toContain('id="fh-quoteService"', false)
        ->toContain('id="fh-quoteTotal"', false)
        ->toContain('data-quote-url="'.route('bookings.quote').'"', false);
});

test('the required booking form and its submit button are still on the page', function () {
    $html = confirmationHtml();

    expect($html)->toContain('name="name"')
        ->toContain('name="email"')
        ->toContain('name="phone"')
        ->toContain('name="policy_accepted"')
        ->toContain('type="submit"')
        ->toContain('id="bookingForm"', false)
        ->toContain('id="fh-quoteSection"', false)
        ->toContain('id="fh-quoteTotal"', false)
        ->toContain(route('bookings.store'));
});

/*
|--------------------------------------------------------------------------
| Search result popup
|--------------------------------------------------------------------------
*/

test('the search popup is wired before the confirmation form gives up', function () {
    $script = servedScript();

    // The popup lives on the search page and the confirmation form does not, so the
    // popup's wiring has to be reached on a page that has no booking form. Pin the
    // order so it cannot drift back behind that guard and leave the shell unwired.
    $popup = strpos($script, 'fh-bookingSummaryModal');
    $confirmationOnly = strpos($script, 'if (!confirmForm)');

    expect($popup)->not->toBeFalse()
        ->and($confirmationOnly)->not->toBeFalse()
        ->and($popup)->toBeLessThan($confirmationOnly);
});

test('a result with nothing to show is never painted as an empty popup', function () {
    $script = servedScript();

    // Bootstrap ignores hide() while a modal is still showing, so dismissing it that
    // way and then navigating leaves the empty shell on screen for the whole of the
    // next page's load. Cancel the show instead: either the summary is there and the
    // popup fills, or nothing is opened and the link is simply followed.
    $branch = strstr($script, 'if (!summary) {', false);

    expect($branch)->not->toBeFalse();

    $branch = substr($branch, 0, strpos($branch, 'return;'));

    expect($branch)
        ->toContain('event.preventDefault()', false)
        ->toContain('window.location.assign(trigger.href)', false)
        // Neither of the ways this used to be "closed" while it was still opening.
        ->not->toContain('.hide()', false)
        ->not->toContain('data-bs-dismiss', false)
        ->not->toContain('location.href =', false);
});

test('the served script is versioned, so a rebuilt popup cannot be masked by a cached copy', function () {
    $html = $this->get(route('booking.search', ['type' => 'hotel']))->assertOk()->getContent();

    expect($html)
        ->toContain('script.js?v=', false)
        ->and($html)->not->toContain('src="'.asset('script.js').'"', false);
});

/*
|--------------------------------------------------------------------------
| Navbar cleanup
|--------------------------------------------------------------------------
*/

test('the public navbar never carries dashboard, bookings or notifications links', function () {
    $html = $this->actingAs(User::factory()->create())->get(route('home'))->assertOk()->getContent();

    expect($html)->not->toContain('>My Dashboard</a>')
        ->not->toContain('>My Bookings</a>')
        ->not->toContain('>Notifications</a>');
});

test('a guest navbar is unchanged and carries no notification bell', function () {
    $html = $this->get(route('home'))->assertOk()->getContent();

    expect($html)->toContain('>Login</a>')
        ->not->toContain('nav-notification-bell')
        ->not->toContain('>My Dashboard</a>');
});

test('an admin gets no dashboard link on the public site', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $html = $this->actingAs($admin)->get(route('home'))->assertOk()->getContent();

    expect($html)->not->toContain('Admin Dashboard')
        ->not->toContain('nav-notification-bell');

    $this->assertGuest();
});

test('the pages the navbar stopped linking to are still reachable', function () {
    $user = User::factory()->create();
    $booking = bookHotelFor($user);

    $this->actingAs($user)->get(route('bookings.my'))->assertOk();
    $this->actingAs($user)->get(route('user.dashboard'))->assertOk();
    $this->actingAs($user)->get(route('notifications.index'))->assertOk();
    $this->actingAs($user)->get(route('bookings.show', $booking->booking_reference))->assertOk();
});

/*
|--------------------------------------------------------------------------
| One notification, the real reference, persisted read state
|--------------------------------------------------------------------------
*/

test('booking a service raises exactly one customer notification carrying the real reference', function () {
    $user = User::factory()->create();
    $booking = bookHotelFor($user);

    $notification = $user->notifications()->firstOrFail();

    expect($user->notifications()->count())->toBe(1)
        ->and($notification->data['title'])->toBe('Booking received')
        ->and($notification->data['reference'])->toBe($booking->booking_reference)
        ->and($notification->data['message'])->toContain($booking->booking_reference)
        ->and($notification->data['url'])->toBe(route('bookings.show', $booking->booking_reference));
});

test('the notification centre lists the unread booking update and its reference', function () {
    $user = User::factory()->create();
    $booking = bookHotelFor($user);
    $notification = $user->notifications()->firstOrFail();

    // The public navbar is a guest interface, so booking updates live in the
    // panel's notification centre.
    $html = $this->actingAs($user)->get(route('notifications.index'))->assertOk()->getContent();

    expect($html)->toContain('Booking received')
        ->toContain($booking->booking_reference)
        ->toContain(route('notifications.read', $notification->getKey()))
        ->toContain(route('notifications.read-all'));
});

test('reading a booking update is persisted, so a reload no longer counts it as unread', function () {
    $user = User::factory()->create();
    $booking = bookHotelFor($user);
    $notification = $user->notifications()->firstOrFail();

    $this->actingAs($user)->get(route('notifications.read', $notification->id))
        ->assertRedirect(route('bookings.show', $booking->booking_reference));

    $html = $this->actingAs($user)->get(route('notifications.index'))->assertOk()->getContent();

    // Read state lives on the notification row, so it survives a reload and the
    // centre stops offering the mark-all action.
    expect($notification->fresh()->read_at)->not->toBeNull()
        ->and($user->fresh()->unreadNotifications()->count())->toBe(0)
        ->and($html)->not->toContain('Mark all as read');
});

test('mark all read clears every booking update in one action', function () {
    $user = User::factory()->create();

    // Two different hotels: the same one cannot be booked twice for the same
    // dates, so re-booking it would raise no second notification.
    $hotels = Hotel::query()->take(2)->get();
    expect($hotels)->toHaveCount(2);

    bookHotelFor($user, $hotels[0]);
    bookHotelFor($user, $hotels[1]);

    expect($user->unreadNotifications()->count())->toBe(2);

    $this->actingAs($user)->get(route('notifications.read-all'))
        ->assertRedirect(route('notifications.index'));

    $html = $this->actingAs($user)->get(route('notifications.index'))->assertOk()->getContent();

    expect($user->fresh()->unreadNotifications()->count())->toBe(0)
        ->and($html)->not->toContain('Mark all as read');
});

test('a customer only ever sees their own booking updates', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    bookHotelFor($owner);

    $html = $this->actingAs($other)->get(route('notifications.index'))->assertOk()->getContent();

    expect($other->unreadNotifications()->count())->toBe(0)
        ->and($html)->toContain('No notifications found');
});

test('the notification bell styles are present in the served stylesheet', function () {
    $css = file_get_contents(public_path('style.css'));

    expect($css)->toBe(file_get_contents(resource_path('css/style.css')))
        ->and($css)->toContain('.nav-notification-bell')
        ->and($css)->toContain('.nav-notification-menu')
        ->and($css)->toContain('.nav-notification-badge');
});

test('the booking notification names the service and reference that were actually booked', function () {
    $user = User::factory()->create();
    $booking = bookHotelFor($user);

    $notification = $user->notifications()->firstOrFail();

    // Built from the row that was written rather than from anything written into
    // the template, so the bell can only ever name what this customer booked.
    expect($notification->data['message'])
        ->toContain($booking->service_title)
        ->toContain($booking->booking_reference)
        ->toContain('awaiting confirmation')
        ->and($notification->data['url'])->toBe(route('bookings.show', $booking->booking_reference));

    // A booking is listed under a booking icon in both notification views.
    $dropdown = $this->actingAs($user)->getJson(route('notifications.data'))->json('dropdown');
    expect($dropdown)->toContain('fa-calendar-check')
        ->and($dropdown)->toContain($booking->service_title);

    $centre = $this->actingAs($user)->get(route('notifications.index'))->assertOk()->getContent();
    expect($centre)->toContain('fa-calendar-check');
});

test('the administrator is notified of a new booking request inside the AdminLTE panel', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();

    $booking = bookHotelFor($user);

    $notification = $admin->notifications()->firstOrFail();

    expect($notification->data['title'])->toBe('New booking request')
        ->and($notification->data['message'])->toContain($booking->service_title)
        ->and($notification->data['message'])->toContain($booking->booking_reference)
        // Management links stay in the admin panel; a customer link would be wrong.
        ->and($notification->data['url'])->toBe(route('admin.bookings.show', $booking));

    // An unread item is clicked through the read route, which marks it and then
    // opens its destination - so the link in the bell is the read route, and
    // following it has to land inside the admin panel.
    $dropdown = $this->actingAs($admin)->getJson(route('notifications.data'))->json('dropdown');
    expect($dropdown)->toContain('New booking request')
        ->and($dropdown)->toContain(route('notifications.read', $notification->getKey()));

    $this->actingAs($admin)
        ->get(route('notifications.read', $notification->getKey()))
        ->assertRedirect(route('admin.bookings.show', $booking));

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('the booking confirmation toast is shown once and a refresh does not bring it back', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->where('is_active', true)->firstOrFail();

    // The confirmation is carried as ordinary session flash data on the redirect -
    // not as a database row - so it reaches the one request that follows and no
    // other. The reference in it is the persisted booking's own.
    $this->actingAs($user)->post('/bookings', [
        'booking_type' => 'hotel',
        'service_id' => $hotel->id,
        'name' => 'Ux Traveller',
        'email' => $user->email,
        'phone' => '9800000000',
        'travelers' => 2,
        'start_date' => now()->addMonth()->toDateString(),
        'end_date' => now()->addMonth()->addDays(2)->toDateString(),
        'policy_accepted' => '1',
    ])->assertRedirect()
        ->assertSessionHas('success');

    $booking = Booking::query()->where('user_id', $user->id)->firstOrFail();

    expect(session('success'))->toContain($booking->booking_reference);

    // The destination shows it as a toast that closes itself, quoting the reference
    // that was actually persisted, and leaves no alert behind in the content.
    $landing = $this->actingAs($user)->get(route('bookings.show', $booking->booking_reference))->assertOk();
    $landing->assertSee('Booking submitted successfully')
        ->assertSee('toast: true', false)
        ->assertDontSee('alert alert-success alert-dismissible');

    // A refresh is simply the next request, and the flash is spent by then.
    $refresh = $this->actingAs($user)->get(route('bookings.show', $booking->booking_reference))->assertOk();
    $refresh->assertDontSee('Booking submitted successfully')
        ->assertDontSee('alert alert-success alert-dismissible');

    // The dashboard treats its own flash the same way: toast only, nothing left
    // sitting in the page.
    $dashboard = $this->actingAs($user)->get(route('user.dashboard'))->assertOk();
    $dashboard->assertDontSee('alert alert-success alert-dismissible');

    // The reference is the booking's own, never a fixed string, and it is what both
    // the bell and My Bookings point at.
    expect($booking->booking_reference)->toStartWith('BK-')
        ->and($user->notifications()->firstOrFail()->data['message'])
        ->toContain($booking->booking_reference);

    // The bell is untouched by any of this: the booking update is a separate,
    // persistent notification, so it is still unread both before and after the
    // refresh that spent the toast.
    expect($user->notifications()->firstOrFail()->read_at)->toBeNull();

    $this->actingAs($user)->get(route('user.dashboard'))->assertOk();

    expect($user->notifications()->firstOrFail()->read_at)->toBeNull();
});

test('a freshly submitted booking is confirmed by one small toast and one AdminLTE card', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->where('is_active', true)->firstOrFail();

    $this->actingAs($user)->post('/bookings', [
        'booking_type' => 'hotel',
        'service_id' => $hotel->id,
        'name' => 'Ux Traveller',
        'email' => $user->email,
        'phone' => '9800000000',
        'travelers' => 2,
        'start_date' => now()->addMonth()->toDateString(),
        'end_date' => now()->addMonth()->addDays(2)->toDateString(),
        'policy_accepted' => '1',
    ])->assertRedirect();

    $booking = Booking::query()->where('user_id', $user->id)->firstOrFail();

    $html = $this->actingAs($user)
        ->get(route('bookings.show', $booking->booking_reference))
        ->assertOk()
        ->getContent();

    // One brief confirmation of the write, quoting the reference that was actually
    // persisted - and it is the panel's toast, not an alert left in the content. An
    // alert sits in the page until it is clicked away, which is exactly the permanent
    // message this must not be.
    expect($html)
        ->toContain('Swal.fire(', false)
        ->toContain('toast: true', false)
        ->toContain('Your booking reference is '.$booking->booking_reference)
        ->not->toContain('alert alert-success alert-dismissible')
        ->not->toContain('alert-heading');

    // The card describing the booking itself is untouched: it is the persistent half
    // of the confirmation, not a second rendering of the flash sentence.
    expect($html)->toContain('Booking request received')
        ->toContain('Your service is currently held.')
        ->toContain($booking->status_label)
        ->not->toContain('Your service is held and we will notify you of the next step.');

    // Still the user AdminLTE panel: no frontend shell, no standalone success
    // page, and no second booking form to fill in.
    expect($html)->toContain('id="adminlte-sidebar-menu"', false)
        ->and($html)->not->toContain('id="mainNavbar"', false)
        ->and($html)->not->toContain('id="jsp-footer-main"', false)
        ->and($html)->not->toContain('name="policy_accepted"', false);
});
