@include('frontend.layouts.header')

@include('frontend.layouts.mobile-nav')

<section class="about bg-white">

    <div class="trvl-breadcrumb-wrap" style="
        background-image:
        linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)),
        url('https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?w=1920&q=85');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
    ">

        <div class="container py-5">
            <div class="row align-items-center">

                <div class="col-md-6 text-center text-md-start">
                    <h1 class="trvl-page-title text-white mb-0">
                        Tour Packages
                    </h1>
                </div>

                <div class="col-md-6">
                    <ul class="trvl-breadcrumb-list
                       d-flex
                       justify-content-center
                       justify-content-md-end
                       align-items-center
                       gap-2
                       list-unstyled
                       mb-0">

                        <li>
                            <a href="{{ route('home') }}" class="text-white text-decoration-none">
                                Home
                            </a>
                        </li>

                        <li class="active text-white-50">
                            Tour Packages
                        </li>

                    </ul>
                </div>
            </div>
        </div>
    </div>

    <section class="Packages">
        <div class="container">
            <div class="sec-hd">
                <h2 class="sec-ttl">
                    Available Tour Packages
                    <span class="wv-ln"></span>
                </h2>
            </div>

            <div class="row g-4">
                @forelse ($tours as $tour)
                    <div class="col-lg-4 col-md-6">
                        @include('frontend.partials.tour-card', ['tour' => $tour])
                    </div>
                @empty
                    <div class="col-12 text-center text-muted">
                        Tour packages are coming soon.
                    </div>
                @endforelse
            </div>
        </div>
    </section>
</section>

@include('frontend.layouts.footer')