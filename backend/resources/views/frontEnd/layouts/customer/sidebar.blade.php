{{-- Shared customer account navigation (used inside sf-account layout) --}}
@php
    $customer = Auth::guard('customer')->user();
    $pendingOrdersCount = \App\Models\Order::where('customer_id', $customer->id)
        ->whereNotIn('order_status', ['6', '11'])
        ->count();
@endphp

<div class="sf-account__side">
    <div class="sf-account__me">
        @if($customer->image)
            <img src="{{ asset($customer->image) }}" alt="{{ $customer->name }}" />
        @else
            <span class="ava">{{ strtoupper(substr($customer->name ?? 'U', 0, 1)) }}</span>
        @endif
        <div>
            <b>{{ $customer->name }}</b>
            <small>{{ $customer->phone }}</small>
        </div>
    </div>
    <nav class="sf-account__nav">
        <a href="{{ route('customer.account') }}" class="{{ request()->is('customer/account') ? 'active' : '' }}"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
        <a href="{{ route('customer.orders') }}" class="{{ request()->is('customer/orders') || request()->is('customer/invoice*') || request()->is('customer/order-success*') ? 'active' : '' }}">
            <i class="fa-solid fa-box-open"></i> My Orders
            @if($pendingOrdersCount > 0)<span class="sf-badge sf-badge--accent" style="margin-left:auto;font-size:10px">{{ $pendingOrdersCount }}</span>@endif
        </a>
        <a href="{{ route('customer.order_track') }}" class="{{ request()->is('customer/order-track*') ? 'active' : '' }}"><i class="fa-solid fa-truck-fast"></i> Track Order</a>
        <a href="{{ route('customer.refunds') }}" class="{{ request()->is('customer/refunds*') ? 'active' : '' }}"><i class="fa-solid fa-rotate-left"></i> Refund Requests</a>
        <a href="{{ route('complaint') }}" class="{{ request()->is('complaint') ? 'active' : '' }}"><i class="fa-solid fa-headset"></i> Support Ticket</a>
        <a href="{{ route('customer.profile_edit') }}" class="{{ request()->is('customer/profile-edit') ? 'active' : '' }}"><i class="fa-solid fa-user-pen"></i> Edit Profile</a>
        <a href="{{ route('customer.change_pass') }}" class="{{ request()->is('customer/change-password') ? 'active' : '' }}"><i class="fa-solid fa-key"></i> Change Password</a>
        <a class="danger" href="{{ route('customer.logout') }}" onclick="event.preventDefault();document.getElementById('sfAccountLogout').submit();"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        <form id="sfAccountLogout" action="{{ route('customer.logout') }}" method="POST" style="display:none">@csrf</form>
    </nav>
</div>
