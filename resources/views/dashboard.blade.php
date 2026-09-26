@extends('adminlte::page')

@section('title', 'Dashboard')

@section('plugins.Chartjs', true)
@section('plugins.Leaflet', true)

@section('content_header')
    <h1>Dashboard</h1>
@stop

@section('content')
    {{-- ============ OVERVIEW CARDS ============ --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            <a href="{{ route('admin.bookings.index') }}" class="text-decoration-none">
                <div class="small-box text-bg-primary">
                    <div class="inner">
                        <h3>{{ number_format($overview['bookings_total']) }}</h3>
                        <p>Total Bookings</p>
                    </div>
                    <div class="small-box-icon"><i class="fas fa-calendar-check"></i></div>
                    <span class="small-box-footer">View all bookings <i class="fas fa-arrow-circle-right"></i></span>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-6">
            <a href="{{ route('admin.bookings.index', ['status' => 'pending']) }}" class="text-decoration-none">
                <div class="small-box text-bg-warning">
                    <div class="inner">
                        <h3>{{ number_format($overview['bookings_pending']) }}</h3>
                        <p>Pending Bookings</p>
                    </div>
                    <div class="small-box-icon"><i class="fas fa-hourglass-half"></i></div>
                    <span class="small-box-footer">Review pending <i class="fas fa-arrow-circle-right"></i></span>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-6">
            <a href="{{ route('admin.bookings.index', ['status' => 'confirmed']) }}" class="text-decoration-none">
                <div class="small-box text-bg-info">
                    <div class="inner">
                        <h3>{{ number_format($overview['bookings_confirmed']) }}</h3>
                        <p>Confirmed Bookings</p>
                    </div>
                    <div class="small-box-icon"><i class="fas fa-check-circle"></i></div>
                    <span class="small-box-footer">Confirmed list <i class="fas fa-arrow-circle-right"></i></span>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-6">
            <a href="{{ route('admin.contact.messages.index') }}" class="text-decoration-none">
                <div class="small-box text-bg-danger">
                    <div class="inner">
                        <h3>{{ number_format($overview['messages_unread']) }}</h3>
                        <p>Unread Messages</p>
                    </div>
                    <div class="small-box-icon"><i class="fas fa-envelope"></i></div>
                    <span class="small-box-footer">Open inbox <i class="fas fa-arrow-circle-right"></i></span>
                </div>
            </a>
        </div>
    </div>

    {{-- ============ BOOKING WORKFLOW + MONEY CARDS ============ --}}
    @php
        $currency = \Modules\Bookings\Models\BookingSetting::getWithDefault('currency');
        $money = fn ($value) => $currency.' '.number_format((float) $value);
    @endphp
    <div class="row mt-2">
        <div class="col-lg-3 col-6">
            <a href="{{ route('admin.bookings.payments.index', ['status' => 'pending']) }}" class="text-decoration-none">
                <div class="small-box text-bg-primary">
                    <div class="inner">
                        <h3>{{ number_format($overview['payments_pending']) }}</h3>
                        <p>Payments Pending Verification</p>
                    </div>
                    <div class="small-box-icon"><i class="fas fa-money-bill-wave"></i></div>
                    <span class="small-box-footer">Review payments <i class="fas fa-arrow-circle-right"></i></span>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-6">
            <a href="{{ route('admin.bookings.index', ['status' => 'paid']) }}" class="text-decoration-none">
                <div class="small-box text-bg-success">
                    <div class="inner">
                        <h3>{{ number_format($overview['bookings_paid']) }}</h3>
                        <p>Paid Bookings</p>
                    </div>
                    <div class="small-box-icon"><i class="fas fa-circle-check"></i></div>
                    <span class="small-box-footer">Paid list <i class="fas fa-arrow-circle-right"></i></span>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-6">
            <a href="{{ route('admin.bookings.index', ['status' => 'cancelled']) }}" class="text-decoration-none">
                <div class="small-box text-bg-secondary">
                    <div class="inner">
                        <h3>{{ number_format($overview['bookings_cancelled']) }}</h3>
                        <p>Cancelled</p>
                    </div>
                    <div class="small-box-icon"><i class="fas fa-ban"></i></div>
                    <span class="small-box-footer">Cancelled list <i class="fas fa-arrow-circle-right"></i></span>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-6">
            <a href="{{ route('admin.bookings.index', ['status' => 'completed']) }}" class="text-decoration-none">
                <div class="small-box text-bg-dark">
                    <div class="inner">
                        <h3>{{ number_format($overview['bookings_completed']) }}</h3>
                        <p>Completed</p>
                    </div>
                    <div class="small-box-icon"><i class="fas fa-flag-checkered"></i></div>
                    <span class="small-box-footer">Completed trips <i class="fas fa-arrow-circle-right"></i></span>
                </div>
            </a>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-success">
                <div class="inner">
                    <h3 class="fs-5">{{ $money($paymentStats['collected']) }}</h3>
                    <p>Revenue Collected</p>
                </div>
                <div class="small-box-icon"><i class="fas fa-sack-dollar"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-warning">
                <div class="inner">
                    <h3 class="fs-5">{{ $money($paymentStats['outstanding']) }}</h3>
                    <p>Outstanding Payments</p>
                </div>
                <div class="small-box-icon"><i class="fas fa-hourglass-half"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-info">
                <div class="inner">
                    <h3 class="fs-5">{{ $money($paymentStats['pending_refunds']) }}</h3>
                    <p>Pending Refunds</p>
                </div>
                <div class="small-box-icon"><i class="fas fa-rotate-left"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-primary">
                <div class="inner">
                    <h3 class="fs-5">{{ $money($paymentStats['net']) }}</h3>
                    <p>Net Revenue</p>
                </div>
                <div class="small-box-icon"><i class="fas fa-scale-balanced"></i></div>
            </div>
        </div>
    </div>

    {{-- ============ MODULE OVERVIEW CARDS ============ --}}
    <div class="row mt-2">
        <div class="col-lg-3 col-6">
            <a href="{{ route('admin.tours.index') }}" class="text-decoration-none">
                <div class="small-box text-bg-success">
                    <div class="inner">
                        <h3>{{ number_format($overview['tours_active']) }} <small>/ {{ number_format($overview['tours_total']) }}</small></h3>
                        <p>Tours <small class="text-white-50">(active / total)</small></p>
                    </div>
                    <div class="small-box-icon"><i class="fas fa-route"></i></div>
                    <span class="small-box-footer">Manage tours <i class="fas fa-arrow-circle-right"></i></span>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-6">
            <a href="{{ route('admin.hotels.index') }}" class="text-decoration-none">
                <div class="small-box text-bg-secondary">
                    <div class="inner">
                        <h3>{{ number_format($overview['hotels_active']) }} <small>/ {{ number_format($overview['hotels_total']) }}</small></h3>
                        <p>Hotels <small>(active / total)</small></p>
                    </div>
                    <div class="small-box-icon"><i class="fas fa-hotel"></i></div>
                    <span class="small-box-footer">Manage hotels <i class="fas fa-arrow-circle-right"></i></span>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-6">
            <a href="{{ route('admin.transport.index') }}" class="text-decoration-none">
                <div class="small-box text-bg-dark">
                    <div class="inner">
                        <h3>{{ number_format($overview['vehicles_available']) }} <small>/ {{ number_format($overview['vehicles_total']) }}</small></h3>
                        <p>Vehicles <small>(available / total)</small></p>
                    </div>
                    <div class="small-box-icon"><i class="fas fa-car"></i></div>
                    <span class="small-box-footer">Manage fleet <i class="fas fa-arrow-circle-right"></i></span>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-light border">
                <div class="inner">
                    <h3 class="text-dark">{{ number_format($overview['users_total']) }}</h3>
                    <p class="text-muted">Registered Users</p>
                </div>
                <div class="small-box-icon text-muted"><i class="fas fa-users"></i></div>
            </div>
        </div>
    </div>

    {{-- ============ BOOKINGS OVERVIEW CHART ============ --}}
    <div class="row mt-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h3 class="card-title"><i class="fas fa-chart-line me-1"></i> Bookings Overview</h3>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="text-muted small me-1">Total in period:</span>
                        <strong id="bookingsPeriodTotal">{{ $bookingsSeries['bookings_total'] }}</strong>
                        <select id="rangeSelect" class="form-select form-select-sm" style="max-width:160px;">
                            @foreach ([7, 30, 90, 180, 365] as $days)
                                <option value="{{ $days }}" {{ $range === $days ? 'selected' : '' }}>
                                    Last {{ $days }} days
                                </option>
                            @endforeach
                        </select>
                        <input type="date" id="fromDate" class="form-control form-control-sm" style="max-width:150px;">
                        <input type="date" id="toDate" class="form-control form-control-sm" style="max-width:150px;">
                        <button id="customRangeBtn" class="btn btn-sm btn-outline-secondary">Apply</button>
                    </div>
                </div>
                <div class="card-body">
                    @if (array_sum($bookingsSeries['bookings']) === 0 && array_sum($bookingsSeries['messages']) === 0)
                        <div class="text-center text-muted py-5">
                            No bookings or inquiries available yet for this period.
                        </div>
                    @else
                        <div style="position:relative; height:300px;">
                            <canvas id="bookingsChart" height="300"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-pie me-1"></i> Booking Status</h3>
                </div>
                <div class="card-body">
                    @php $statusTotal = array_sum(array_column($bookingStatuses, 'count')); @endphp
                    @if ($statusTotal === 0)
                        <div class="text-center text-muted py-5">No bookings available yet.</div>
                    @else
                        <div style="position:relative; height:160px;">
                            <canvas id="statusChart" height="160"></canvas>
                        </div>
                        <ul class="list-unstyled mt-3 mb-0">
                            @foreach ($bookingStatuses as $status)
                                <li class="d-flex justify-content-between align-items-center py-1">
                                    <span><i class="fa-solid fa-circle me-2" style="color: {{ $status['color'] }};"></i>{{ $status['label'] }}</span>
                                    <span class="badge text-bg-light border">{{ $status['count'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ============ MOST BOOKED + FLEET ============ --}}
    <div class="row mt-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-route me-1"></i> Most Booked Tours</h3>
                    <div class="card-tools">
                        <span class="badge text-bg-light border">{{ $overview['tours_total'] }} tours</span>
                    </div>
                </div>
                <div class="card-body">
                    @if (count($mostBookedTours) === 0)
                        <div class="text-center text-muted py-5">No tour bookings yet. Requests will appear here.</div>
                    @else
                        <div style="position:relative; height:260px;">
                            <canvas id="topToursChart" height="260"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-hotel me-1"></i> Most Booked Hotels</h3>
                    <div class="card-tools">
                        <span class="badge text-bg-light border">{{ $overview['hotels_total'] }} hotels</span>
                    </div>
                </div>
                <div class="card-body">
                    @if (count($mostBookedHotels) === 0)
                        <div class="text-center text-muted py-5">No hotel bookings yet. Requests will appear here.</div>
                    @else
                        <div style="position:relative; height:260px;">
                            <canvas id="topHotelsChart" height="260"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-car me-1"></i> Transport Fleet</h3>
                    <div class="card-tools">
                        <span class="badge text-bg-light border">{{ $overview['vehicles_available'] }} available</span>
                    </div>
                </div>
                <div class="card-body">
                    @php $fleetTotal = array_sum(array_column($vehicleTypes, 'count')); @endphp
                    @if ($fleetTotal === 0)
                        <div class="text-center text-muted py-5">No vehicles available yet.</div>
                    @else
                        <div style="position:relative; height:160px;">
                            <canvas id="fleetChart" height="160"></canvas>
                        </div>
                        <ul class="list-unstyled mt-3 mb-0">
                            @foreach ($vehicleTypes as $type)
                                @if ($type['count'] > 0)
                                    <li class="d-flex justify-content-between align-items-center py-1">
                                        <span>{{ $type['label'] }}</span>
                                        <span class="badge text-bg-light border">{{ $type['count'] }}</span>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ============ MAP + RECENT ACTIVITY ============ --}}
    <div class="row mt-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-map-marker-alt me-1"></i> Travel Destinations</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.home.destinations.index') }}" class="btn btn-tool" title="Manage destinations">
                            <i class="fas fa-gear"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if (count($markers) === 0)
                        <div class="text-center text-muted py-5">
                            No destination coordinates available yet. Coordinates are resolved from the destination
                            settings or the built-in reference table.
                        </div>
                    @else
                        <div id="destMap" style="height:380px; border-radius:6px;"></div>
                        <p class="text-muted small mt-2 mb-0">
                            <i class="fa-solid fa-circle-info me-1"></i>
                            Markers use real coordinates from the destination settings (or the reference table) and the
                            actual content counts per destination.
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-history me-1"></i> Recent Activity</h3>
                </div>
                <div class="card-body p-0">
                    @if (count($activity) === 0)
                        <div class="text-center text-muted py-5">No recent activity yet.</div>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach ($activity as $item)
                                <li class="list-group-item d-flex gap-3 align-items-start">
                                    @php
                                        $icon = ['booking' => ['fa-calendar-check', 'text-primary'], 'message' => ['fa-envelope', 'text-danger'], 'tour' => ['fa-route', 'text-success'], 'hotel' => ['fa-hotel', 'text-secondary'], 'vehicle' => ['fa-car', 'text-body']][$item['type']] ?? ['fa-circle', 'text-muted'];
                                    @endphp
                                    <div class="d-flex flex-column mt-1">
                                        <i class="fa-solid {{ $icon[0] }} {{ $icon[1] }}"></i>
                                    </div>
                                    <div class="w-100">
                                        <div class="d-flex justify-content-between gap-2">
                                            @if ($item['url'])
                                                <a href="{{ $item['url'] }}" class="text-decoration-none fw-semibold">{{ $item['label'] }}</a>
                                            @else
                                                <span class="fw-semibold">{{ $item['label'] }}</span>
                                            @endif
                                            <small class="text-muted text-nowrap">{{ $item['time']->diffForHumans() }}</small>
                                        </div>
                                        @if ($item['value'])
                                            <div class="text-muted small">{{ $item['value'] }}</div>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ============ RECENT BOOKINGS TABLE ============ --}}
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title"><i class="fas fa-calendar-check me-1"></i> Recent Bookings</h3>
                    <a href="{{ route('admin.bookings.index') }}" class="btn btn-sm btn-outline-secondary">View all</a>
                </div>
                <div class="card-body p-0">
                    @if ($recentBookings->isEmpty())
                        <div class="text-center text-muted py-5">
                            No bookings available yet.
                            <a href="{{ route('bookings.create') }}" class="d-block mt-2">Go to the booking page</a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Reference</th>
                                        <th>Customer</th>
                                        <th>Service</th>
                                        <th>Travel Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recentBookings as $booking)
                                        <tr>
                                            <td><code>{{ $booking->booking_reference }}</code></td>
                                            <td>
                                                <strong>{{ $booking->name }}</strong>
                                                <br><small class="text-muted">{{ $booking->phone ?: $booking->email }}</small>
                                            </td>
                                            <td>
                                                {{ \Illuminate\Support\Str::limit($booking->service_title, 32) }}
                                                <br><span class="badge text-bg-light border">{{ $booking->booking_type_label }}</span>
                                            </td>
                                            <td>
                                                {{ $booking->start_date ? $booking->start_date->format('M d, Y') : '—' }}
                                            </td>
                                            <td>{!! $booking->amount_display !!}</td>
                                            <td>
                                                <span class="badge {{ $booking->status_color }}">{{ $booking->status_label }}</span>
                                            </td>
                                            <td>{{ $booking->created_at->format('M d, Y') }}</td>
                                            <td class="text-end text-nowrap">
                                                <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-sm btn-info" title="View">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ============ CONTENT BY DESTINATION + RECENT INQUIRIES ============ --}}
    <div class="row mt-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-layer-group me-1"></i> Content by Destination</h3>
                </div>
                <div class="card-body">
                    @if (count($contentByDestination) === 0)
                        <div class="text-center text-muted py-5">No destinations available yet.</div>
                    @else
                        <div style="position:relative; height:280px;">
                            <canvas id="destinationChart" height="280"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title"><i class="fas fa-inbox me-1"></i> Recent Inquiries</h3>
                    <a href="{{ route('admin.contact.messages.index') }}" class="btn btn-sm btn-outline-secondary">View all</a>
                </div>
                <div class="card-body">
                    <div class="row text-center mb-3">
                        <div class="col-4">
                            <h4 class="mb-0">{{ $overview['messages_total'] }}</h4>
                            <span class="text-muted small">Total</span>
                        </div>
                        <div class="col-4">
                            <h4 class="mb-0 text-danger">{{ $overview['messages_unread'] }}</h4>
                            <span class="text-muted small">Unread</span>
                        </div>
                        <div class="col-4">
                            <h4 class="mb-0">{{ $overview['messages_today'] }}</h4>
                            <span class="text-muted small">Today</span>
                        </div>
                    </div>

                    @if ($recentInquiries->isEmpty())
                        <div class="text-center text-muted py-4">No messages received yet.</div>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach ($recentInquiries as $message)
                                <li class="list-group-item px-0 d-flex gap-3 align-items-start">
                                    <div class="w-100">
                                        <div class="d-flex justify-content-between gap-2">
                                            <a href="{{ route('admin.contact.messages.show', $message) }}"
                                                class="text-decoration-none fw-semibold {{ $message->status === 'new' ? 'text-danger' : '' }}">
                                                {{ $message->name }}
                                            </a>
                                            <small class="text-muted text-nowrap">{{ $message->created_at->diffForHumans() }}</small>
                                        </div>
                                        <div class="text-muted small">{{ \Illuminate\Support\Str::limit($message->subject, 48) }}</div>
                                        <span class="badge {{ $message->status_color }}">{{ $message->status_label }}</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .small-box .small-box-footer { opacity: 0.9; }
        #destMap { width: 100%; }
    </style>
