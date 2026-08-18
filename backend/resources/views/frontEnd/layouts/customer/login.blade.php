@extends('frontEnd.layouts.master')

@section('title', 'Login')

@section('content')
<div class="sf-auth">
    <div class="sf-auth__deco"></div>
    <a class="sf-auth__back" href="{{ route('home') }}"><i class="fa-solid fa-arrow-left"></i> Back to Shop</a>

    <div class="sf-auth__card">
        <a class="sf-auth__logo" href="{{ route('home') }}">
            @if(!empty(optional($generalsetting)->dark_logo))
                <img src="{{ asset(optional($generalsetting)->dark_logo) }}" alt="{{ optional($generalsetting)->name }}" />
            @else
                <span class="sf-logo__mark">SG</span>
            @endif
        </a>
        <h2>Welcome Back 👋</h2>
        <p class="sub">Login to track orders, get offers and check out faster.</p>

        @if($errors->any())
            <div class="sf-form-msg sf-form-msg--error">{{ $errors->first() }}</div>
        @endif

        <form action="{{ route('customer.signin') }}" method="POST">
            @csrf
            <div class="sf-field">
                <label>Phone or Email <span class="req">*</span></label>
                <input type="text" name="login" class="sf-input" value="{{ old('login') }}" placeholder="01XXXXXXXXX or you@mail.com" required />
            </div>
            <div class="sf-field">
                <label>Password <span class="req">*</span></label>
                <input type="password" name="password" class="sf-input" placeholder="********" required />
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
                <label class="sf-check"><input type="checkbox" name="remember" /> Remember me</label>
                <a class="sf-link" style="font-size:13px" href="{{ route('customer.forgot.password') }}">Forgot password?</a>
            </div>
            <button type="submit" class="sf-btn sf-btn--primary sf-btn--lg sf-btn--block"><i class="fa-solid fa-right-to-bracket"></i> Login</button>
        </form>

        <div class="sf-auth__sep">or</div>

        <div class="sf-auth__alt">
            <a href="{{ route('customer.register') }}"><i class="fa-solid fa-user-plus"></i> Create Account</a>
            <a href="{{ route('customer.order_track') }}"><i class="fa-solid fa-truck-fast"></i> Track Order</a>
        </div>

        <div class="sf-auth__foot">New here? <a href="{{ route('customer.register') }}">Register for free</a></div>
    </div>
</div>
@endsection
