<div class="col-md-6 col-lg-3 amc-vehicleitem"
    data-amc-type="{{ $vehicle->vehicle_type }}"
    data-amc-make="{{ strtolower($vehicle->brand ?? '') }}"
    data-amc-model="{{ strtolower($vehicle->model ?? '') }}"
    data-amc-price="{{ (int) $vehicle->price }}">

    <a href="{{ $url ?? route('transport.show', $vehicle) }}" class="text-reset text-decoration-none d-block h-100">

        <div class="amc-vehiclecard">

            <!-- Image -->
            <div class="amc-vehicleimgwrap">

                <img src="{{ $vehicle->image_url }}" alt="{{ $vehicle->name }}">

                @if ($vehicle->year)
                    <span class="amc-vehiclebadge">{{ $vehicle->year }}</span>
                @endif

            </div>

            <!-- Content -->
            <div class="amc-vehicleinfo">

                <!-- Meta -->
                <div class="amc-vehiclemeta">
                    @if ($vehicle->transmission)
                        <span>
                            <i class="bi bi-gear"></i>
                            {{ $vehicle->transmission }}
                        </span>
                    @endif
                    @if ($vehicle->seating_capacity)
                        <span>
                            <i class="bi bi-people"></i>
                            {{ $vehicle->seating_capacity }} Seats
                        </span>
                    @endif
                </div>

                <!-- Name -->
                <h3 class="amc-vehiclename">{{ $vehicle->name }}</h3>

                <!-- Price -->
                <div class="amc-vehicleprice">
                    {{ $vehicle->price_display }}
                </div>

                <!-- Duration / Unit -->
                <div class="amc-vehicleduration">
                    <i class="bi bi-clock"></i>
                    <span>
                        @if ($vehicle->price_unit === 'contact')
                            Price on Request
                        @else
                            {{ $vehicle->price_unit_label }}
                        @endif
                    </span>
                </div>

                <!-- Link -->
                <span class="amc-vehiclelink">
                    View Details
                    <i class="bi bi-chevron-right"></i>
                </span>

            </div>

        </div>

    </a>
</div>