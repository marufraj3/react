@extends('frontEnd.layouts.master')

@section('title', 'Refund — #' . $refund->refund_id)

@section('content')
<div class="sf-page">
    <div class="sf-container">
        <div class="sf-account">
            @include('frontEnd.layouts.customer.sidebar')

            <div class="sf-apanel" style="max-width:720px">
                <h3><i class="fa-solid fa-rotate-left"></i> Refund Details <span class="sf-badge {{ $refund->status == 'approved' || $refund->status == 'processed' ? 'sf-badge--green' : ($refund->status == 'rejected' ? 'sf-badge--accent' : 'sf-badge--amber') }}" style="font-size:11px">{{ ucfirst($refund->status) }}</span></h3>

                <div class="sf-timeline" style="margin:26px 0 30px">
                    <div class="sf-tl-item {{ in_array($refund->status, ['pending', 'approved', 'processed']) ? 'done' : '' }}">
                        <b>Request Submitted</b>
                        <p>{{ optional($refund->created_at)->format('d M Y, h:i A') }} — Refund ID #{{ $refund->refund_id }}</p>
                    </div>
                    <div class="sf-tl-item {{ in_array($refund->status, ['approved', 'processed']) ? 'done' : '' }} {{ $refund->status == 'approved' ? 'current' : '' }}">
                        <b>Approved</b>
                        <p>Our team reviewed and approved your refund request.</p>
                    </div>
                    <div class="sf-tl-item {{ $refund->status == 'processed' ? 'done' : '' }} {{ $refund->status == 'processed' ? 'current' : '' }}">
                        <b>Processed</b>
                        <p>Money has been sent to your account.</p>
                    </div>
                    @if($refund->status == 'rejected')
                        <div class="sf-tl-item">
                            <b style="color:var(--c-accent)">Rejected</b>
                            <p>This request was declined by our team.</p>
                        </div>
                    @endif
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div style="background:var(--c-bg);border-radius:var(--r-md);padding:16px">
                        <div style="font-size:11px;font-weight:800;letter-spacing:1px;text-transform:uppercase;color:var(--c-faint);margin-bottom:8px">Refund Amount</div>
                        <b style="font-size:21px;color:var(--c-accent)">৳{{ number_format((float) ($refund->amount + $refund->shipping_charge)) }}</b>
                        <div style="font-size:11.5px;color:var(--c-faint);margin-top:4px">Product ৳{{ number_format((float) $refund->amount) }} + Delivery ৳{{ number_format((float) $refund->shipping_charge) }}</div>
                    </div>
                    <div style="background:var(--c-bg);border-radius:var(--r-md);padding:16px">
                        <div style="font-size:11px;font-weight:800;letter-spacing:1px;text-transform:uppercase;color:var(--c-faint);margin-bottom:8px">Refund To</div>
                        <b style="font-size:14px">{{ ucfirst(str_replace('_', ' ', $refund->refund_method ?? 'N/A')) }}</b>
                        <div style="font-size:12.5px;color:var(--c-muted);margin-top:3px">{{ $refund->refund_account_name }}</div>
                        <div style="font-size:12.5px;color:var(--c-muted)">{{ $refund->refund_account }}</div>
                    </div>
                </div>

                <div style="margin-top:16px">
                    <div style="font-size:11px;font-weight:800;letter-spacing:1px;text-transform:uppercase;color:var(--c-faint);margin-bottom:8px">Reason</div>
                    <p style="font-size:13.5px;color:var(--c-muted)">{{ $refund->reason }}</p>
                </div>

                <div style="display:flex;gap:10px;margin-top:22px;flex-wrap:wrap">
                    @if($refund->status == 'pending')
                        <form action="{{ route('customer.refunds.cancel', $refund->id) }}" method="POST" style="display:inline" onsubmit="return confirm('Cancel this refund request?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="sf-btn sf-btn--outline" style="color:var(--c-accent)"><i class="fa-solid fa-xmark"></i> Cancel Request</button>
                        </form>
                    @endif
                    <a class="sf-btn sf-btn--ghost" href="{{ route('customer.refunds') }}">Back to Refunds</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
