@php($me = auth()->user())

{{-- 账号资料页顶部已有身份卡，这里就不再重复 --}}
@unless (request()->routeIs('account.index'))
    <div class="sg-card mb-4">
        <div class="d-flex align-items-center gap-3">
            <x-user-avatar :user="$me" size="lg" />
            <div class="min-w-0">
                <div class="fw-semibold text-truncate">{{ $me->name }}</div>
                <div class="mt-1">
                    <span class="role-badge role-badge--{{ $me->role }}">{{ $me->role_label }}</span>
                </div>
                <div class="text-muted mt-1 text-truncate" style="font-size:.75rem;">{{ $me->email }}</div>
            </div>
        </div>
    </div>
@endunless

<nav class="account-nav mb-4">
    <a class="{{ request()->routeIs('account.index') ? 'active' : '' }}" href="{{ route('account.index') }}">
        账号资料
    </a>
    <a class="{{ request()->routeIs('account.favorites') ? 'active' : '' }}" href="{{ route('account.favorites') }}">
        我的收藏
    </a>
    <a class="{{ request()->routeIs('cart.*') ? 'active' : '' }}" href="{{ route('cart.index') }}">
        购物车
        <span class="nav-count" data-cart-badge @if ($me->cartCount() === 0) hidden @endif>{{ $me->cartCount() }}</span>
    </a>
    <a class="{{ request()->routeIs('orders.*') ? 'active' : '' }}" href="{{ route('orders.index') }}">
        我的订单
    </a>
    <a class="{{ request()->routeIs('bookings.*') ? 'active' : '' }}" href="{{ route('bookings.index') }}">
        我的预约
    </a>
    <a class="{{ request()->routeIs('account.works*') ? 'active' : '' }}" href="{{ route('account.works') }}">
        我的作品
    </a>
    <a class="{{ request()->routeIs('account.notes*') ? 'active' : '' }}" href="{{ route('account.notes') }}">
        我的食记
    </a>
    @if ($me->canManageRestaurant())
        <a class="{{ request()->routeIs('account.restaurant*') ? 'active' : '' }}" href="{{ route('account.restaurant') }}">
            餐厅管理
        </a>
    @endif
    @if ($me->canManageSupplier())
        <a class="{{ request()->routeIs('account.supplier*') ? 'active' : '' }}" href="{{ route('account.supplier') }}">
            食材店管理
        </a>
    @endif
</nav>
