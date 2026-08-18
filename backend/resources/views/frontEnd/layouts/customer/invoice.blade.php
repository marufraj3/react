@extends('frontEnd.layouts.master')

@section('title', 'Invoice — #' . $order->invoice_id)

@section('content')
<div class="sf-page">
    <div class="sf-container">
        <div class="sf-account">
            @include('frontEnd.layouts.customer.sidebar')

            <div class="sf-apanel" id="invoiceArea">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:14px;flex-wrap:wrap;margin-bottom:22px">
                    <div>
                        <h3 style="margin-bottom:4px">Invoice #{{ $order->invoice_id }}</h3>
                        <p class="sf-faint" style="font-size:12.5px">Placed on {{ optional($order->created_at)->format('d M Y, h:i A') }}</p>
                    </div>
                    <div style="text-align:right">
                        <span class="sf-badge sf-badge--navy">{{ optional($order->status)->name ?? 'Processing' }}</span>
                        <div style="font-size:12px;color:var(--c-faint);margin-top:6px">Payment: {{ optional($order->payment)->payment_method ?? 'COD' }}</div>
                    </div>
                </div>

                @if(!empty($order->shipping))
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:22px">
                        <div style="background:var(--c-bg);border-radius:var(--r-md);padding:14px 16px">
                            <div style="font-size:11px;font-weight:800;letter-spacing:1px;text-transform:uppercase;color:var(--c-faint);margin-bottom:6px">Deliver To</div>
                            <b style="font-size:14px">{{ $order->shipping->name }}</b>
                            <div style="font-size:12.5px;color:var(--c-muted);margin-top:4px">{{ $order->shipping->phone }}</div>
                            <div style="font-size:12.5px;color:var(--c-muted)">{{ $order->shipping->address }}</div>
                        </div>
                        <div style="background:var(--c-bg);border-radius:var(--r-md);padding:14px 16px">
                            <div style="font-size:11px;font-weight:800;letter-spacing:1px;text-transform:uppercase;color:var(--c-faint);margin-bottom:6px">Summary</div>
                            <div class="sf-summary__row" style="font-size:12.5px"><span>Subtotal</span><span>৳{{ number_format((float) (($order->amount + $order->discount) - $order->shipping_charge)) }}</span></div>
                            <div class="sf-summary__row" style="font-size:12.5px"><span>Shipping</span><span>৳{{ number_format((float) $order->shipping_charge) }}</span></div>
                            <div class="sf-summary__row total" style="font-size:13.5px"><span>Total</span><span class="sf-price"><span class="cur">৳</span>{{ number_format((float) $order->amount) }}</span></div>
                        </div>
                    </div>
                @endif

                <table style="width:100%;border-collapse:collapse;font-size:13px">
                    <thead>
                        <tr style="background:#f8f9fc;color:var(--c-faint);text-transform:uppercase;font-size:10.5px;letter-spacing:.6px">
                            <th style="padding:10px 12px;text-align:left">Product</th>
                            <th style="padding:10px 12px;text-align:center">Qty</th>
                            <th style="padding:10px 12px;text-align:right">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->orderdetails as $detail)
                            <tr style="border-top:1px solid var(--c-line)">
                                <td style="padding:11px 12px">
                                    <div style="display:flex;align-items:center;gap:10px">
                                        <img src="{{ asset(optional($detail->product->image)->image ?? optional($detail->image)->image ?? 'public/logo.png') }}" alt="" style="width:42px;height:42px;border-radius:9px;object-fit:cover;border:1px solid var(--c-line)" />
                                        <span style="font-weight:700">{{ $detail->product_name ?? optional($detail->product)->name }}</span>
                                    </div>
                                </td>
                                <td style="padding:11px 12px;text-align:center;font-weight:700">{{ $detail->qty }}</td>
                                <td style="padding:11px 12px;text-align:right;font-weight:800">৳{{ number_format((float) ($detail->total_price ?? ($detail->price * $detail->qty))) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div style="display:flex;gap:10px;margin-top:22px;flex-wrap:wrap">
                    <button type="button" class="sf-btn sf-btn--dark sf-btn--sm" onclick="window.print()"><i class="fa-solid fa-print"></i> Print Invoice</button>
                    <a class="sf-btn sf-btn--outline sf-btn--sm" href="{{ route('customer.orders') }}">Back to Orders</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
