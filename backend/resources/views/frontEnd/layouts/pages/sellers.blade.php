@extends('frontEnd.layouts.master')

@section('title', 'Our Sellers')

@section('content')
<div class="sf-page">
    <div class="sf-container">
        <div class="sf-page-head" style="border-radius:var(--r-lg);margin-top:18px">
            <div class="sf-container">
                <h1><i class="fa-solid fa-store" style="color:#ffb02e;margin-right:10px"></i>Our Sellers</h1>
                <p style="color:#c3cdea;font-size:14px;margin-top:6px">Shop from verified sellers with great products and ratings.</p>
            </div>
        </div>

        @if($vendors->count())
            <div class="sf-shops" style="margin-top:22px">
                @foreach($vendors as $vendor)
                    <a class="sf-shop" href="{{ route('vendor.shop', $vendor->slug) }}">
                        <img class="logo" src="{{ asset($vendor->logo ?? 'public/logo.png') }}" alt="{{ $vendor->shop_name }}" />
                        <div>
                            <b>{{ $vendor->shop_name }}</b>
                            <small>{{ $vendor->products_count ?? 0 }}+ products</small>
                            <div class="sf-stars" style="margin-top:4px">
                                @for($i = 1; $i <= 5; $i++)<i class="fa-solid fa-star {{ $i <= round($vendor->average_rating ?? 0) ? 'on' : '' }}"></i>@endfor
                                <span class="sf-faint" style="margin-left:5px">{{ $vendor->average_rating ?? 0 }} ({{ $vendor->total_reviews ?? 0 }})</span>
                            </div>
                            @if(($vendor->verification_status ?? '') == 'approved')
                                <span class="sf-badge sf-badge--green" style="margin-top:6px"><i class="fa-solid fa-circle-check"></i> Verified</span>
                            @endif
                        </div>
                        <span class="sf-btn sf-btn--outline sf-btn--sm">Visit Shop</span>
                    </a>
                @endforeach
            </div>
            {{ $vendors->onEachSide(1)->links('pagination::bootstrap-5') }}
        @else
            <div class="sf-empty sf-card-surface">
                <i class="fa-solid fa-store"></i>
                <h4>No sellers yet</h4>
                <p>Sellers will appear here soon.</p>
            </div>
        @endif
    </div>
</div>
@endsection
