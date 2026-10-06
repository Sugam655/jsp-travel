<div class="mobile-nav" id="mobileNav">
    <a href="{{ route('home') }}" onclick="closeMobileNav()">Home</a>
    <a href="{{ route('about') }}" onclick="closeMobileNav()">About</a>
    <a href="{{ route('tours.index') }}" onclick="closeMobileNav()">Tours</a>
    <a href="{{ route('hotel') }}" onclick="closeMobileNav()">Hotels</a>
    <a href="{{ route('transport') }}" onclick="closeMobileNav()">Car Rental</a>
    <a href="{{ route('contact.index') }}" onclick="closeMobileNav()">Contact</a>

    <a href="{{ route('booking.search') }}" class="nav-cta" onclick="closeMobileNav()">
        <span>Book Now</span>
        <i class="fas fa-arrow-right" aria-hidden="true"></i>
    </a>

    {{-- Same rule as the desktop navbar: the mobile menu is the public one, so it
         offers Login and never the panel dashboard or a logout form. --}}
    <a href="{{ route('login') }}" onclick="closeMobileNav()">Login</a>
</div>