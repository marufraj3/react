@extends('layouts.app')
@section('title', 'Verify Email')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="sf-card-surface" style="padding:34px;text-align:center">
            <div style="width:74px;height:74px;border-radius:50%;background:var(--c-primary-50);color:var(--c-primary);font-size:30px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px"><i class="fa-solid fa-envelope-circle-check"></i></div>
            <h2 class="fw-bold mb-2">Verify Your Email Address</h2>
            @if (session('resent'))
                <div class="sf-form-msg sf-form-msg--success">A fresh verification link has been sent to your email.</div>
            @endif
            <p class="sf-muted" style="font-size:14px;margin-bottom:20px">Before proceeding, please check your email for a verification link.</p>
            <form method="POST" action="{{ route('verification.resend') }}" class="d-inline">
                @csrf
                <button type="submit" class="sf-btn sf-btn--primary">Resend Verification Email</button>
            </form>
        </div>
    </div>
</div>
@endsection
