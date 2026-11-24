@extends('layouts.app')

@section('title', '食材店 · 食光')

@section('content')
    <section class="sg-page-head">
        <div class="container">
            <div class="row align-items-end g-4">
                <div class="col-lg-6">
                    <h1>食材店</h1>
                    <p>{{ $suppliers->total() }} 家食材店，从清晨的蔬菜摊到专门的水产柜，买得到菜谱里写的那种料。</p>
                </div>
                <div class="col-lg-6">
                    <form class="sg-search ms-lg-auto" action="{{ route('suppliers.index') }}" method="GET">
                        @if ($category !== '')
                            <input type="hidden" name="category" value="{{ $category }}">
                        @endif
                        <input type="search" name="q" value="{{ $search }}" placeholder="搜店名、主营或地址" aria-label="搜索食材店">
                        <button class="btn btn-primary px-4" type="submit">搜索</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="sg-section">
        <div class="container">
            <div class="sg-filter-bar mb-4">
                <a class="sg-filter {{ $category === '' ? 'active' : '' }}"
                   href="{{ route('suppliers.index', array_filter(['q' => $search])) }}">全部</a>
                @foreach ($categories as $item)
                    <a class="sg-filter {{ $category === $item ? 'active' : '' }}"
                       href="{{ route('suppliers.index', array_filter(['category' => $item, 'q' => $search])) }}">{{ $item }}</a>
                @endforeach
            </div>

            @if ($suppliers->isEmpty())
                <div class="text-center py-5">
                    <p class="text-muted mb-3">没有找到符合条件的食材店。</p>
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('suppliers.index') }}">看看全部食材店</a>
                </div>
            @else
                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                    @foreach ($suppliers as $supplier)
                        <x-supplier-card :supplier="$supplier" />
                    @endforeach
                </div>

                <div class="mt-5 d-flex justify-content-center">
                    {{ $suppliers->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
