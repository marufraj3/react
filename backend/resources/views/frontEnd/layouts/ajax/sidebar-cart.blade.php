@php
    $subtotal = Cart::instance('shopping')->subtotal();
    $subtotalN = floatval(preg_replace('/[^\d.]/', '', $subtotal));
    $shipping = floatval(Session::get('shipping') ? Session::get('shipping') : 0);
    $discount = floatval(Session::get('discount') ? Session::get('discount') : 0);
@endphp

<div class="sf-cartdrawer__head">
    <i class="fa-solid fa-cart-shopping" style="color:#ffd9d4"></i>
    <b>Your Cart</b>
    <span class="sf-badge sf-badge--accent">{{ Cart::instance('shopping')->count() }} item(s)</span>
    <button type="button" onclick="closeSidebarCart()" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
</div>

<div class="sf-cartdrawer__body">
    @if(Cart::instance('shopping')->count() > 0)
        @foreach(Cart::instance('shopping')->content() as $value)
            <div class="sf-cart-row" style="grid-template-columns:76px 1fr auto;gap:12px;padding:12px;border:1px solid var(--c-line);border-radius:14px;margin-bottom:10px">
                <a href="{{ route('product', $value->options->slug ?? '#') }}">
                    <img src="{{ asset($value->options->image ?? 'public/uploads/default.webp') }}" alt="{{ $value->name }}" style="width:76px;height:76px">
                </a>
                <div>
                    <div class="sf-cart-row__name sf-clamp-2"><a href="{{ route('product', $value->options->slug ?? '#') }}">{{ $value->name }}</a></div>
                    @if(!empty($value->options->product_size) || !empty($value->options->product_color))
                        <div class="sf-cart-row__var">
                            @if(!empty($value->options->product_size))Size: {{ $value->options->product_size }}@endif
                            @if(!empty($value->options->product_size) && !empty($value->options->product_color)) · @endif
                            @if(!empty($value->options->product_color))Color: {{ $value->options->product_color }}@endif
                        </div>
                    @endif
                    <div class="sf-cart-row__price" style="margin-top:6px">
                        <span class="sf-price"><span class="cur">৳</span>{{ number_format((float) $value->price) }}</span>
                        @if(!empty($value->options->old_price) && $value->options->old_price > $value->price)
                            <span class="sf-old-price">৳{{ number_format((float) $value->options->old_price) }}</span>
                        @endif
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;align-items:center;gap:8px">
                    <div class="sf-qty" style="transform:scale(.86)">
                        <button type="button" data-qty="minus" class="cart_decrement" data-id="{{ $value->rowId }}" aria-label="Decrease">−</button>
                        <input type="text" value="{{ $value->qty }}" readonly>
                        <button type="button" data-qty="plus" class="cart_increment" data-id="{{ $value->rowId }}" aria-label="Increase">+</button>
                    </div>
                    <button class="sf-cart-remove cart_remove" data-id="{{ $value->rowId }}" aria-label="Remove"><i class="fa-regular fa-trash-can"></i></button>
                </div>
            </div>
        @endforeach
    @else
        <div class="sf-empty">
            <i class="fa-solid fa-cart-shopping"></i>
            <h4>Your cart is empty</h4>
            <p>Looks like you haven't added anything yet.</p>
            <a class="sf-btn sf-btn--dark" style="margin-top:16px" href="{{ route('shop') }}" onclick="closeSidebarCart()">Start Shopping</a>
        </div>
    @endif
</div>

@if(Cart::instance('shopping')->count() > 0)
    <div class="sf-cartdrawer__foot">
        <div class="sf-cartdrawer__row"><span>Subtotal</span><span>৳{{ number_format($subtotalN) }}</span></div>
        <div class="sf-cartdrawer__row"><span>Shipping</span><span>@if($shipping > 0)৳{{ number_format($shipping) }}@else<span class="free" style="color:#087a45;font-weight:800">FREE</span>@endif</span></div>
        @if($discount > 0)<div class="sf-cartdrawer__row"><span>Discount</span><span style="color:#087a45">− ৳{{ number_format($discount) }}</span></div>@endif
        <div class="sf-cartdrawer__row total"><span>Total</span><span class="sf-price"><span class="cur">৳</span>{{ number_format($subtotalN + $shipping - $discount) }}</span></div>
        <a class="sf-btn sf-btn--primary sf-btn--lg sf-btn--block" href="{{ route('customer.checkout') }}">Proceed to Checkout <i class="fa-solid fa-arrow-right"></i></a>
        <a class="sf-btn sf-btn--ghost sf-btn--block" href="{{ route('cart.show') }}" onclick="closeSidebarCart()">View Full Cart</a>
    </div>
@endif
