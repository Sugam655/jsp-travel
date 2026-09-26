<div class="col-md-6 col-lg-3 nest-propertyitem"
    data-nest-loc="{{ $hotel->location_label }}"
    data-nest-type="{{ $hotel->rating ?? '' }}"
    data-nest-price="{{ (int) $hotel->price }}">

    <a href="{{ $url ?? route('hotels.show', $hotel) }}" class="text-reset text-decoration-none d-block h-100">
        <div class="nest-propertycard">

            <!-- Image -->
            <div class="nest-propertyimgwrap">

                <img src="{{ $hotel->image_url }}" alt="{{ $hotel->title }}">

                @if ($hotel->featured)
                    <span class="nest-propertybadge">
                        Featured
                    </span>
                @endif

            </div>

            <!-- Content -->
            <div class="nest-propertyinfo">

                <!-- Location -->
                <div class="nest-propertyloc">
                    <i class="bi bi-geo-alt-fill"></i>
                    {{ $hotel->location_label }}
                </div>

                <!-- Title -->
                <h3 class="nest-propertyname">
                    {{ $hotel->title }}
                </h3>

                <!-- Description -->
                @if ($hotel->short_description)
                    <p class="nest-propertydesc">
                        {{ $hotel->short_description }}
                    </p>
                @endif

                <div class="nest-propertymeta">
                    @if ($hotel->rating)
                        <span class="nest-metaitem">
                            <i class="bi bi-star-fill"></i>
                            {{ $hotel->rating }} / 5
                        </span>
                    @endif
                    @if ($hotel->address)
                        <span class="nest-metaitem">
                            <i class="bi bi-building"></i>
                            {{ $hotel->address }}
                        </span>
                    @endif
                </div>

                <!-- Price -->
                <div class="nest-propertyprice">
                    <small>Starting from</small>
                    <strong>Rs.{{ number_format((float) $hotel->price) }} / night</strong>
                </div>

                <!-- Button -->
                <span class="nest-propertybtn">
                    View Hotel
                    <i class="bi bi-arrow-right"></i>
                </span>

            </div>

        </div>
    </a>
</div>