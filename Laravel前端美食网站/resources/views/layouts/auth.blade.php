<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '食光 · 好好吃饭')</title>
    @vite(['resources/css/app.scss', 'resources/js/app.js'])
</head>
<body>
<div class="auth-shell">
    <aside class="auth-brand d-none d-lg-flex">
        <div class="auth-brand__logo">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 11h18a9 9 0 0 1-18 0Z"/>
                <path d="M9 7c0-1.1 1-1.4 1-2.4M13.5 7c0-1.1 1-1.4 1-2.4"/>
            </svg>
            <span>食光</span>
            <span class="auth-brand__en">SHIGUANG</span>
        </div>

        <div class="auth-brand__quote">
            <h1>好好吃饭，<br>就是好好生活。</h1>
            <p>每一道菜都有它的来处。这里有 1,280 道被反复做过、改过、验证过的家常菜谱，从今晚的一碗面开始。</p>
        </div>

        <div class="auth-brand__stats">
            <div><b>1,280</b><span>道菜谱</span></div>
            <div><b>386</b><span>种食材</span></div>
            <div><b>12 万</b><span>位厨友</span></div>
        </div>
    </aside>

    <main class="auth-main">
        <div class="auth-card">
            <a href="{{ route('home') }}" class="d-inline-block mb-4 text-decoration-none text-secondary small">
                ← 回到首页
            </a>

            @yield('content')
        </div>
    </main>
</div>
</body>
</html>
