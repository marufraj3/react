@extends('frontEnd.layouts.master')

@section('title', $vendor->shop_name)

@section('content')
<div class="sf-page">
    <div class="sf-container">

        <div class="sf-seller-hero">
            <img class="banner" src="{{ $vendor->banner ? asset($vendor->banner) : asset('public/frontEnd/images/banner.png') }}" alt="{{ $vendor->shop_name }}" />
            <div class="sf-seller-hero__in">
                <img class="logo" src="{{ asset($vendor->logo ?? 'public/logo.png') }}" alt="{{ $vendor->shop_name }}" />
                <div>
                    <h2>{{ $vendor->shop_name }}
                        @if(($vendor->verification_status ?? '') == 'approved')
                            <span class="sf-badge sf-badge--green" style="font-size:10px;vertical-align:middle"><i class="fa-solid fa-circle-check"></i> Verified</span>
                        @endif
                    </h2>
                    <div class="sf-stars" style="margin-top:4px">
                        @for($i = 1; $i <= 5; $i++)<i class="fa-solid fa-star {{ $i <= round($vendor->average_rating ?? 0) ? 'on' : '' }}"></i>@endfor
                        <span class="sf-faint" style="margin-left:6px">{{ $vendor->average_rating ?? 0 }} rating · {{ $vendor->total_reviews ?? 0 }} reviews · {{ $vendor->total_products ?? 0 }} products</span>
                    </div>
                </div>
                <a class="sf-btn sf-btn--primary" href="{{ route('contact') }}">Contact Seller</a>
            </div>
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
                <i class="fa-solid fa-box-open"></i>
                <h4>No products in this shop yet</h4>
                <p>Check back soon — new products are added regularly.</p>
            </div>
        @endif
    </div>
</div>
@endsection
