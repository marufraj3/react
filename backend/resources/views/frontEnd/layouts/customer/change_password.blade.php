@extends('frontEnd.layouts.master')

@section('title', 'Change Password')

@section('content')
<div class="sf-page">
    <div class="sf-container">
        <div class="sf-account">
            @include('frontEnd.layouts.customer.sidebar')

            <div class="sf-apanel" style="max-width:560px">
                <h3><i class="fa-solid fa-key"></i> Change Password</h3>
                <p class="sf-muted" style="font-size:13px;margin-bottom:20px">Keep your account secure — use a strong password you don't use elsewhere.</p>

                <form action="{{ route('customer.password_update') }}" method="POST">
                    @csrf
                    <div class="sf-field">
                        <label>Current Password <span class="req">*</span></label>
                        <input type="password" name="old_password" class="sf-input" placeholder="Enter current password" required />
                    </div>
                    <div class="sf-field">
                        <label>New Password <span class="req">*</span></label>
                        <input type="password" name="new_password" class="sf-input" placeholder="At least 8 characters" minlength="8" required />
                    </div>
                    <div class="sf-field">
                        <label>Confirm New Password <span class="req">*</span></label>
                        <input type="password" name="confirm_password" class="sf-input" placeholder="Re-enter new password" required />
                    </div>
                    <button type="submit" class="sf-btn sf-btn--primary sf-btn--lg sf-btn--block"><i class="fa-solid fa-shield-halved"></i> Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
