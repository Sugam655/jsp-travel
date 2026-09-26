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
            <a href="index.html" class="nav-logo" aria-label="JSP Travel Dashboard">
                <img src="uploads/ChatGPT Image Sep 2, 2026, 03_55_54 PM.png" alt="JSP Travel Logo">
            </a>

            <!-- Desktop Navigation -->
            <ul class="nav-links">
                <li><a href="{{route('home')}}">Home</a></li>
               <li><a href="{{ route('about') }}">About</a></li>
                <li><a href="{{ route('destinations.index') }}">Tours</a></li>
                <li><a href="{{ route('hotel') }}">Hotels</a></li>
                <li><a href="{{ route('transport') }}">Car Rental</a></li>
                <li><a href="{{ route('contact.index') }}">Contact</a></li>
            </ul>

            <!-- Desktop Button -->
            <a href="{{ route('bookings.create') }}" class="nav-cta desktop">
                <span>Book Now</span>
                <i class="fas fa-arrow-right" aria-hidden="true"></i>
            </a>

            <!-- Desktop Account -->
            <div class="nav-auth">
                @auth
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('dashboard') }}">Admin Dashboard</a>
                    @else
                        <a href="{{ route('user.dashboard') }}">My Dashboard</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="nav-auth-logout" aria-label="Logout">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}">Login</a>
                @endauth
            </div>

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