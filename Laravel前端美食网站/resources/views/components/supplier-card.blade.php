@props(['supplier'])

<div class="col">
    <a class="rest-card h-100" href="{{ route('suppliers.show', $supplier) }}">
        <div class="rest-card__media">
            <img src="{{ $supplier->cover }}" alt="{{ $supplier->name }}" loading="lazy">
            <span class="rest-card__score">{{ number_format($supplier->rating, 1) }}</span>
        </div>
        <div class="rest-card__body">
            <h3 class="rest-card__name">{{ $supplier->name }}</h3>
            <div class="rest-card__meta">
                {{ $supplier->category }} · {{ $supplier->city }}{{ $supplier->district }}
                · {{ $supplier->products_count ?? $supplier->products->count() }} 款在售
            </div>

            <p class="rest-card__intro">{{ $supplier->intro }}</p>

            <div class="rest-card__features">
                @foreach (array_slice($supplier->features ?? [], 0, 3) as $feature)
                    <span class="sg-badge">{{ $feature }}</span>
                @endforeach
            </div>

            <div class="rest-card__foot">
                <span>营业 {{ $supplier->hours }}</span>
                <span class="rest-card__more">查看店铺 →</span>
            </div>
        </div>
    </a>
</div>
