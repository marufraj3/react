@php
    $subtotal = Cart::instance('shopping')->subtotal();
@endphp
<span class="sf-head-cart__ico">
    <i class="fa-solid fa-cart-shopping"></i>
    <b class="cart_count">{{ Cart::instance('shopping')->count() }}</b>
</span>
<span class="sf-head-cart__txt">
    <small>My Cart</small>
    <b>৳{{ number_format((float) str_replace(',', '', $subtotal)) }}</b>
</span>