@stop

@section('js')
    <script>
        (function () {
            const chartColors = {
                tours: '#198754',
                hotels: '#6c757d',
                vehicles: '#adb5bd',
                destinations: ['#0d6efd', '#198754', '#f39c12', '#0dcaf0', '#dc3545', '#6610f2', '#fd7e14', '#20c997'],
                fleet: ['#0d6efd', '#198754', '#f39c12', '#0dcaf0', '#dc3545', '#6610f2', '#20c997']
            };

            const bookingsData = @json($bookingsSeries);
            const statusData = @json($bookingStatuses);
            const fleetData = @json($vehicleTypes);
            const topToursData = @json($mostBookedTours);
            const topHotelsData = @json($mostBookedHotels);
            const destinationData = @json($contentByDestination);
            const markers = @json($markers);

            Chart.defaults.font.family = "'Source Sans 3', system-ui, sans-serif";

            const bookingsChartEl = document.getElementById('bookingsChart');

            function bookingsChartOptions() {
                return {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } }
                    }
                };
            }

            let bookingsChart = null;
            if (bookingsChartEl) {
                bookingsChart = new Chart(bookingsChartEl, {
                    type: 'line',
                    data: {
                        labels: bookingsData.labels,
                        datasets: [
                            {
                                label: 'Bookings',
                                data: bookingsData.bookings,
                                borderColor: '#0d6efd',
                                backgroundColor: 'rgba(13,110,253,0.12)',
                                fill: true,
                                tension: 0.3
                            },
                            {
                                label: 'Inquiries',
                                data: bookingsData.messages,
                                borderColor: '#f39c12',
                                backgroundColor: 'rgba(243,156,18,0.10)',
                                borderDash: [6, 4],
                                fill: true,
                                tension: 0.3
                            }
                        ]
                    },
                    options: bookingsChartOptions()
                });
            }

            document.getElementById('rangeSelect')?.addEventListener('change', function () {
                refreshChart(this.value);
            });

            document.getElementById('customRangeBtn')?.addEventListener('click', function () {
                const from = document.getElementById('fromDate').value;
                const to = document.getElementById('toDate').value;
                if (!from || !to) {
                    return;
                }
                refreshCustom(from, to);
            });

            function refreshChart(range) {
                fetchChart('?range=' + encodeURIComponent(range));
            }

            function refreshCustom(from, to) {
                fetchChart('?from=' + encodeURIComponent(from) + '&to=' + encodeURIComponent(to));
            }

            function fetchChart(query) {
                const url = "{{ route('dashboard.chart-data') }}" + query;
                $.getJSON(url, function (data) {
                    if (!bookingsChart) {
                        return;
                    }
                    bookingsChart.data.labels = data.labels;
                    bookingsChart.data.datasets[0].data = data.bookings;
                    bookingsChart.data.datasets[1].data = data.messages;
                    bookingsChart.update();
                    document.getElementById('bookingsPeriodTotal').textContent = data.bookings_total;
                }).fail(function () {
                    alert('Could not refresh the chart data. Please try again.');
                });
            }

            if (document.getElementById('statusChart')) {
                new Chart(document.getElementById('statusChart'), {
                    type: 'doughnut',
                    data: {
                        labels: statusData.map(s => s.label),
                        datasets: [{
                            data: statusData.map(s => s.count),
                            backgroundColor: statusData.map(s => s.color),
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: ctx => ' ' + ctx.label + ': ' + ctx.parsed } }
                        }
                    }
                });
            }

            function horizontalBarChart(canvasId, labels, data, color) {
                new Chart(document.getElementById(canvasId), {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: data,
                            backgroundColor: color,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
                    }
                });
            }

            if (document.getElementById('topToursChart')) {
                horizontalBarChart('topToursChart',
                    topToursData.map(d => d.service),
                    topToursData.map(d => d.count),
                    chartColors.tours);
            }

            if (document.getElementById('topHotelsChart')) {
                horizontalBarChart('topHotelsChart',
                    topHotelsData.map(d => d.service),
                    topHotelsData.map(d => d.count),
                    chartColors.hotels);
            }

            if (document.getElementById('fleetChart')) {
                const fleetTypes = fleetData.filter(t => t.count > 0);
                new Chart(document.getElementById('fleetChart'), {
                    type: 'doughnut',
                    data: {
                        labels: fleetTypes.map(t => t.label),
                        datasets: [{
                            data: fleetTypes.map(t => t.count),
                            backgroundColor: chartColors.fleet.slice(0, fleetTypes.length),
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } }
                    }
                });
            }

            if (document.getElementById('destinationChart')) {
                new Chart(document.getElementById('destinationChart'), {
                    type: 'bar',
                    data: {
                        labels: destinationData.map(d => d.name),
                        datasets: [
                            { label: 'Tours', data: destinationData.map(d => d.tours), backgroundColor: chartColors.tours, borderRadius: 3 },
                            { label: 'Hotels', data: destinationData.map(d => d.hotels), backgroundColor: chartColors.hotels, borderRadius: 3 },
                            { label: 'Vehicles', data: destinationData.map(d => d.vehicles), backgroundColor: chartColors.vehicles, borderRadius: 3 }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: { stacked: true },
                            y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } }
                        }
                    }
                });
            }

            const mapEl = document.getElementById('destMap');
            if (mapEl && markers.length > 0) {
                const map = L.map('destMap').setView([28.9, 80.9], 7);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    maxZoom: 18
                }).addTo(map);

                const bounds = [];
                markers.forEach(function (m) {
                    const parts = [];
                    if (m.location) {
                        parts.push('<span>' + m.location + '</span>');
                    }
                    parts.push('<strong>' + m.name + '</strong>');
                    parts.push('Tours: ' + m.tours + ' &middot; Hotels: ' + m.hotels + ' &middot; Vehicles: ' + m.vehicles);
                    parts.push('<a href="' + m.url + '" target="_blank">Manage destination &rarr;</a>');

                    L.marker([m.lat, m.lng]).addTo(map).bindPopup(parts.join('<br>'));
                    bounds.push([m.lat, m.lng]);
                });

                if (bounds.length > 1) {
                    map.fitBounds(bounds, { padding: [24, 24] });
                }

                window.addEventListener('resize', function () {
                    map.invalidateSize();
                });
            }
        })();
    </script>
@stop