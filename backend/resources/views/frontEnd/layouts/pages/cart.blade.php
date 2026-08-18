@extends('frontEnd.layouts.master')

@section('title', 'Shopping Cart')

@section('content')
@php
    $subtotal = Cart::instance('shopping')->subtotal();
    $subtotalN = floatval(preg_replace('/[^\d.]/', '', $subtotal));
    view()->share('subtotal', str_replace(',', '', $subtotal));
    $shipping = floatval(Session::get('shipping') ? Session::get('shipping') : 0);
    $discount = floatval(Session::get('discount') ? Session::get('discount') : 0);
    $couponCode = Session::get('coupon_code');
    $grandTotal = $subtotalN + $shipping - $discount;
    $cartCount = Cart::instance('shopping')->count();
@endphp

<div class="sf-page">
    <div class="sf-container">

        <nav class="sf-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('home') }}">Home</a><i class="fa-solid fa-angle-right"></i>
            <span class="cur">Shopping Cart</span>
        </nav>

        <div class="sf-steps">
            <div class="sf-step active"><span class="n">1</span> Cart</div>
            <div class="sf-step"><span class="n">2</span> Checkout</div>
            <div class="sf-step"><span class="n">3</span> Done</div>
        </div>

        <div class="sf-cart-grid">
            {{-- ============ CART LIST ============ --}}
            <div class="sf-cart-table cartlist" id="cartlist">
                @if($cartCount > 0)
                    <div class="sf-cart-head">
                        <span>Product</span><span></span><span>Price</span><span>Quantity</span><span></span>
                    </div>
                    @foreach($data as $value)
                        <div class="sf-cart-row" data-product-id="{{ $value->id }}" data-product-name="{{ e($value->name) }}" data-price="{{ (float) $value->price }}">
                            <a href="{{ route('product', $value->options->slug ?? '#') }}">
                                <img src="{{ asset($value->options->image ?? 'public/uploads/default.webp') }}" alt="{{ $value->name }}" />
                            </a>
                            <div>
                                <div class="sf-cart-row__name"><a href="{{ route('product', $value->options->slug ?? '#') }}">{{ $value->name }}</a></div>
                                @if(!empty($value->options->product_size) || !empty($value->options->product_color))
                                    <div class="sf-cart-row__var">
                                        @if(!empty($value->options->product_size))Size: {{ $value->options->product_size }}@endif
                                        @if(!empty($value->options->product_size) && !empty($value->options->product_color)) · @endif
                                        @if(!empty($value->options->product_color))Color: {{ $value->options->product_color }}@endif
                                    </div>
                                @endif
                            </div>
                            <div class="sf-cart-row__price">
                                <span class="sf-price"><span class="cur">৳</span>{{ number_format((float) $value->price) }}</span>
                                @if(!empty($value->options->old_price) && $value->options->old_price > $value->price)
                                    <span class="sf-old-price">৳{{ number_format((float) $value->options->old_price) }}</span>
                                @endif
                            </div>
                            <div class="sf-qty">
                                <button type="button" class="cart_decrement" data-id="{{ $value->rowId }}" aria-label="Decrease">−</button>
                                <input type="text" value="{{ $value->qty }}" readonly />
                                <button type="button" class="cart_increment" data-id="{{ $value->rowId }}" aria-label="Increase">+</button>
                            </div>
                            <button class="sf-cart-remove cart_remove" data-id="{{ $value->rowId }}" aria-label="Remove"><i class="fa-regular fa-trash-can"></i></button>
                        </div>
                    @endforeach
                @else
                    <div class="sf-empty">
                        <i class="fa-solid fa-cart-shopping"></i>
                        <h4>Your cart is empty</h4>
                        <p>Browse our products and find something you love.</p>
                        <a class="sf-btn sf-btn--dark" style="margin-top:16px" href="{{ route('shop') }}">Start Shopping</a>
                    </div>
                @endif
            </div>

            {{-- ============ SUMMARY ============ --}}
            <div>
                <div class="sf-summary cart-summary">
                    <h4><i class="fa-solid fa-receipt" style="color:var(--c-primary);margin-right:8px"></i>Order Summary</h4>

                    @if($couponCode)
                        <div class="sf-coupon-applied">
                            <i class="fa-solid fa-ticket"></i> {{ $couponCode }} applied
                            <a href="{{ route('coupon.remove') }}" onclick="return confirm('Remove coupon?')">Remove</a>
                        </div>
                    @else
                        <form class="sf-coupon" action="{{ route('coupon.apply') }}" method="POST">
                            @csrf
                            <input class="sf-input" type="text" name="coupon_code" placeholder="Coupon code" required />
                            <button type="submit" class="sf-btn sf-btn--dark">Apply</button>
                        </form>
                    @endif

                    <div class="sf-summary__row"><span>Items ({{ $cartCount }})</span><span>৳{{ number_format($subtotalN) }}</span></div>
                    <div class="sf-summary__row"><span>Shipping</span><span>@if($shipping > 0)৳{{ number_format($shipping) }}@else<span class="free">FREE</span>@endif</span></div>
                    @if($discount > 0)<div class="sf-summary__row"><span>Discount</span><span style="color:#087a45">− ৳{{ number_format($discount) }}</span></div>@endif
                    <div class="sf-summary__row total"><span>Total</span><span class="sf-price"><span class="cur">৳</span>{{ number_format($grandTotal) }}</span></div>

                    <a href="{{ route('customer.checkout') }}" class="sf-btn sf-btn--primary sf-btn--lg sf-btn--block" id="checkoutButton">
                        Proceed to Checkout <i class="fa-solid fa-arrow-right"></i>
                    </a>
                    <a href="{{ route('shop') }}" class="sf-btn sf-btn--ghost sf-btn--block">Continue Shopping</a>
                    <p class="sf-summary__note"><i class="fa-solid fa-lock" style="margin-right:4px"></i> Secure checkout · bKash · Nagad · COD</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
window.dataLayer = window.dataLayer || [];
(function () {
    var cartItems = [
        @foreach($data as $item)
        { item_id: '{{ $item->id }}', item_name: @json($item->name), price: {{ (float) $item->price }}, quantity: {{ (int) $item->qty }} }@if(!$loop->last),@endif
        @endforeach
    ];
    var cartValue = {{ (float) $grandTotal }};
    var cartItemCount = {{ (int) $cartCount }};

    dataLayer.push({ event: 'view_cart', ecommerce: { currency: 'BDT', value: cartValue, items: cartItems } });
    if (typeof fbq === 'function') fbq('trackCustom', 'ViewCart', { value: cartValue, currency: 'BDT', num_items: cartItemCount });
    if (typeof ttq !== 'undefined') ttq.track('ViewContent', { content_type: 'product_group', value: cartValue, currency: 'BDT', quantity: cartItemCount });

    var checkoutBtn = document.getElementById('checkoutButton');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', function () {
            if (typeof fbq === 'function') fbq('track', 'InitiateCheckout', { value: cartValue, currency: 'BDT', num_items: cartItemCount, content_ids: cartItems.map(function (i) { return i.item_id; }) });
            dataLayer.push({ event: 'begin_checkout', ecommerce: { currency: 'BDT', value: cartValue, items: cartItems } });
        });
    }
})();
</script>
@endpush
