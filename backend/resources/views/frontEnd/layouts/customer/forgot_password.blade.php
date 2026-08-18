@extends('frontEnd.layouts.master')

@section('title', 'Forgot Password')

@section('content')
<div class="sf-auth">
    <div class="sf-auth__deco"></div>
    <a class="sf-auth__back" href="{{ route('home') }}"><i class="fa-solid fa-arrow-left"></i> Back to Shop</a>

    <div class="sf-auth__card">
        <div style="width:72px;height:72px;border-radius:50%;background:var(--c-accent-50);color:var(--c-accent);font-size:28px;display:flex;align-items:center;justify-content:center;margin:0 auto 18px">
            <i class="fa-solid fa-key"></i>
        </div>
        <h2>Forgot Password?</h2>
        <p class="sub">No worries — enter your mobile number and we'll send you an OTP to reset your password.</p>

        @if($errors->any())
            <div class="sf-form-msg sf-form-msg--error">{{ $errors->first() }}</div>
        @endif

        <form action="{{ route('customer.forgot.verify') }}" method="POST">
            @csrf
            <div class="sf-field">
                <label>Mobile Number <span class="req">*</span></label>
                <input type="text" name="phone" class="sf-input" value="{{ old('phone') }}" minlength="11" maxlength="11" pattern="0[0-9]+" placeholder="017xxxxxxxx" required />
            </div>
            <button type="submit" class="sf-btn sf-btn--primary sf-btn--lg sf-btn--block"><i class="fa-solid fa-paper-plane"></i> Send OTP</button>
        </form>

        <div class="sf-auth__foot">Remembered it? <a href="{{ route('customer.login') }}">Back to login</a></div>
    </div>
</div>
@endsection
