@extends('frontEnd.layouts.master')

@section('title', 'My Orders')

@section('content')
<div class="sf-page">
    <div class="sf-container">
        <div class="sf-account">
            @include('frontEnd.layouts.customer.sidebar')

            <div class="sf-apanel">
                <h3><i class="fa-solid fa-box-open"></i> My Orders <span class="sf-badge sf-badge--plain" style="font-size:11px">{{ $orders->total() }}</span></h3>

                @forelse($orders as $order)
                    <div class="sf-order">
                        <div class="sf-order__head">
                            <span class="id">#{{ $order->invoice_id }}</span>
                            <span>{{ optional($order->created_at)->format('d M Y, h:i A') }}</span>
                            <span>{{ $order->orderdetails->count() }} item(s)</span>
                            <span class="sf-badge {{ $order->order_status == '6' ? 'sf-badge--green' : 'sf-badge--navy' }}">{{ optional($order->status)->name ?? 'Processing' }}</span>
                        </div>
                        <div class="sf-order__body">
                            @php $firstItem = $order->orderdetails->first(); @endphp
                            @if($firstItem && $firstItem->product)
                                <img src="{{ asset(optional($firstItem->product->image)->image ?? 'public/logo.png') }}" alt="" />
                            @endif
                            <div class="info">
                                <b>{{ optional($firstItem->product)->name ?? 'Order items' }}</b>
                                <small>Qty: {{ $firstItem->qty ?? 1 }} · {{ optional($order->payment)->payment_method ?? 'COD' }}</small>
                            </div>
                            <div class="amt">
                                <span class="sf-price"><span class="cur">৳</span>{{ number_format((float) $order->amount) }}</span>
                            </div>
                        </div>
                        <div class="sf-order__foot">
                            <a class="sf-btn sf-btn--outline sf-btn--sm" href="{{ route('customer.order_note', ['id' => $order->id]) }}"><i class="fa-regular fa-eye"></i> Details</a>
                            <a class="sf-btn sf-btn--dark sf-btn--sm" href="{{ route('customer.order_track') }}?invoice_id={{ $order->invoice_id }}"><i class="fa-solid fa-truck-fast"></i> Track</a>
                            @if(in_array($order->order_status, ['6']))
                                <a class="sf-btn sf-btn--ghost sf-btn--sm" href="{{ route('customer.refunds.create', ['order_id' => $order->id]) }}"><i class="fa-solid fa-rotate-left"></i> Return</a>
                            @endif
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

                {{ $orders->onEachSide(1)->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection
