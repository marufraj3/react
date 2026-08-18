@extends('frontEnd.layouts.master')

@section('title', 'Flash Sale')

@section('content')
<div class="sf-page">
    <div class="sf-container">

        <div class="sf-page-head" style="border-radius:var(--r-lg);margin-top:18px">
            <div class="sf-container">
                <h1><i class="fa-solid fa-bolt" style="color:#ffb02e;margin-right:10px"></i>Flash Sale</h1>
                <p style="color:#c3cdea;font-size:14px;margin-top:6px">Massive discounts for a very short time. When the timer hits zero — the deals are gone.</p>
                @if(!empty(optional($generalsetting)->flash_sale_end_date))
                    <div class="sf-flash__timer" data-end="{{ strtotime(optional($generalsetting)->flash_sale_end_date) * 1000 }}" style="margin:14px 0 0">
                        <span class="box"><b data-t-d>00</b><small>Days</small></span><span class="sep">:</span>
                        <span class="box"><b data-t-h>00</b><small>Hours</small></span><span class="sep">:</span>
                        <span class="box"><b data-t-m>00</b><small>Mins</small></span><span class="sep">:</span>
                        <span class="box"><b data-t-s>00</b><small>Secs</small></span>
                    </div>
                @endif
            </div>
        </div>

        @if($products->count())
            <div class="sf-pgrid sf-pgrid--5" style="margin-top:22px">
                @foreach($products as $product)
                    @include('frontEnd.layouts.partials.product-card', ['product' => $product, 'showSold' => true])
                @endforeach
            </div>
            {{ $products->onEachSide(1)->links('pagination::bootstrap-5') }}
        @else
            <div class="sf-empty sf-card-surface">
                <i class="fa-solid fa-bolt"></i>
                <h4>No flash sale right now</h4>
                <p>New flash sales start soon — check back later.</p>
                <a class="sf-btn sf-btn--dark" style="margin-top:16px" href="{{ route('shop') }}">Browse Products</a>
            </div>
        @endif
    </div>
</div>
@endsection
