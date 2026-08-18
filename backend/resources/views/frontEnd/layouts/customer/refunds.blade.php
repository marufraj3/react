@extends('frontEnd.layouts.master')

@section('title', 'My Refund Requests')

@section('content')
<div class="sf-page">
    <div class="sf-container">
        <div class="sf-account">
            @include('frontEnd.layouts.customer.sidebar')

            <div class="sf-apanel">
                <h3><i class="fa-solid fa-rotate-left"></i> Refund Requests</h3>

                @forelse($refunds as $refund)
                    <div class="sf-order">
                        <div class="sf-order__head">
                            <span class="id">#{{ $refund->refund_id }}</span>
                            <span>{{ optional($refund->created_at)->format('d M Y, h:i A') }}</span>
                            <span class="sf-badge {{ $refund->status == 'approved' || $refund->status == 'processed' ? 'sf-badge--green' : ($refund->status == 'rejected' ? 'sf-badge--accent' : 'sf-badge--amber') }}" style="margin-left:auto">
                                {{ ucfirst($refund->status) }}
                            </span>
                        </div>
                        <div class="sf-order__body">
                            <div class="info">
                                <b>Order #{{ optional($refund->order)->invoice_id ?? $refund->order_id }}</b>
                                <small>{{ Str::limit($refund->reason, 80) }}</small>
                            </div>
                            <div class="amt">
                                <span class="sf-price"><span class="cur">৳</span>{{ number_format((float) ($refund->amount + $refund->shipping_charge)) }}</span>
                            </div>
                        </div>
                        <div class="sf-order__foot">
                            <a class="sf-btn sf-btn--outline sf-btn--sm" href="{{ route('customer.refunds.show', $refund->id) }}"><i class="fa-regular fa-eye"></i> View</a>
                            @if($refund->status == 'pending')
                                <form action="{{ route('customer.refunds.cancel', $refund->id) }}" method="POST" style="display:inline" onsubmit="return confirm('Cancel this refund request?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="sf-btn sf-btn--ghost sf-btn--sm" style="color:var(--c-accent)"><i class="fa-solid fa-xmark"></i> Cancel</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="sf-empty">
                        <i class="fa-solid fa-rotate-left"></i>
                        <h4>No refund requests</h4>
                        <p>Delivered orders can be returned from the order list.</p>
                        <a class="sf-btn sf-btn--dark" style="margin-top:16px" href="{{ route('customer.orders') }}">View Orders</a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
