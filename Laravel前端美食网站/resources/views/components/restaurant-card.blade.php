@props(['restaurant'])

<div class="col">
    <a class="rest-card h-100" href="{{ route('restaurants.show', $restaurant) }}">
        <div class="rest-card__media">
            <img src="{{ $restaurant->cover }}" alt="{{ $restaurant->name }}" loading="lazy">
            <span class="rest-card__score">{{ number_format($restaurant->rating, 1) }}</span>
        </div>
        <div class="rest-card__body">
            <h3 class="rest-card__name">{{ $restaurant->name }}</h3>
            <div class="rest-card__meta">
                {{ $restaurant->cuisine }} · {{ $restaurant->city }}{{ $restaurant->district }}
                · 人均 ¥{{ $restaurant->avg_price }}
            </div>

            <p class="rest-card__intro">{{ $restaurant->intro }}</p>

            <div class="rest-card__features">
                @if ($restaurant->accepts_booking)
                    <span class="sg-badge sg-badge--book">可预约</span>
                @endif
                @foreach (array_slice($restaurant->features ?? [], 0, 3) as $feature)
                    <span class="sg-badge">{{ $feature }}</span>
                @endforeach
            </div>

            <div class="rest-card__foot">
                <span>营业 {{ $restaurant->hours }}</span>
                <span class="rest-card__more">查看详情 →</span>
            </div>
        </div>
    </a>
</div>
