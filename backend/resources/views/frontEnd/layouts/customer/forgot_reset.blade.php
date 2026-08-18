@extends('frontEnd.layouts.master')

@section('title', 'Reset Password')

@section('content')
<div class="sf-auth">
    <div class="sf-auth__deco"></div>
    <a class="sf-auth__back" href="{{ route('home') }}"><i class="fa-solid fa-arrow-left"></i> Back to Shop</a>

    <div class="sf-auth__card">
        <div style="width:72px;height:72px;border-radius:50%;background:var(--c-green-50);color:var(--c-green);font-size:28px;display:flex;align-items:center;justify-content:center;margin:0 auto 18px">
            <i class="fa-solid fa-lock-open"></i>
        </div>
        <h2>Set New Password</h2>
        <p class="sub">Enter the OTP we sent you and choose a new password.</p>

        @if($errors->any())
            <div class="sf-form-msg sf-form-msg--error">{{ $errors->first() }}</div>
        @endif

        <form action="{{ route('customer.forgot.store') }}" method="POST">
            @csrf
            <div class="sf-field">
                <label>OTP Code <span class="req">*</span></label>
                <input type="number" name="otp" class="sf-input" style="font-size:20px;text-align:center;letter-spacing:8px;font-weight:800" value="{{ old('otp') }}" required />
            </div>
            <div class="sf-field">
                <label>New Password <span class="req">*</span></label>
                <input type="password" name="password" class="sf-input" minlength="8" required />
            </div>
            <button type="submit" class="sf-btn sf-btn--primary sf-btn--lg sf-btn--block"><i class="fa-solid fa-shield-halved"></i> Reset Password</button>
        </form>

        <form action="{{ route('customer.forgot.resendotp') }}" method="POST" style="text-align:center;margin-top:14px">
            @csrf
            <button type="submit" class="sf-btn sf-btn--ghost sf-btn--sm">Resend OTP</button>
        </form>
    </div>
</div>
@endsection
