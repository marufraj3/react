@extends('frontEnd.layouts.master')

@section('title', 'Special Offers')

@section('content')
<div class="sf-page">
    <div class="sf-container">
        <div class="sf-page-head" style="border-radius:var(--r-lg);margin-top:18px">
            <div class="sf-container">
                <h1><i class="fa-solid fa-gift" style="color:#ffb02e;margin-right:10px"></i>Special Offers</h1>
                <p style="color:#c3cdea;font-size:14px;margin-top:6px">Exclusive deals, discounts and vouchers — all in one place.</p>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:14px;margin-top:22px">
            <a href="{{ route('flashsales') }}" class="sf-card-surface" style="padding:26px 22px;display:block;transition:.2s">
                <i class="fa-solid fa-bolt" style="font-size:26px;color:var(--c-accent)"></i>
                <h3 style="font-size:17px;margin:12px 0 6px">Flash Sale</h3>
                <p class="sf-faint" style="font-size:13px">Limited-time deals with big discounts. Ends soon!</p>
            </a>
            <a href="{{ route('hotdeals') }}" class="sf-card-surface" style="padding:26px 22px;display:block;transition:.2s">
                <i class="fa-solid fa-fire" style="font-size:26px;color:#ff7a00"></i>
                <h3 style="font-size:17px;margin:12px 0 6px">Hot Deals</h3>
                <p class="sf-faint" style="font-size:13px">Our most popular products at their best prices.</p>
            </a>
            <a href="{{ route('shop') }}" class="sf-card-surface" style="padding:26px 22px;display:block;transition:.2s">
                <i class="fa-solid fa-tags" style="font-size:26px;color:var(--c-green)"></i>
                <h3 style="font-size:17px;margin:12px 0 6px">Clearance</h3>
                <p class="sf-faint" style="font-size:13px">Quality products on clearance — while stock lasts.</p>
            </a>
        </div>
    </div>
</div>
@endsection
