@extends('layouts.app')
@section('title', 'Reset Password')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="sf-card-surface" style="padding:30px">
            <h2 class="fw-bold text-center mb-1">Set New Password</h2>
            <p class="text-center sf-faint mb-4" style="font-size:13.5px">Choose a strong password for your account.</p>
            @if($errors->any())<div class="sf-form-msg sf-form-msg--error">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="sf-field">
                    <label>Email Address <span class="req">*</span></label>
                    <input id="email" type="email" class="sf-input" name="email" value="{{ old('email') }}" required />
                </div>
                <div class="sf-field">
                    <label>New Password <span class="req">*</span></label>
                    <input id="password" type="password" class="sf-input" name="password" minlength="8" required />
                </div>
                <div class="sf-field">
                    <label>Confirm Password <span class="req">*</span></label>
                    <input id="password-confirm" type="password" class="sf-input" name="password_confirmation" required />
                </div>
                <button type="submit" class="sf-btn sf-btn--primary sf-btn--lg sf-btn--block">Reset Password</button>
            </form>
        </div>
    </div>
</div>
@endsection
