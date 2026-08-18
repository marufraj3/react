@php
    $builderSubtotal = (float) str_replace([',', '.00'], '', Cart::instance('shopping')->subtotal());
    $builderShipping = (float) Session::get('shipping', 0);
    $builderDiscount = (float) Session::get('discount', 0);
    $builderCoupon   = Session::get('coupon_code');
    $builderBumps    = $orderBumps ?? collect();
@endphp
<div class="cpb-live-checkout">
    @if($products->count() > 1)
        <div class="cpb-checkout-products">
            <div class="cpb-checkout-eyebrow">আপনার পছন্দের পণ্যটি বেছে নিন</div>
            <div class="cpb-compact-products">
                @foreach($products as $product)
                    <button type="button" class="cpb-compact-product {{ $loop->first ? 'is-selected' : '' }}" data-select-product="{{ $product->id }}">
                        <img src="{{ asset(optional($product->image)->image ?? 'public/uploads/default.webp') }}" alt="">
                        <span><strong>{{ Str::limit($product->name, 35) }}</strong><small>৳{{ number_format((float) $product->new_price, 0) }}</small></span>
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <div id="cpb-variant-picker" class="cpb-variant-picker" hidden>
        <div class="cpb-variant-field" data-size-field hidden>
            <label for="cpb-product-size">সাইজ বেছে নিন</label>
            <select id="cpb-product-size" data-product-size></select>
        </div>
        <div class="cpb-variant-field" data-color-field hidden>
            <label for="cpb-product-color">কালার বেছে নিন</label>
            <select id="cpb-product-color" data-product-color></select>
        </div>
    </div>

    <div class="cpb-checkout-columns">
        <section class="cpb-order-summary" aria-labelledby="cpb-summary-heading">
            <div class="cpb-checkout-card-head">
                <span>১</span>
                <div><strong id="cpb-summary-heading">আপনার অর্ডার</strong><small>পরিমাণ ও মোট মূল্য যাচাই করুন</small></div>
            </div>
            <div class="cartlist cpb-cartlist">
                <table class="cart_table table table-bordered text-center mb-0">
                    <thead><tr><th>প্রোডাক্ট</th><th style="width:120px">পরিমাণ</th><th style="width:95px">মূল্য</th></tr></thead>
                    <tbody>
                    @foreach(Cart::instance('shopping')->content() as $item)
                        <tr>
                            <td class="text-start">
                                <img src="{{ asset($item->options->image ?? 'public/uploads/default.webp') }}" width="42" height="42" alt=""> <strong>{{ Str::limit($item->name, 24) }}</strong>
                                @if(!empty($item->options->product_size) || !empty($item->options->product_color))
                                    <small class="d-block text-muted">
                                        @if(!empty($item->options->product_size)) Size: {{ $item->options->product_size }} @endif
                                        @if(!empty($item->options->product_color)) | Color: {{ $item->options->product_color }} @endif
                                    </small>
                                @endif
                            </td>
                            <td><div class="quantity"><button type="button" class="cart_decrement" data-id="{{ $item->rowId }}">−</button><input type="text" value="{{ $item->qty }}" readonly><button type="button" class="cart_increment" data-id="{{ $item->rowId }}">+</button></div></td>
                            <td>৳{{ number_format($item->price * $item->qty, 0) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                        <tr><th colspan="2" class="text-end">মোট</th><td id="net_total"><strong>{{ number_format($builderSubtotal, 0) }}</strong></td></tr>
                        <tr><th colspan="2" class="text-end">ডেলিভারি চার্জ</th><td id="cart_shipping_cost"><strong>{{ number_format($builderShipping, 0) }}</strong></td></tr>
                        @if($builderDiscount > 0)
                            <tr><th colspan="2" class="text-end">কুপন ছাড়</th><td id="cart_discount"><strong>−{{ number_format($builderDiscount, 0) }}</strong></td></tr>
                        @endif
                        <tr><th colspan="2" class="text-end">সর্বমোট</th><td id="grand_total"><strong>{{ number_format($builderSubtotal + $builderShipping - $builderDiscount, 0) }}</strong></td></tr>
                    </tfoot>
                </table>
            </div>

            {{-- ===== অর্ডার বাম্প — এক ক্লিকে অ্যাড-অন অফার ===== --}}
            @if($builderBumps->isNotEmpty())
                <div class="cpb-bumps" data-cpb-bumps data-bump-url="{{ route('cart.addBump') }}">
                    @foreach($builderBumps as $bump)
                        <div class="cpb-bump" data-cpb-bump="{{ $bump->id }}"
                             data-bump-product-id="{{ $bump->product_id }}"
                             data-bump-product-name="{{ $bump->product->name }}"
                             data-bump-price="{{ $bump->offerPrice() }}">
                            <label class="cpb-bump-check">
                                <input type="checkbox" data-cpb-bump-toggle="{{ $bump->id }}">
                                <span class="cpb-bump-box" aria-hidden="true"></span>
                                <span class="cpb-bump-body">
                                    <strong>{{ $bump->title ?: 'এই অফারটিও যোগ করুন' }}</strong>
                                    @if($bump->subtitle)
                                        <small>{{ $bump->subtitle }}</small>
                                    @endif
                                    <span class="cpb-bump-product">
                                        <img src="{{ asset(optional($bump->product->image)->image ?? 'public/uploads/default.webp') }}" width="44" height="44" alt="" loading="lazy">
                                        <span>
                                            <span class="cpb-bump-name">{{ Str::limit($bump->product->name, 40) }}</span>
                                            <span class="cpb-bump-price">
                                                <b>৳{{ number_format($bump->offerPrice(), 0) }}</b>
                                                @if($bump->savings() > 0)
                                                    <del>৳{{ number_format((float) ($bump->product->new_price ?? 0), 0) }}</del>
                                                    <em>৳{{ number_format($bump->savings(), 0) }} সাশ্রয়</em>
                                                @endif
                                            </span>
                                        </span>
                                    </span>
                                </span>
                            </label>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- ===== কুপন কোড ===== --}}
            <div class="cpb-coupon" data-cpb-coupon
                 data-apply-url="{{ route('coupon.apply') }}"
                 data-remove-url="{{ route('coupon.remove') }}">
                @if($builderCoupon)
                    <div class="cpb-coupon-applied" data-cpb-coupon-applied>
                        <span>✓ কুপন <b>{{ $builderCoupon }}</b> প্রয়োগ হয়েছে</span>
                        <button type="button" data-cpb-coupon-remove>বাতিল</button>
                    </div>
                @else
                    <div class="cpb-coupon-form">
                        <input type="text" id="cpb-coupon-code" placeholder="কুপন কোড থাকলে লিখুন" autocomplete="off" aria-label="কুপন কোড">
                        <button type="button" data-cpb-coupon-apply>প্রয়োগ</button>
                    </div>
                @endif
                <p class="cpb-coupon-msg" data-cpb-coupon-msg role="status" aria-live="polite" hidden></p>
            </div>

            <div class="cpb-checkout-assurance"><span>🔒 নিরাপদ অর্ডার</span><span>✓ ক্যাশ অন ডেলিভারি</span></div>
        </section>

        <section class="cpb-customer-form" aria-labelledby="cpb-form-heading">
            <div class="cpb-checkout-card-head">
                <span>২</span>
                <div><strong id="cpb-form-heading">ডেলিভারি তথ্য</strong><small>অর্ডার নিশ্চিত করতে সঠিক তথ্য দিন</small></div>
            </div>
            @if($errors->any())
                <div class="cpb-form-errors" role="alert">
                    <strong>তথ্যগুলো আবার যাচাই করুন:</strong>
                    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <form action="{{ route('customer.ordersave') }}" method="POST" data-cpb-order-form>
                @csrf
                <input type="hidden" name="payment_method" value="cod">
                <div class="cpb-form-field">
                    <label for="cpb-name">আপনার নাম <span>*</span></label>
                    <input id="cpb-name" type="text" name="name" value="{{ old('name') }}" placeholder="আপনার সম্পূর্ণ নাম" autocomplete="name" required>
                </div>
                <div class="cpb-form-field">
                    <label for="cpb-phone">মোবাইল নম্বর <span>*</span></label>
                    <input id="cpb-phone" type="tel" name="phone" value="{{ old('phone') }}" placeholder="01XXXXXXXXX" inputmode="numeric" pattern="0[0-9]{10}" maxlength="11" autocomplete="tel" required>
                </div>
                <div class="cpb-form-field">
                    <label for="cpb-address">সম্পূর্ণ ঠিকানা <span>*</span></label>
                    <textarea id="cpb-address" name="address" placeholder="জেলা, থানা, এলাকা/গ্রাম ও বাড়ির ঠিকানা" autocomplete="street-address" required>{{ old('address') }}</textarea>
                </div>
                <div class="cpb-form-field">
                    <label for="cpb-area">ডেলিভারি এরিয়া <span>*</span></label>
                    <select id="cpb-area" name="area" required>
                        @foreach(($shippingcharge ?? collect()) as $charge)
                            <option value="{{ $charge->id }}" {{ (string) old('area') === (string) $charge->id ? 'selected' : '' }}>{{ $charge->name }} — ৳{{ number_format((float) $charge->amount, 0) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="cpb-form-field">
                    <label for="cpb-note">অর্ডার নোট <small>(ঐচ্ছিক)</small></label>
                    <textarea id="cpb-note" name="order_note" rows="2" placeholder="বিশেষ কোনো নির্দেশনা থাকলে লিখুন">{{ old('order_note') }}</textarea>
                </div>
                <button class="cpb-place-order" type="submit"><span>অর্ডার কনফার্ম করুন</span><strong>→</strong></button>
                <p class="cpb-form-privacy">আপনার তথ্য শুধু অর্ডার প্রসেস করার জন্য ব্যবহার করা হবে।</p>
            </form>
        </section>
    </div>
</div>
