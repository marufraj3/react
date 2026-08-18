@extends('frontEnd.layouts.master')

@section('title', 'Contact Us')

@section('content')
<div class="sf-page">
    <div class="sf-container">

        <div class="sf-page-head" style="border-radius:var(--r-lg);margin-top:18px">
            <div class="sf-container">
                <h1><i class="fa-solid fa-headset" style="color:#ffb02e;margin-right:10px"></i>Contact Us</h1>
                <p style="color:#c3cdea;font-size:14px;margin-top:6px">We'd love to hear from you — questions, feedback or order help.</p>
            </div>
        </div>

        <div class="sf-contact" style="margin-top:22px">
            <div class="sf-contact__cards">
                @if(!empty(optional($contact)->hotline))
                    <div class="sf-contact-card">
                        <i class="fa-solid fa-phone"></i>
                        <div><b>Call Us</b>
                            <a href="tel:{{ $contact->hotline }}">{{ $contact->hotline }}</a>
                            <p>Every day, 9 AM – 10 PM</p>
                        </div>
                    </div>
                @endif
                @if(!empty(optional($contact)->whatsapp))
                    <div class="sf-contact-card">
                        <i class="fab fa-whatsapp" style="color:#25D366"></i>
                        <div><b>WhatsApp</b>
                            <a href="https://wa.me/{{ $contact->whatsapp }}" target="_blank" rel="noopener">{{ $contact->whatsapp }}</a>
                            <p>Quick replies during business hours</p>
                        </div>
                    </div>
                @endif
                @if(!empty(optional($contact)->email))
                    <div class="sf-contact-card">
                        <i class="fa-regular fa-envelope"></i>
                        <div><b>Email</b>
                            <a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a>
                            <p>We reply within 24 hours</p>
                        </div>
                    </div>
                @endif
                @if(!empty(optional($contact)->address))
                    <div class="sf-contact-card">
                        <i class="fa-solid fa-location-dot"></i>
                        <div><b>Our Address</b>
                            <p>{{ $contact->address }}</p>
                        </div>
                    </div>
                @endif
            </div>

            <div class="sf-card-surface" style="padding:26px">
                <h3 style="font-size:18px;margin-bottom:18px"><i class="fa-solid fa-paper-plane" style="color:var(--c-primary);margin-right:8px"></i>Send us a message</h3>

                @if(session('success'))
                    <div class="sf-form-msg sf-form-msg--success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="sf-form-msg sf-form-msg--error">{{ $errors->first() }}</div>
                @endif

                <form action="{{ route('frontend.contact.store') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="sf-field"><label>Full Name <span class="req">*</span></label>
                                <input type="text" name="full_name" class="sf-input" value="{{ Auth::guard('customer')->user()->name ?? old('full_name') }}" required /></div>
                        </div>
                        <div class="col-md-6">
                            <div class="sf-field"><label>Mobile <span class="req">*</span></label>
                                <input type="text" name="mobile" class="sf-input" value="{{ Auth::guard('customer')->user()->phone ?? old('mobile') }}" required /></div>
                        </div>
                        <div class="col-md-6">
                            <div class="sf-field"><label>Email</label>
                                <input type="email" name="email" class="sf-input" value="{{ Auth::guard('customer')->user()->email ?? old('email') }}" /></div>
                        </div>
                        <div class="col-md-6">
                            <div class="sf-field"><label>Subject <span class="req">*</span></label>
                                <input type="text" name="subject" class="sf-input" value="{{ old('subject') }}" required /></div>
                        </div>
                        <div class="col-12">
                            <div class="sf-field"><label>Message <span class="req">*</span></label>
                                <textarea name="details" class="sf-textarea" required>{{ old('details') }}</textarea></div>
                        </div>
                    </div>
                    <button type="submit" class="sf-btn sf-btn--primary sf-btn--lg"><i class="fa-solid fa-paper-plane"></i> Send Message</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
