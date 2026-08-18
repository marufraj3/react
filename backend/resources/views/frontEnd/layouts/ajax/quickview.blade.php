@php
    $img = isset($data->image) && $data->image ? $data->image->image : 'public/logo.png';
    $rating = (int) round(optional($data->reviews)->avg('ratting') ?? 0);
@endphp
<div class="sf-modal" style="border-radius:20px;max-width:720px">
    <div class="sf-modal__head">
        <img src="{{ asset($img) }}" alt="{{ $data->name }}" />
        <b>{{ $data->name }}</b>
        <button class="close" type="button" onclick="$('#custom-modal').hide();$('#page-overlay').hide().removeClass('show')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="sf-modal__body">
        <div class="sf-pd__pricebar" style="margin-bottom:14px">
            <span class="sf-price"><span class="cur">৳</span>{{ number_format((float) $data->new_price) }}</span>
            @if(!empty($data->old_price) && $data->old_price > $data->new_price)
                <span class="sf-old-price">৳{{ number_format((float) $data->old_price) }}</span>
                <span class="sf-badge sf-badge--accent">-{{ round((($data->old_price - $data->new_price) * 100) / $data->old_price) }}%</span>
            @endif
        </div>
        <div class="sf-stars" style="font-size:13px;margin-bottom:12px">
            @for($i = 1; $i <= 5; $i++)<i class="fa-solid fa-star {{ $i <= $rating ? 'on' : '' }}"></i>@endfor
            <span class="sf-faint" style="margin-left:6px">({{ optional($data->reviews)->count() ?? 0 }} reviews)</span>
        </div>
        @if(!empty($data->short_description))
            <div class="sf-prose" style="max-height:110px;overflow:auto">{!! $data->short_description !!}</div>
        @endif
        <form action="{{ route('cart.store') }}" method="POST">
            @csrf
            <input type="hidden" name="id" value="{{ $data->id }}">
            <div style="display:flex;gap:14px;align-items:center;flex-wrap:wrap;margin-top:16px">
                <div class="sf-qty">
                    <button type="button" data-qty="minus">−</button>
                    <input type="text" name="qty" value="1" />
                    <button type="button" data-qty="plus">+</button>
                </div>
                <button type="submit" class="sf-btn sf-btn--primary sf-btn--lg cart_store" data-id="{{ $data->id }}" style="flex:1;min-width:180px">
                    <i class="fa-solid fa-cart-plus"></i> Add to Cart
                </button>
            </div>
        </form>
        <div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:14px;font-size:12.5px;font-weight:700;color:var(--c-muted)">
            <span><i class="fa-solid fa-shield-halved" style="color:var(--c-green);margin-right:5px"></i> 100% Secure Payment</span>
            <span><i class="fa-solid fa-truck-fast" style="color:var(--c-green);margin-right:5px"></i> Fast Delivery</span>
            <span><i class="fa-solid fa-rotate-left" style="color:var(--c-green);margin-right:5px"></i> Easy Return</span>
        </div>
        <a class="sf-btn sf-btn--outline sf-btn--block" style="margin-top:14px" href="{{ route('product', ['id' => $data->slug]) }}">View Full Details <i class="fa-solid fa-arrow-right"></i></a>
    </div>
</div>
