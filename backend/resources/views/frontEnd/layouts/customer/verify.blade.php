@extends('frontEnd.layouts.master')

@section('title', 'Verify Account')

@section('content')
<div class="sf-auth">
    <div class="sf-auth__deco"></div>
    <a class="sf-auth__back" href="{{ route('home') }}"><i class="fa-solid fa-arrow-left"></i> Back to Shop</a>

    <div class="sf-auth__card">
        <div style="width:72px;height:72px;border-radius:50%;background:var(--c-primary-50);color:var(--c-primary);font-size:28px;display:flex;align-items:center;justify-content:center;margin:0 auto 18px">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
        <h2>Verify Your Account</h2>
        <p class="sub">We sent a 6-digit OTP to your mobile number. Enter it below to activate your account.</p>

        @if($errors->any())
            <div class="sf-form-msg sf-form-msg--error">{{ $errors->first() }}</div>
        @endif

        <form action="{{ route('customer.account.verify') }}" method="POST">
            @csrf
            <div class="sf-field">
                <label>OTP Code <span class="req">*</span></label>
                <input type="number" name="otp" class="sf-input" style="font-size:22px;text-align:center;letter-spacing:8px;font-weight:800" value="{{ old('otp') }}" placeholder="••••••" required />
            </div>
            <button type="submit" class="sf-btn sf-btn--primary sf-btn--lg sf-btn--block"><i class="fa-solid fa-circle-check"></i> Verify & Activate</button>
        </form>

        <form action="{{ route('customer.resendotp') }}" method="POST" style="text-align:center;margin-top:14px">
            @csrf
            <button type="submit" class="sf-btn sf-btn--ghost sf-btn--sm">Didn't get the code? <b style="color:var(--c-primary)">Resend OTP</b></button>
        </form>
    </div>
</div>
@endsection
