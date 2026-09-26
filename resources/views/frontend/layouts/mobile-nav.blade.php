<div class="mobile-nav" id="mobileNav">
    <a href="{{ route('home') }}" onclick="closeMobileNav()">Home</a>
    <a href="{{ route('about') }}" onclick="closeMobileNav()">About</a>
    <a href="{{ route('destinations.index') }}" onclick="closeMobileNav()">Tours</a>
    <a href="{{ route('hotel') }}" onclick="closeMobileNav()">Hotels</a>
    <a href="{{ route('transport') }}" onclick="closeMobileNav()">Car Rental</a>
    <a href="{{ route('contact.index') }}" onclick="closeMobileNav()">Contact</a>

    <a href="{{ route('bookings.create') }}" class="nav-cta" onclick="closeMobileNav()">
        <span>Book Now</span>
        <i class="fas fa-arrow-right" aria-hidden="true"></i>
    </a>

    <div class="mobile-nav-auth">
        @auth
            @if (auth()->user()->isAdmin())
                <a href="{{ route('dashboard') }}" onclick="closeMobileNav()">Admin Dashboard</a>
            @else
                <a href="{{ route('user.dashboard') }}" onclick="closeMobileNav()">My Dashboard</a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="mobile-auth-logout" onclick="closeMobileNav()">Logout</button>
            </form>
        @else
            <a href="{{ route('login') }}" onclick="closeMobileNav()">Login</a>
        @endauth
    </div>
</div>