@extends('frontEnd.layouts.master')

@section('title', 'Order Successful — #' . $order->invoice_id)

@section('content')
@php
    $payment = \App\Models\Payment::where('order_id', $order->id)->orderBy('id', 'desc')->first();
    $paid = $payment ? (float) $payment->amount : 0;
@endphp

<div class="sf-page">
    <div class="sf-container">
        <div class="sf-success">
            <div class="sf-success__ico"><i class="fa-solid fa-check"></i></div>
            <h2>Thank You! Order Placed 🎉</h2>
            <p>Your order has been received successfully. We'll start processing it right away.</p>

            <div class="sf-success__meta">
                <div><small>ORDER ID</small><b>#{{ $order->invoice_id }}</b></div>
                <div><small>DATE</small><b>{{ optional($order->created_at)->format('d M Y, h:i A') }}</b></div>
                <div><small>TOTAL</small><b>৳{{ number_format((float) $order->amount) }}</b></div>
                @if($paid > 0)<div><small>PAID NOW</small><b>৳{{ number_format($paid) }}</b></div>@endif
            </div>

            <div class="sf-success__btns">
                <a class="sf-btn sf-btn--primary" href="{{ route('customer.order_track') }}?invoice_id={{ $order->invoice_id }}"><i class="fa-solid fa-truck-fast"></i> Track Order</a>
                <a class="sf-btn sf-btn--dark" href="{{ route('customer.order_note', ['id' => $order->id]) }}"><i class="fa-regular fa-eye"></i> Order Details</a>
                <a class="sf-btn sf-btn--outline" href="{{ route('shop') }}"><i class="fa-solid fa-bag-shopping"></i> Continue Shopping</a>
            </div>
        </div>
    </div>
</div>
@endsection
