@php
    $subtotal = Cart::instance('shopping')->subtotal();
@endphp
<div class="sf-cart-head">
    <span>Product</span><span></span><span>Price</span><span>Quantity</span><span></span>
</div>
@foreach(Cart::instance('shopping')->content() as $value)
    <div class="sf-cart-row">
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
        <div class="sf-qty" style="transform:scale(.9)">
            <button type="button" class="cart_decrement" data-id="{{ $value->rowId }}" aria-label="Decrease">−</button>
            <input type="text" value="{{ $value->qty }}" readonly />
            <button type="button" class="cart_increment" data-id="{{ $value->rowId }}" aria-label="Increase">+</button>
        </div>
        <button class="sf-cart-remove cart_remove" data-id="{{ $value->rowId }}" aria-label="Remove"><i class="fa-regular fa-trash-can"></i></button>
    </div>
@endforeach
@if(Cart::instance('shopping')->count() == 0)
    <div class="sf-empty">
        <i class="fa-solid fa-cart-shopping"></i>
        <h4>Your cart is empty</h4>
        <p>Browse our products and find something you love.</p>
        <a class="sf-btn sf-btn--dark" style="margin-top:16px" href="{{ route('shop') }}">Start Shopping</a>
    </div>
@endif
