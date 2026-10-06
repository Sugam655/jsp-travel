<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JSP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
<link rel="stylesheet" href="{{ asset('style.css') }}">
</head>
<body>

    <!-- Navbar -->
    <nav class="main-navbar" id="mainNavbar">
        <div class="navbar-inner">

            <!-- Logo -->
            <a href="{{ route('home') }}" class="nav-logo" aria-label="JSP Travel home">
                <svg class="nav-logo-mark" viewBox="0 0 150 58" role="img" aria-label="JSP Travel">
                    <text x="0" y="34" fill="#d8a13b" font-family="'Playfair Display', serif" font-size="32"
                        font-weight="700" letter-spacing="1">JSP</text>
                    <text x="2" y="50" fill="#013274" font-family="'Poppins', sans-serif" font-size="10"
                        font-weight="500" letter-spacing="4">TRAVEL</text>
                </svg>
            </a>

            <!-- Desktop Navigation -->
            <ul class="nav-links">
                <li><a href="{{route('home')}}">Home</a></li>
               <li><a href="{{ route('about') }}">About</a></li>
                <li><a href="{{ route('tours.index') }}">Tours</a></li>
                <li><a href="{{ route('hotel') }}">Hotels</a></li>
                <li><a href="{{ route('transport') }}">Car Rental</a></li>
                <li><a href="{{ route('contact.index') }}">Contact</a></li>
                {{-- The public site is browsed as a guest (frontend.guest signs
                     visitors out), so the account entry is the same navbar link as
                     the others and never the panel dashboard or a logout form. --}}
                <li><a href="{{ route('login') }}">Login</a></li>
            </ul>

            <!-- Desktop Button -->
            <a href="{{ route('booking.search') }}" class="nav-cta desktop">
                <span>Book Now</span>
                <i class="fas fa-arrow-right" aria-hidden="true"></i>
            </a>

            <!-- Mobile Toggle -->
            <button type="button" class="nav-toggle" id="navToggle" aria-label="Toggle navigation menu"
                aria-controls="mobileNav" aria-expanded="false">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </nav>

    <script>
        (function () {
            function closeMobileNav() {
                var nav = document.getElementById('mobileNav');
                var toggle = document.getElementById('navToggle');
                if (nav) nav.classList.remove('active');
                if (toggle) {
                    toggle.classList.remove('active');
                    toggle.setAttribute('aria-expanded', 'false');
                }
            }

            window.closeMobileNav = closeMobileNav;

            document.addEventListener('DOMContentLoaded', function () {
                var toggle = document.getElementById('navToggle');
                var nav = document.getElementById('mobileNav');
                if (toggle && nav) {
                    toggle.addEventListener('click', function () {
                        var open = nav.classList.toggle('active');
                        toggle.classList.toggle('active', open);
                        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                    });
                }
            });
        })();
    </script>