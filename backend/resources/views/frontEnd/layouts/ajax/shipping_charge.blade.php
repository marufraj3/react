@php
    $subtotal = Cart::instance('shopping')->subtotal();
    $subtotalN = floatval(preg_replace('/[^\d.]/', '', $subtotal));
    $shipping = floatval(Session::get('shipping') ? Session::get('shipping') : 0);
    $discount = floatval(Session::get('discount') ? Session::get('discount') : 0);
    $total = $subtotalN + $shipping - $discount;
@endphp
<div class="sf-summary">
    <h4><i class="fa-solid fa-receipt" style="color:var(--c-primary);margin-right:8px"></i>Order Summary</h4>
    <div class="sf-summary__row"><span>Items ({{ Cart::instance('shopping')->count() }})</span><span>৳{{ number_format($subtotalN) }}</span></div>
    <div class="sf-summary__row"><span>Shipping</span><span>@if($shipping > 0)৳{{ number_format($shipping) }}@else<span class="free">FREE</span>@endif</span></div>
    @if($discount > 0)<div class="sf-summary__row"><span>Discount</span><span style="color:#087a45">− ৳{{ number_format($discount) }}</span></div>@endif
    <div class="sf-summary__row total"><span>Total</span><span class="sf-price"><span class="cur">৳</span>{{ number_format($total) }}</span></div>
    <a class="sf-btn sf-btn--primary sf-btn--lg sf-btn--block" href="{{ route('customer.checkout') }}">Proceed to Checkout <i class="fa-solid fa-arrow-right"></i></a>
    <a class="sf-btn sf-btn--ghost sf-btn--block" href="{{ route('shop') }}">Continue Shopping</a>
    <p class="sf-summary__note"><i class="fa-solid fa-lock" style="margin-right:4px"></i> Secure checkout · Cash on delivery available</p>
</div>
