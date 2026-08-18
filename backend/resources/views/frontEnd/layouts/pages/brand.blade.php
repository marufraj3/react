@extends('frontEnd.layouts.master')

@section('title', $brand->name . ' — Products')

@section('content')
<div class="sf-page">
    <div class="sf-container">

        <nav class="sf-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('home') }}">Home</a><i class="fa-solid fa-angle-right"></i>
            <a href="{{ route('shop') }}">Shop</a><i class="fa-solid fa-angle-right"></i>
            <span class="cur">{{ $brand->name }}</span>
        </nav>

        <div style="display:flex;align-items:center;gap:18px;background:#fff;border:1px solid var(--c-line);border-radius:var(--r-lg);padding:20px;margin-bottom:20px">
            <img src="{{ asset($brand->image) }}" alt="{{ $brand->name }}" style="width:84px;height:84px;border-radius:16px;object-fit:contain;border:1px solid var(--c-line);padding:8px;background:#fff" />
            <div>
                <h1 style="font-size:22px">{{ $brand->name }}</h1>
                <p class="sf-faint" style="font-size:13px;margin-top:4px">{{ $products->total() }} product(s) from this brand</p>
            </div>
        </div>

        <form method="GET" class="sf-shop__toolbar" style="margin-bottom:16px">
            <h3>All {{ $brand->name }} Products</h3>
            <label class="sf-shop__sort">Sort by
                <select class="sf-select" name="sort" onchange="this.form.submit()">
                    <option value="">Newest</option>
                    <option value="3" @if(request('sort') == 3) selected @endif>Price: High → Low</option>
                    <option value="4" @if(request('sort') == 4) selected @endif>Price: Low → High</option>
                </select>
            </label>
        </form>

        @if($products->count())
            <div class="sf-pgrid sf-pgrid--5">
                @foreach($products as $product)
                    @include('frontEnd.layouts.partials.product-card', ['product' => $product])
                @endforeach
            </div>
            {{ $products->onEachSide(1)->links('pagination::bootstrap-5') }}
        @else
            <div class="sf-empty sf-card-surface">
                <i class="fa-regular fa-face-frown"></i>
                <h4>No products found</h4>
                <p>This brand has no products right now.</p>
                <a class="sf-btn sf-btn--dark" style="margin-top:16px" href="{{ route('shop') }}">Browse All Products</a>
            </div>
        @endif
    </div>
</div>
@endsection
