@props(['recipe'])

<a class="recipe-card" href="{{ route('recipes.show', $recipe) }}">
    <div class="recipe-card__media">
        <img src="{{ $recipe->cover }}" alt="{{ $recipe->title }}" loading="lazy">
        <span class="recipe-card__time">{{ $recipe->total_minutes }} 分钟</span>
    </div>
    <div class="recipe-card__body">
        <span class="sg-chip">{{ $recipe->category }}</span>
        <h3 class="recipe-card__title">{{ $recipe->title }}</h3>
        <div class="recipe-card__meta">
            <span>{{ $recipe->difficulty }}</span>
            <span>·</span>
            <span>{{ $recipe->author }}</span>
            <span class="ms-auto">★ {{ $recipe->comments_avg_rating ? number_format((float) $recipe->comments_avg_rating, 1) : '—' }}</span>
        </div>
    </div>
</a>
