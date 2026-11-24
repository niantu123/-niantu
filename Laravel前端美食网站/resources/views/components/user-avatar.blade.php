@props(['user', 'size' => 'xs'])

@if ($user->avatar_url)
    <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="avatar-{{ $size }}">
@else
    <span class="avatar-{{ $size }}">{{ mb_substr($user->name, 0, 1) }}</span>
@endif
