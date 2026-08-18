@extends('frontEnd.layouts.master')

@section('title', 'My Dashboard')

@section('content')
@php
    $customer = Auth::guard('customer')->user();
    $totalOrders = \App\Models\Order::where('customer_id', $customer->id)->count();
    $pendingOrders = \App\Models\Order::where('customer_id', $customer->id)->whereNotIn('order_status', ['6', '11'])->count();
    $completedOrders = \App\Models\Order::where('customer_id', $customer->id)->where('order_status', '6')->count();
    $recentOrders = \App\Models\Order::where('customer_id', $customer->id)
        ->with(['status', 'orderdetails.product.image'])
        ->latest()->limit(5)->get();
    $recommendedProducts = \App\Models\Product::where('status', 1)->where('approval_status', 'approved')
        ->where('stock', '>', 0)->with('image')->inRandomOrder()->limit(4)->get();
    $totalOrderAmount = \App\Models\Order::where('customer_id', $customer->id)->sum('amount');
@endphp

<div class="sf-page">
    <div class="sf-container">
        <div class="sf-account">
            @include('frontEnd.layouts.customer.sidebar')

            <div>
                <div class="sf-stats" style="margin-bottom:20px">
                    <div class="sf-stat sf-stat--navy"><i class="fa-solid fa-box-open"></i><div><b>{{ $totalOrders }}</b><small>Total Orders</small></div></div>
                    <div class="sf-stat sf-stat--amber"><i class="fa-solid fa-hourglass-half"></i><div><b>{{ $pendingOrders }}</b><small>In Progress</small></div></div>
                    <div class="sf-stat sf-stat--green"><i class="fa-solid fa-circle-check"></i><div><b>{{ $completedOrders }}</b><small>Delivered</small></div></div>
                    <div class="sf-stat sf-stat--red"><i class="fa-solid fa-sack-dollar"></i><div><b>৳{{ number_format((float) $totalOrderAmount) }}</b><small>Total Spent</small></div></div>
                </div>

                <div class="sf-apanel">
                    <h3><i class="fa-solid fa-clock-rotate-left"></i> Recent Orders</h3>
                    @forelse($recentOrders as $order)
                        <div class="sf-order">
                            <div class="sf-order__head">
                                <span class="id">#{{ $order->invoice_id }}</span>
                                <span>{{ optional($order->created_at)->format('d M Y, h:i A') }}</span>
                                <span class="sf-badge {{ in_array($order->order_status, ['6']) ? 'sf-badge--green' : 'sf-badge--navy' }}">
                                    {{ optional($order->status)->name ?? 'Processing' }}
                                </span>
                            </div>
                            <div class="sf-order__body">
                                @php $firstItem = $order->orderdetails->first(); @endphp
                                @if($firstItem && $firstItem->product)
                                    <img src="{{ asset(optional($firstItem->product->image)->image ?? 'public/logo.png') }}" alt="" />
                                @endif
                                <div class="info">
                                    <b>{{ optional($firstItem->product)->name ?? 'Order items' }}</b>
                                    <small>{{ $order->orderdetails->count() }} item(s) · {{ optional($order->payment)->payment_method ?? 'COD' }}</small>
                                </div>
                                <div class="amt">
                                    <span class="sf-price"><span class="cur">৳</span>{{ number_format((float) $order->amount) }}</span>
                                </div>
                            </div>
                            <div class="sf-order__foot">
                                <a class="sf-btn sf-btn--outline sf-btn--sm" href="{{ route('customer.order_note', ['id' => $order->id]) }}"><i class="fa-regular fa-eye"></i> Details</a>
                                <a class="sf-btn sf-btn--dark sf-btn--sm" href="{{ route('customer.order_track') }}?invoice_id={{ $order->invoice_id }}"><i class="fa-solid fa-truck-fast"></i> Track</a>
                            </div>
                        </div>
                    @empty
                        <div class="sf-empty">
                            <i class="fa-solid fa-box-open"></i>
                            <h4>No orders yet</h4>
                            <p>Your orders will appear here once you place one.</p>
                            <a class="sf-btn sf-btn--dark" style="margin-top:16px" href="{{ route('shop') }}">Start Shopping</a>
                        </div>
                    @endforelse
                    @if($recentOrders->count())
                        <a class="sf-btn sf-btn--soft sf-btn--block" style="margin-top:16px" href="{{ route('customer.orders') }}">View All Orders</a>
                    @endif
                </div>

                @if($recommendedProducts->count())
                    <div class="sf-sec-head">
                        <div><h2 class="sf-sec-head__ttl">Recommended For You</h2></div>
                        <a class="sf-sec-head__link" href="{{ route('shop') }}">View All <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                    <div class="sf-pgrid">
                        @foreach($recommendedProducts as $product)
                            @include('frontEnd.layouts.partials.product-card', ['product' => $product])
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
