@props(['note'])

<a class="note-card h-100" href="{{ route('notes.show', $note) }}">
    <div class="note-card__media">
        @if ($note->cover)
            <img src="{{ $note->cover }}" alt="{{ $note->title }}" loading="lazy">
        @else
            <span class="note-card__placeholder">{{ mb_substr($note->title, 0, 1) }}</span>
        @endif
    </div>
    <div class="note-card__body">
        <h3 class="note-card__title">{{ $note->title }}</h3>
        <p class="note-card__excerpt">{{ $note->content }}</p>
        <div class="note-card__foot">
            <span class="note-card__author">{{ $note->user?->name ?? '厨友' }}</span>
            <span class="text-muted">{{ $note->comments_count ?? 0 }} 条评论 · {{ $note->created_at->diffForHumans() }}</span>
        </div>
    </div>
</a>
