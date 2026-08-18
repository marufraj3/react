@extends('frontEnd.layouts.master')

@section('title', 'Track Your Order')

@section('content')
<div class="sf-page">
    <div class="sf-container">
        <div class="sf-account">
            @include('frontEnd.layouts.customer.sidebar')

            <div class="sf-apanel">
                <h3><i class="fa-solid fa-truck-fast"></i> Track Your Order</h3>
                <p class="sf-muted" style="margin-bottom:20px">Enter your order invoice ID or the phone number used at checkout to see the live status of your delivery.</p>

                <form class="sf-track" action="{{ route('customer.order_track_result') }}" method="GET">
                    <div class="sf-field">
                        <label>Invoice ID</label>
                        <input type="text" name="invoice_id" class="sf-input" value="{{ request('invoice_id') }}" placeholder="e.g. INV-20240816-1234" />
                    </div>
                    <div class="sf-field">
                        <label>OR — Mobile Number</label>
                        <input type="text" name="phone" class="sf-input" maxlength="11" pattern="0[0-9]+" placeholder="017xxxxxxxx" />
                    </div>
                    <button type="submit" class="sf-btn sf-btn--primary sf-btn--lg sf-btn--block"><i class="fa-solid fa-magnifying-glass"></i> Track Order</button>
                </form>

                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:26px">
                    <div style="text-align:center;background:var(--c-bg);border-radius:var(--r-md);padding:16px 8px">
                        <i class="fa-solid fa-box" style="font-size:20px;color:var(--c-primary)"></i>
                        <div style="font-size:11.5px;font-weight:700;color:var(--c-muted);margin-top:6px">Order Placed</div>
                    </div>
                    <div style="text-align:center;background:var(--c-bg);border-radius:var(--r-md);padding:16px 8px">
                        <i class="fa-solid fa-truck" style="font-size:20px;color:var(--c-primary)"></i>
                        <div style="font-size:11.5px;font-weight:700;color:var(--c-muted);margin-top:6px">On the Way</div>
                    </div>
                    <div style="text-align:center;background:var(--c-bg);border-radius:var(--r-md);padding:16px 8px">
                        <i class="fa-solid fa-house-circle-check" style="font-size:20px;color:var(--c-green)"></i>
                        <div style="font-size:11.5px;font-weight:700;color:var(--c-muted);margin-top:6px">Delivered</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
