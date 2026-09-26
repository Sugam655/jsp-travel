<a href="{{ $url ?? route('tours.show', $tour) }}" class="text-reset text-decoration-none shadow-none">
    <div class="pkg-c">
        <div class="pkg-img">
            <img src="{{ $tour->image_url }}" alt="{{ $tour->title }}">
            @if ($tour->discount_percent)
                <span class="disc">{{ $tour->discount_percent }}% OFF</span>
            @endif
        </div>
        <div class="pkg-bd">
            <div class="pkg-nm">{{ $tour->title }}</div>
            <div class="pkg-mt">
                <div class="pkg-dur">
                    <i class="far fa-clock"></i>
                    {{ $tour->duration }}
                </div>
                <div class="pkg-pr">
                    <span class="cur-pr">Rs.{{ number_format((float) $tour->price) }}</span>
                    @if ($tour->old_price)
                        <span class="old-pr">Rs.{{ number_format((float) $tour->old_price) }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</a>