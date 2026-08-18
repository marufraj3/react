@extends('frontEnd.layouts.master')

@section('title', $subcategory->subcategoryName . ' — Shop')

@section('content')
<div class="sf-page">
    <div class="sf-container">

        <nav class="sf-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('home') }}">Home</a><i class="fa-solid fa-angle-right"></i>
            @if(!empty($subcategory->category))<a href="{{ route('category', $subcategory->category->slug) }}">{{ $subcategory->category->name }}</a><i class="fa-solid fa-angle-right"></i>@endif
            <span class="cur">{{ $subcategory->subcategoryName }}</span>
        </nav>

        <form class="sf-shop" method="GET">

            <aside class="sf-sidebar" id="sfFilterSide">
                <div class="sf-filter-card">
                    <h5>Sub-groups</h5>
                    <div class="sf-filter-cats">
                        <a href="{{ route('subcategory', $subcategory->slug) }}" class="{{ !request('childcategory') ? 'active' : '' }}">All {{ $subcategory->subcategoryName }}</a>
                        @foreach($childcategories as $child)
                            <a href="{{ route('subcategory', $subcategory->slug) }}?childcategory[]={{ $child->id }}">{{ $child->childcategoryName }}</a>
                        @endforeach
                    </div>
                </div>

                @if($min_price !== null && $max_price !== null && $max_price > $min_price)
                    <div class="sf-filter-card sf-filter-price">
                        <h5>Price Range</h5>
                        <div class="vals"><span id="sfRangeMinVal">৳{{ number_format((float) $min_price) }}</span><span id="sfRangeMaxVal">৳{{ number_format((float) $max_price) }}</span></div>
                        <div class="sf-range">
                            <div class="bar" id="sfRangeBar"></div>
                            <input type="range" id="sfRangeMin" name="min_price" min="{{ (int) $min_price }}" max="{{ (int) $max_price }}" value="{{ request('min_price', (int) $min_price) }}" step="1" />
                            <input type="range" id="sfRangeMax" name="max_price" min="{{ (int) $min_price }}" max="{{ (int) $max_price }}" value="{{ request('max_price', (int) $max_price) }}" step="1" />
                        </div>
                        <button type="submit" class="sf-btn sf-btn--dark sf-btn--block sf-btn--sm" style="margin-top:14px">Apply Price</button>
                    </div>
                @endif

                @if(($impproducts ?? collect())->count())
                    <div class="sf-filter-card">
                        <h5><i class="fa-solid fa-fire" style="color:var(--c-accent)"></i> Trending Now</h5>
                        <div style="display:flex;flex-direction:column;gap:10px">
                            @foreach($impproducts->take(3) as $ip)
                                <a href="{{ route('product', $ip->slug) }}" style="display:flex;gap:10px;align-items:center">
                                    <img src="{{ asset($ip->image->image ?? 'public/logo.png') }}" alt="{{ $ip->name }}" style="width:48px;height:48px;border-radius:10px;object-fit:cover;border:1px solid var(--c-line)" />
                                    <span style="font-size:12.5px;font-weight:700;color:var(--c-text)" class="sf-clamp-2">{{ $ip->name }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </aside>
            <div class="sf-sidebar-ovl" id="sfFilterOvl"></div>

            <div class="sf-shop__main">
                <div class="sf-shop__toolbar">
                    <h3>{{ $subcategory->subcategoryName }} <small>{{ $products->total() }} product(s) found</small></h3>
                    <button type="button" class="sf-shop__filterbtn" id="sfFilterBtn"><i class="fa-solid fa-sliders"></i> Filter</button>
                    <label class="sf-shop__sort">Sort by
                        <select class="sf-select" name="sort" onchange="this.form.submit()">
                            <option value="">Newest</option>
                            <option value="1" @if(request('sort') == 1) selected @endif>Newest First</option>
                            <option value="2" @if(request('sort') == 2) selected @endif>Oldest First</option>
                            <option value="3" @if(request('sort') == 3) selected @endif>Price: High → Low</option>
                            <option value="4" @if(request('sort') == 4) selected @endif>Price: Low → High</option>
                        </select>
                    </label>
                </div>

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
                        <p>Try adjusting your filters or browse other categories.</p>
                        <a class="sf-btn sf-btn--dark" style="margin-top:16px" href="{{ route('shop') }}">Browse All Products</a>
                    </div>
                @endif
            </div>
        </form>
    </div>
</div>
@endsection
