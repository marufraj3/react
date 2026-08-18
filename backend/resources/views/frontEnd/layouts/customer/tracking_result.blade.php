@extends('frontEnd.layouts.master')

@section('title', 'Order Tracking Result')

@section('content')
<div class="sf-page">
    <div class="sf-container">
        <nav class="sf-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('home') }}">Home</a><i class="fa-solid fa-angle-right"></i>
            <a href="{{ route('customer.order_track') }}">Track Order</a><i class="fa-solid fa-angle-right"></i>
            <span class="cur">Results ({{ $order->count() }})</span>
        </nav>

        @foreach($order as $value)
            <div class="sf-cpanel" style="max-width:860px;margin:0 auto 18px">
                <div class="sf-order__head" style="margin:-22px -22px 18px;border-radius:16px 16px 0 0">
                    <span class="id">Invoice #{{ $value->invoice_id }}</span>
                    <span>{{ optional($value->created_at)->format('d M Y, h:i A') }}</span>
                    <span class="sf-badge sf-badge--navy">{{ optional($value->status)->name ?? 'Processing' }}</span>
                </div>

                <div style="display:flex;gap:14px;flex-wrap:wrap;align-items:center;margin-bottom:20px">
                    @foreach($value->orderdetails->take(3) as $detail)
                        <img src="{{ asset(optional($detail->product->image)->image ?? 'public/logo.png') }}" alt="" style="width:60px;height:60px;border-radius:12px;object-fit:cover;border:1px solid var(--c-line)" />
                    @endforeach
                    <div>
                        <div style="font-size:13px;font-weight:700">{{ $value->orderdetails->count() }} item(s)</div>
                        <span class="sf-price" style="font-size:19px"><span class="cur">৳</span>{{ number_format((float) $value->amount) }}</span>
                    </div>
                    @if(!empty($value->shipping))
                        <div style="margin-left:auto;text-align:right">
                            <div style="font-size:13px;font-weight:800">{{ $value->shipping->name }}</div>
                            <div style="font-size:12px;color:var(--c-faint)">{{ $value->shipping->phone }}</div>
                            <div style="font-size:12px;color:var(--c-faint)">{{ $value->shipping->address }}</div>
                        </div>
                    @endif
                </div>

                <div class="sf-timeline">
                    @php
                        $statusId = (int) $value->order_status;
                        $steps = [
                            ['id' => 0, 'label' => 'Order Placed', 'desc' => 'We received your order successfully.'],
                            ['id' => 1, 'label' => 'Confirmed', 'desc' => 'Your order has been confirmed by our team.'],
                            ['id' => 2, 'label' => 'Packed', 'desc' => 'Your items are packed and ready to ship.'],
                            ['id' => 3, 'label' => 'Shipped', 'desc' => 'Your parcel is on the way with the courier.'],
                            ['id' => 6, 'label' => 'Delivered', 'desc' => 'The parcel has been delivered. Thank you!'],
                        ];
                    @endphp
                    @foreach($steps as $step)
                        <div class="sf-tl-item {{ $statusId >= $step['id'] ? 'done' : '' }} {{ $step['id'] === $statusId ? 'current' : '' }}">
                            <b>{{ $step['label'] }}</b>
                            <p>{{ $step['desc'] }}</p>
                        </div>
                    @endforeach
                </div>

                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:20px">
                    @if(Auth::guard('customer')->check() && Auth::guard('customer')->id() == $value->customer_id)
                        <a class="sf-btn sf-btn--outline sf-btn--sm" href="{{ route('customer.order_note', ['id' => $value->id]) }}">View Details</a>
                    @endif
                    @if(!empty(optional($contact)->hotline))
                        <a class="sf-btn sf-btn--green sf-btn--sm" href="tel:{{ $contact->hotline }}"><i class="fa-solid fa-phone"></i> Need help? {{ $contact->hotline }}</a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
