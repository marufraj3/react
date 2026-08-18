@extends('frontEnd.layouts.master')

@section('title', 'Request Refund — #' . $order->invoice_id)

@section('content')
<div class="sf-page">
    <div class="sf-container">
        <div class="sf-account">
            @include('frontEnd.layouts.customer.sidebar')

            <div class="sf-apanel" style="max-width:720px">
                <h3><i class="fa-solid fa-rotate-left"></i> Request Refund <span class="sf-badge sf-badge--navy" style="font-size:11px">#{{ $order->invoice_id }}</span></h3>

                <div style="display:flex;gap:12px;background:var(--c-bg);border-radius:var(--r-md);padding:14px 16px;margin-bottom:20px;align-items:center;flex-wrap:wrap">
                    @foreach($order->orderdetails->take(3) as $detail)
                        <img src="{{ asset(optional($detail->product->image)->image ?? 'public/logo.png') }}" alt="" style="width:52px;height:52px;border-radius:10px;object-fit:cover;border:1px solid var(--c-line)" />
                    @endforeach
                    <div>
                        <div style="font-size:13.5px;font-weight:800">{{ $order->orderdetails->count() }} item(s) in this order</div>
                        <div style="font-size:12.5px;color:var(--c-faint)">Order total: <b style="color:var(--c-accent)">৳{{ number_format((float) $order->amount) }}</b></div>
                    </div>
                </div>

                <form action="{{ route('customer.refunds.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="order_id" value="{{ $order->id }}" />
                    <input type="hidden" name="amount" value="{{ $order->amount }}" />

                    <div class="sf-field">
                        <label>Reason for Refund <span class="req">*</span></label>
                        <textarea name="reason" class="sf-textarea" required placeholder="Tell us why you want to return this order…"></textarea>
                    </div>
                    <div class="sf-field">
                        <label>Refund Method <span class="req">*</span></label>
                        <select name="refund_method" class="sf-select" required>
                            <option value="">Select refund method…</option>
                            <option value="bkash">bKash</option>
                            <option value="nagad">Nagad</option>
                            <option value="rocket">Rocket</option>
                            <option value="bank">Bank Transfer</option>
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="sf-field"><label>Account Name <span class="req">*</span></label>
                                <input type="text" name="refund_account_name" class="sf-input" required placeholder="Account holder name" /></div>
                        </div>
                        <div class="col-md-6">
                            <div class="sf-field"><label>Account Number <span class="req">*</span></label>
                                <input type="text" name="refund_account" class="sf-input" required placeholder="01XXXXXXXXX" /></div>
                        </div>
                    </div>

                    <button type="submit" class="sf-btn sf-btn--primary sf-btn--lg"><i class="fa-solid fa-paper-plane"></i> Submit Request</button>
                    <a class="sf-btn sf-btn--ghost" href="{{ route('customer.orders') }}">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
