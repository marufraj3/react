@extends('frontEnd.layouts.master')

@section('title', 'Order Details — #' . $order->invoice_id)

@section('content')
<div class="sf-page">
    <div class="sf-container">
        <div class="sf-account">
            @include('frontEnd.layouts.customer.sidebar')

            <div>
                <div class="sf-apanel">
                    <h3><i class="fa-regular fa-receipt"></i> Order Details <span class="sf-badge sf-badge--navy" style="font-size:11px">#{{ $order->invoice_id }}</span></h3>

                    <div class="sf-stats" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px">
                        <div class="sf-stat sf-stat--navy"><i class="fa-regular fa-calendar"></i><div><small>Placed On</small><b style="font-size:13.5px">{{ optional($order->created_at)->format('d M Y, h:i A') }}</b></div></div>
                        <div class="sf-stat sf-stat--red"><i class="fa-solid fa-sack-dollar"></i><div><small>Total Amount</small><b style="font-size:15px">৳{{ number_format((float) $order->amount) }}</b></div></div>
                        <div class="sf-stat sf-stat--green"><i class="fa-solid fa-circle-info"></i><div><small>Status</small><b style="font-size:13.5px">{{ optional($order->status)->name ?? 'Processing' }}</b></div></div>
                    </div>

                    @if(!empty($order->admin_note))
                        <div class="sf-form-msg" style="background:var(--c-amber-50);color:#b26a00">
                            <b><i class="fa-solid fa-note-sticky"></i> Note from our team:</b>
                            <div class="sf-prose" style="margin-top:6px">{!! $order->admin_note !!}</div>
                        </div>
                    @endif

                    <h5 style="font-size:14px;margin-bottom:12px"><i class="fa-solid fa-box" style="color:var(--c-primary)"></i> Items</h5>
                    @foreach($order->orderdetails as $detail)
                        <div style="display:flex;gap:12px;padding:12px 0;border-bottom:1px solid var(--c-line);align-items:center">
                            <img src="{{ asset(optional($detail->product->image)->image ?? optional($detail->image)->image ?? 'public/logo.png') }}" alt="" style="width:58px;height:58px;border-radius:11px;object-fit:cover;border:1px solid var(--c-line)" />
                            <div style="flex:1;min-width:0">
                                <div style="font-size:13.5px;font-weight:700" class="sf-clamp-1">{{ $detail->product_name ?? optional($detail->product)->name }}</div>
                                <div style="font-size:12px;color:var(--c-faint)">Qty: {{ $detail->qty }}</div>
                            </div>
                            <div style="font-weight:800;color:var(--c-accent);font-size:14px">৳{{ number_format((float) ($detail->total_price ?? ($detail->price * $detail->qty))) }}</div>
                        </div>
                    @endforeach

                    <div style="max-width:320px;margin-left:auto;margin-top:16px">
                        <div class="sf-summary__row"><span>Subtotal</span><span>৳{{ number_format((float) (($order->amount + $order->discount) - $order->shipping_charge)) }}</span></div>
                        <div class="sf-summary__row"><span>Shipping</span><span>৳{{ number_format((float) $order->shipping_charge) }}</span></div>
                        @if($order->discount > 0)<div class="sf-summary__row"><span>Discount</span><span style="color:#087a45">− ৳{{ number_format((float) $order->discount) }}</span></div>@endif
                        <div class="sf-summary__row total"><span>Total</span><span class="sf-price"><span class="cur">৳</span>{{ number_format((float) $order->amount) }}</span></div>
                    </div>

                    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:20px">
                        <a class="sf-btn sf-btn--primary sf-btn--sm" href="{{ route('customer.order_track') }}?invoice_id={{ $order->invoice_id }}"><i class="fa-solid fa-truck-fast"></i> Track Order</a>
                        <a class="sf-btn sf-btn--outline sf-btn--sm" href="{{ route('customer.invoice', ['id' => $order->id]) }}"><i class="fa-solid fa-print"></i> View Invoice</a>
                        <a class="sf-btn sf-btn--ghost sf-btn--sm" href="{{ route('customer.orders') }}">Back to Orders</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
