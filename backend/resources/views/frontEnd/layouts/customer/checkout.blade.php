@extends('frontEnd.layouts.master')

@section('title', 'Checkout')

@section('content')
@php
    $subtotal = floatval(preg_replace('/[^\d.]/', '', Cart::instance('shopping')->subtotal()));

    $requires_shipping = false;
    foreach (Cart::instance('shopping')->content() as $item) {
        $product = \App\Models\Product::find($item->id);
        if ($product && $product->is_digital != 1) { $requires_shipping = true; break; }
    }

    $hasAllFreeDelivery = \App\Http\Controllers\Frontend\ShoppingController::hasAllFreeDeliveryProducts();

    if ($requires_shipping && !$hasAllFreeDelivery) {
        $shipping = Session::get('shipping') ? Session::get('shipping') : 0;
    } else {
        $shipping = 0;
        Session::put('shipping', 0);
    }

    $discount = Session::get('discount', 0);
    $grand_total = $subtotal + $shipping - $discount;

    $cartItemsForJs = [];
    $hasDigital = false;
    foreach (Cart::instance('shopping')->content() as $item) {
        $p = \App\Models\Product::find($item->id);
        if ($p && $p->is_digital == 1) { $hasDigital = true; }
        $cartItemsForJs[] = [
            'id' => $item->id,
            'name' => $item->name,
            'qty' => $item->qty,
            'price' => (float) $item->price,
            'image' => asset($item->options->image ?? ''),
            'link' => isset($item->options->slug) ? url('/product/' . $item->options->slug) : '#',
            'is_digital' => (int) ($p->is_digital ?? 0),
            'free_delivery' => (int) ($p->free_delivery ?? 0),
        ];
    }

    $advance_amount = \App\Http\Controllers\Frontend\ShoppingController::getCartAdvanceAmount();
    $hasAdvance = $advance_amount > 0;
    $payable_now = $hasAdvance ? $advance_amount : $grand_total;
    $due_amount = $hasAdvance ? ($grand_total - $advance_amount) : 0;
    $customer = Auth::guard('customer')->user();
@endphp

<div class="sf-page">
    <div class="sf-container">

        <nav class="sf-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('home') }}">Home</a><i class="fa-solid fa-angle-right"></i>
            <a href="{{ route('cart.show') }}">Cart</a><i class="fa-solid fa-angle-right"></i>
            <span class="cur">Checkout</span>
        </nav>

        <div class="sf-steps">
            <div class="sf-step done"><span class="n"><i class="fa-solid fa-check"></i></span> Cart</div>
            <div class="sf-step active"><span class="n">2</span> Checkout</div>
            <div class="sf-step"><span class="n">3</span> Done</div>
        </div>

        @if(Cart::instance('shopping')->count() == 0)
            <div class="sf-empty sf-card-surface">
                <i class="fa-solid fa-cart-shopping"></i>
                <h4>Your cart is empty</h4>
                <p>Add some products before checking out.</p>
                <a class="sf-btn sf-btn--dark" style="margin-top:16px" href="{{ route('shop') }}">Start Shopping</a>
            </div>
        @else
        <form id="checkout-form" action="{{ route('customer.ordersave') }}" method="POST">
            @csrf
            <div class="sf-checkout">

                {{-- ============ LEFT: FORMS ============ --}}
                <div>
                    <div class="sf-cpanel">
                        <h4><span class="n">1</span> Shipping Information</h4>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="sf-field">
                                    <label>Full Name <span class="req">*</span></label>
                                    <input type="text" name="name" class="sf-input" value="{{ optional($customer)->name ?? old('name') }}" placeholder="e.g. Rahim Uddin" required />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="sf-field">
                                    <label>Mobile Number <span class="req">*</span></label>
                                    <input type="text" name="phone" class="sf-input" minlength="11" maxlength="11" pattern="0[0-9]+" value="{{ optional($customer)->phone ?? old('phone') }}" placeholder="017xxxxxxxx" required />
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="sf-field">
                                    <label>Complete Address <span class="req">*</span></label>
                                    <input type="text" name="address" class="sf-input" value="{{ optional($customer)->address ?? old('address') }}" placeholder="House, Road, Area, District" required />
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="sf-field">
                                    <label>Delivery Area <span class="req">*</span></label>
                                    @if($requires_shipping)
                                        <select id="area" class="sf-select select2" name="area" required>
                                            <option value="">Select delivery area…</option>
                                            @foreach($shippingcharge as $value)
                                                <option value="{{ $value->id }}" data-charge="{{ $value->amount }}" {{ Session::get('shipping_id') == $value->id ? 'selected' : '' }}>
                                                    {{ $value->name }} — ৳{{ number_format((float) $value->amount) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input type="text" class="sf-input" value="Digital Product (No Shipping Charge)" readonly disabled style="background:#f3f4f6" />
                                        <input type="hidden" name="area" value="free_shipping" />
                                    @endif
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="sf-field" style="margin-bottom:0">
                                    <label>Order Note (optional)</label>
                                    <textarea name="order_note" id="order_note" class="sf-textarea" style="min-height:70px" placeholder="Anything special we should know about delivery…">{{ $order_note ?? '' }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sf-cpanel">
                        <h4><span class="n">2</span> Payment Method</h4>

                        @if($hasAdvance)
                            <div class="sf-form-msg sf-form-msg--error" style="background:var(--c-amber-50);color:#b26a00">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                Advance payment required: <b>৳{{ number_format($advance_amount, 2) }}</b> must be paid now for this order.
                            </div>
                        @endif

                        @if(!$hasDigital && !$hasAdvance)
                            <label class="sf-pay-opt">
                                <input type="radio" name="payment_method" value="cod" checked required />
                                <span class="sf-pay-opt__body">
                                    <i class="fa-solid fa-hand-holding-dollar" style="font-size:22px;color:var(--c-green)"></i>
                                    <span><b>Cash on Delivery</b><small>Pay when you receive your product</small></span>
                                </span>
                                <i class="fa-solid fa-circle-check"></i>
                            </label>
                        @endif

                        @if($bkash_gateway)
                            <label class="sf-pay-opt">
                                <input type="radio" name="payment_method" value="bkash" required />
                                <span class="sf-pay-opt__body">
                                    <img src="{{ asset('public/frontEnd/images/bkash-logo.png') }}" alt="bKash" />
                                    <span><b>bKash</b><small>Pay with your bKash account</small></span>
                                </span>
                                <i class="fa-solid fa-circle-check"></i>
                            </label>
                        @endif

                        @if($shurjopay_gateway)
                            <label class="sf-pay-opt">
                                <input type="radio" name="payment_method" value="shurjopay" required />
                                <span class="sf-pay-opt__body">
                                    <img src="{{ asset('public/frontEnd/images/shurjoPay.png') }}" alt="ShurjoPay" />
                                    <span><b>Online Payment</b><small>Cards & mobile banking via ShurjoPay</small></span>
                                </span>
                                <i class="fa-solid fa-circle-check"></i>
                            </label>
                        @endif

                        @if($uddoktapay_gateway)
                            <label class="sf-pay-opt">
                                <input type="radio" name="payment_method" value="uddoktapay" required />
                                <span class="sf-pay-opt__body">
                                    <img src="{{ asset('public/frontEnd/images/uddokta.png') }}" alt="UddoktaPay" />
                                    <span><b>UddoktaPay</b><small>Mobile banking payment gateway</small></span>
                                </span>
                                <i class="fa-solid fa-circle-check"></i>
                            </label>
                        @endif

                        @if($aamarpay_gateway)
                            <label class="sf-pay-opt">
                                <input type="radio" name="payment_method" value="aamarpay" required />
                                <span class="sf-pay-opt__body">
                                    <img src="{{ asset('public/frontEnd/images/aamarpay.png') }}" alt="aamarPay" onerror="this.style.display='none'" />
                                    <span><b>aamarPay</b><small>Cards & mobile banking payment</small></span>
                                </span>
                                <i class="fa-solid fa-circle-check"></i>
                            </label>
                        @endif

                        <div id="payment-error" class="sf-form-error" style="display:none">
                            <i class="fa-solid fa-circle-exclamation"></i> Please select a payment method to complete your order.
                        </div>
                    </div>
                </div>

                {{-- ============ RIGHT: SUMMARY ============ --}}
                <div>
                    <div class="sf-summary">
                        <h4><i class="fa-solid fa-bag-shopping" style="color:var(--c-primary);margin-right:8px"></i>Order Summary ({{ Cart::instance('shopping')->count() }})</h4>

                        <div class="cartlist" style="max-height:340px;overflow-y:auto;margin:0 -4px 12px;padding:0 4px">
                            @foreach(Cart::instance('shopping')->content() as $value)
                                <div style="display:flex;gap:11px;padding:10px 0;border-bottom:1px solid var(--c-line);align-items:center">
                                    <a href="{{ route('product', $value->options->slug) }}" style="flex-shrink:0">
                                        <img src="{{ asset($value->options->image) }}" alt="{{ $value->name }}" style="width:54px;height:54px;border-radius:10px;object-fit:cover;border:1px solid var(--c-line)" />
                                    </a>
                                    <div style="flex:1;min-width:0">
                                        <a href="{{ route('product', $value->options->slug) }}" class="sf-clamp-1" style="font-size:13px;font-weight:700;color:var(--c-text);display:block">{{ $value->name }}</a>
                                        <div style="font-size:11px;color:var(--c-faint);font-weight:600">
                                            @if($value->options->product_size)Size: {{ $value->options->product_size }}@endif
                                            @if($value->options->product_color) · Color: {{ $value->options->product_color }}@endif
                                        </div>
                                        <div style="display:flex;align-items:center;gap:8px;margin-top:4px">
                                            <span style="font-size:13px;font-weight:800;color:var(--c-accent)">৳{{ number_format((float) ($value->price * $value->qty)) }}</span>
                                            <span style="font-size:11px;color:var(--c-faint)">× {{ $value->qty }}</span>
                                        </div>
                                    </div>
                                    <button type="button" class="sf-cart-remove cart_remove" data-id="{{ $value->rowId }}" aria-label="Remove" style="flex-shrink:0"><i class="fa-regular fa-trash-can"></i></button>
                                </div>
                            @endforeach
                        </div>

                        @if(!Session::has('coupon_code'))
                            <div class="sf-coupon">
                                <input type="text" id="coupon_input" class="sf-input" placeholder="Have a coupon? Enter code" />
                                <button type="button" class="sf-btn sf-btn--dark" onclick="submitCoupon()">Apply</button>
                            </div>
                        @else
                            <div class="sf-coupon-applied">
                                <i class="fa-solid fa-ticket"></i> Coupon <b>{{ Session::get('coupon_code') }}</b> applied!
                                <a href="{{ route('coupon.remove') }}">Remove</a>
                            </div>
                        @endif

                        <div class="sf-summary__row"><span>Subtotal</span><span id="subtotalAmount">৳{{ number_format($subtotal, 2) }}</span></div>
                        <div class="sf-summary__row"><span>Delivery Charge</span><span id="shippingAmount">৳{{ number_format($shipping, 2) }}</span></div>
                        @if($discount > 0)
                            <div class="sf-summary__row"><span>Coupon Discount</span><span style="color:#087a45" id="discountAmount">− ৳{{ number_format($discount, 2) }}</span></div>
                        @endif
                        <div class="sf-summary__row total"><span>Total</span><span class="sf-price" id="grandTotalAmount"><span class="cur">৳</span>{{ number_format($grand_total, 2) }}</span></div>

                        @if($hasAdvance)
                            <div style="margin-top:10px;padding:12px 14px;background:var(--c-amber-50);border-radius:var(--r-sm);font-size:12.5px;font-weight:700">
                                <div class="sf-summary__row" style="color:#087a45"><span>Advance (Pay Now)</span><span id="advanceAmountCell">৳{{ number_format($advance_amount, 2) }}</span></div>
                                <div class="sf-summary__row" style="color:var(--c-accent)"><span>Due (On Delivery)</span><span id="dueAmountCell">৳{{ number_format($due_amount, 2) }}</span></div>
                            </div>
                        @endif

                        <button type="submit" class="sf-btn sf-btn--primary sf-btn--lg sf-btn--block">
                            <i class="fa-solid fa-lock"></i> Confirm Order
                        </button>
                        <p class="sf-summary__note"><i class="fa-solid fa-shield-halved" style="margin-right:4px"></i> 100% secure checkout process</p>
                    </div>
                </div>
            </div>
        </form>
        @endif
    </div>
</div>

{{-- Hidden coupon form (outside main form) --}}
<form id="coupon-form" action="{{ route('coupon.apply') }}" method="POST" style="display:none;">
    @csrf
    <input type="hidden" name="coupon_code" id="hidden_coupon_code" />
</form>
@endsection

@push('script')
<script src="{{ asset('public/frontEnd/js/select2.min.js') }}"></script>
<script>
    function submitCoupon() {
        var code = document.getElementById('coupon_input').value;
        if (code) {
            document.getElementById('hidden_coupon_code').value = code;
            document.getElementById('coupon-form').submit();
        } else {
            if (typeof toastr !== 'undefined') toastr.error('Please enter a coupon code');
            else alert('Please enter a coupon code');
        }
    }

    let incompleteOrderTimer;
    let isSubmitting = false;

    $(document).ready(function () {
        $(".select2").select2({ width: '100%', minimumResultsForSearch: -1 });

        // Cart operations on checkout → reload
        $(document).on('click', '.cart_remove', function (e) {
            e.preventDefault(); e.stopImmediatePropagation();
            var id = $(this).data("id");
            if (id) {
                $("#loading").show();
                $.ajax({
                    type: "GET", url: "{{ route('cart.remove') }}", data: { id: id },
                    success: function () { window.location.reload(); },
                    error: function () { window.location.reload(); }
                });
            }
        });

        const baseSubtotal = parseFloat("{{ $subtotal ?? 0 }}");
        const baseDiscount = parseFloat("{{ $discount ?? 0 }}");
        const advanceAmount = parseFloat("{{ $advance_amount ?? 0 }}");
        const hasAdvance = @json($hasAdvance ?? false);
        const cartItems = @json($cartItemsForJs ?? []);
        const hasAllFreeDelivery = @json($hasAllFreeDelivery ?? false);

        function checkFreeDelivery() {
            let allFreeDelivery = true;
            for (let i = 0; i < cartItems.length; i++) {
                let item = cartItems[i];
                if (item.is_digital == 1) continue;
                if (item.free_delivery != 1) { allFreeDelivery = false; break; }
            }
            return allFreeDelivery;
        }

        function refreshTotals(shippingCharge) {
            var grandTotal = baseSubtotal + shippingCharge - baseDiscount;
            var dueAmount = hasAdvance ? (grandTotal - advanceAmount) : 0;
            $('#shippingAmount').text('৳ ' + shippingCharge.toFixed(2));
            $('#grandTotalAmount').html('<span class="cur">৳</span>' + grandTotal.toFixed(2));
            if (hasAdvance) {
                $('#dueAmountCell').text('৳ ' + dueAmount.toFixed(2));
            }
        }

        $('#area').on('change', function () {
            var selectedCharge = parseFloat($('option:selected', this).attr('data-charge')) || 0;
            var isFreeDelivery = checkFreeDelivery();
            var shippingCharge = isFreeDelivery ? 0 : selectedCharge;
            refreshTotals(shippingCharge);
            if (isFreeDelivery) {
                $.get('{{ route("shipping.charge") }}', { id: 'free_delivery' });
            } else {
                $.get('{{ route("shipping.charge") }}', { id: $(this).val() });
            }
            saveIncompleteOrder();
        });

        $(document).ready(function () {
            var isFreeDeliveryOnLoad = hasAllFreeDelivery || checkFreeDelivery();
            if (isFreeDeliveryOnLoad) {
                refreshTotals(0);
                $.get('{{ route("shipping.charge") }}', { id: 'free_delivery' });
            } else {
                var currentShipping = parseFloat($('#shippingAmount').text().replace(/[৳,\s]/g, '').trim()) || 0;
                refreshTotals(currentShipping);
            }
        });

        function saveIncompleteOrder() {
            if (isSubmitting) return;
            if (incompleteOrderTimer) clearTimeout(incompleteOrderTimer);
            incompleteOrderTimer = setTimeout(function () {
                var name = $('input[name="name"]').val();
                var phone = $('input[name="phone"]').val();
                var address = $('input[name="address"]').val();
                if (!name || !phone || !address) return;

                var selectedCharge = parseFloat($('#area option:selected').attr('data-charge')) || 0;
                var isFreeDelivery = checkFreeDelivery();
                var shippingCharge = isFreeDelivery ? 0 : selectedCharge;
                var total = (baseSubtotal + shippingCharge - baseDiscount).toFixed(2);

                $.ajax({
                    url: '{{ route("incomplete.order.store") }}',
                    type: 'POST',
                    contentType: 'application/json',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: JSON.stringify({ name: name, phone: phone, address: address, items: cartItems, total_amount: total })
                });
            }, 2000);
        }

        $('#checkout-form input, #checkout-form select, #checkout-form textarea').on('input change', function () {
            if ($(this).attr('name') !== 'payment_method') saveIncompleteOrder();
        });

        $('#checkout-form').on('submit', function (e) {
            var paymentMethod = $('input[name="payment_method"]:checked').val();
            if (!paymentMethod) {
                e.preventDefault();
                toastr.error('Please select a payment method', 'Error');
                $('#payment-error').show();
                return false;
            } else {
                $('#payment-error').hide();
                isSubmitting = true;
                if (incompleteOrderTimer) clearTimeout(incompleteOrderTimer);
            }
        });

        $('input[name="payment_method"]').on('change', function () { $('#payment-error').hide(); });
    });

    /* ---------- Tracking ---------- */
    window.dataLayer = window.dataLayer || [];
    (function () {
        const items = @json($cartItemsForJs);
        const hasAdvance = @json($hasAdvance);
        const advanceAmount = parseFloat("{{ $advance_amount }}") || 0;
        const grandTotal = parseFloat("{{ $grand_total }}") || 0;
        const payableNow = hasAdvance ? advanceAmount : grandTotal;
        const coupon = @json(Session::get('coupon_code', null));

        const ga4Items = items.map(function (item, index) {
            return { item_id: String(item.id), item_name: item.name, quantity: Number(item.qty), price: Number(item.price), index: index };
        });

        if (ga4Items.length) {
            dataLayer.push({ ecommerce: null });
            dataLayer.push({ event: "begin_checkout", ecommerce: { currency: "BDT", value: payableNow, coupon: coupon, items: ga4Items } });
        }

        if (typeof fbq === "function" && items.length) {
            fbq("track", "InitiateCheckout", {
                value: payableNow, currency: "BDT", num_items: items.length,
                content_ids: items.map(function (i) { return i.id; }),
                contents: items.map(function (i) { return { id: i.id, quantity: i.qty, item_price: i.price }; }),
                coupon: coupon || undefined
            });
        }

        document.addEventListener("DOMContentLoaded", function () {
            var form = document.getElementById("checkout-form");
            if (!form) return;
            form.addEventListener("submit", function () {
                var paymentInput = form.querySelector('input[name="payment_method"]:checked');
                var paymentMethod = paymentInput ? paymentInput.value : null;
                dataLayer.push({ ecommerce: null });
                dataLayer.push({ event: "add_payment_info", payment_type: paymentMethod, ecommerce: { currency: "BDT", value: payableNow, coupon: coupon, items: ga4Items } });
                if (typeof fbq === "function" && items.length) {
                    fbq("track", "AddPaymentInfo", { value: payableNow, currency: "BDT", payment_method: paymentMethod, num_items: items.length, content_ids: items.map(function (i) { return i.id; }) });
                }
            });
        });
    })();
</script>
@endpush
