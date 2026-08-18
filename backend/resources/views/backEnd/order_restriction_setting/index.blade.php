@extends('backEnd.layouts.master')

@section('title','Order Restriction Settings')

@section('content')

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        --glass-white: rgba(255, 255, 255, 0.95);
        --text-dark: #2d3748;
        --text-muted: #718096;
        --border-color: #e2e8f0;
    }

    .order-restriction-page-wrapper {
        padding-top: 30px;
        background-color: #f8f9fc;
        min-height: 100vh;
        font-family: 'Inter', sans-serif;
    }

    /* Header Styling */
    .order-restriction-header-card {
        background: var(--primary-gradient);
        border-radius: 16px;
        padding: 30px;
        color: white;
        box-shadow: 0 10px 25px rgba(245, 87, 108, 0.2);
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }

    /* Form Card Styling */
    .settings-card {
        background: var(--glass-white);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
    }

    .settings-card-header {
        background: transparent;
        border-bottom: 1px solid var(--border-color);
        padding: 20px 25px;
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-dark);
        display: flex;
        align-items: center;
    }

    .form-control-lg-custom {
        padding: 12px 15px;
        font-size: 0.95rem;
        border-radius: 8px;
        border: 1px solid #cbd5e0;
    }

    .form-control-lg-custom:focus {
        border-color: #f5576c;
        box-shadow: 0 0 0 3px rgba(245, 87, 108, 0.1);
    }

    /* Button Styling */
    .btn-save {
        background: var(--primary-gradient);
        border: 0;
        padding: 12px;
        font-weight: 600;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
    }

    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(245, 87, 108, 0.3);
    }

    /* Info Card */
    .info-card {
        background: #f8f9fc;
        border-left: 4px solid #f5576c;
        border-radius: 8px;
        padding: 20px;
        margin-top: 20px;
    }

    /* Alert Styling */
    .alert-custom {
        border-radius: 10px;
        border: none;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
</style>

<div class="container-fluid order-restriction-page-wrapper">

    <div class="row justify-content-center">
        <div class="col-lg-11">
            <div class="order-restriction-header-card d-flex flex-wrap justify-content-between align-items-center">
                <div class="d-flex align-items-center mb-3 mb-md-0">
                    <div class="bg-white p-2 rounded-circle me-3 shadow-sm d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fe-clock fs-2 text-danger"></i>
                    </div>
                    <div>
                        <h2 class="mb-1 text-white fw-bold">Order Restriction Settings</h2>
                        <p class="mb-0 text-white-50 small">Control order limits and restrictions</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        
        <div class="col-lg-8 mb-4">
            
            @if(session()->has('success') || session()->has('message'))
                <div class="alert alert-success alert-custom alert-dismissible fade show mb-4 d-flex align-items-center" role="alert">
                    <i class="fe-check-circle fs-4 me-2"></i>
                    <div>
                        <strong>সফল হয়েছে!</strong> {{ session('success') ?? session('message') }}
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-custom alert-dismissible fade show mb-4" role="alert">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card settings-card h-100">
                <div class="settings-card-header">
                    <i class="fe-sliders me-2 text-danger"></i> Order Restriction Configuration
                </div>
                
                <div class="card-body p-4">

                    <div class="alert alert-info d-flex gap-2 align-items-start mb-4" role="alert">
                        <i class="fe-info mt-1"></i>
                        <div>
                            <strong>সংস্করণ ২ — এখন শুধু ফোন নম্বর দিয়ে গণনা হয়।</strong>
                            <div class="small mt-1">
                                আগে ফোন নম্বরের পাশাপাশি IP ঠিকানা দিয়েও গণনা হতো। মোবাইল ডেটা (CGNAT)
                                বা অফিস/হোস্টেলের শেয়ার্ড ওয়াইফাইয়ে একাধিক আলাদা কাস্টমার একই IP থেকে আসায়
                                নির্দোষ অর্ডারও আটকে যেত — সেই অংশটি বাদ দেওয়া হয়েছে।
                                বাতিল হওয়া অর্ডার সীমার হিসাবে ধরা হয় না, এবং
                                হোয়াইটলিস্ট করা নম্বর কখনোই আটকাবে না।
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('admin.order.restriction.setting.update') }}" method="POST">
                        @csrf

                        {{-- মাস্টার সুইচ — এটি চালু না করলে কোনো অর্ডার আটকানো হবে না --}}
                        <div class="mb-4 p-3 rounded" style="background:#f8f9fc;border:1px solid #e2e8f0;">
                            @if ($hasSwitch)
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           id="order_limit_enabled" name="order_limit_enabled" value="1"
                                           {{ old('order_limit_enabled', $data->order_limit_enabled ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold text-dark" for="order_limit_enabled">
                                        অর্ডার সীমা চালু করুন
                                    </label>
                                </div>
                                <small class="text-muted d-block mt-2">
                                    বন্ধ থাকলে নিচের সীমাগুলো শুধু সেভ থাকবে, চেকআউটে কোনো অর্ডার আটকানো হবে না।
                                    নতুন দোকানে এটি বন্ধ রাখাই নিরাপদ।
                                </small>
                            @else
                                <div class="text-danger small">
                                    <strong>মাইগ্রেশন বাকি আছে।</strong>
                                    <code>order_limit_enabled</code> কলামটি তৈরি না হওয়া পর্যন্ত ফিচারটি বন্ধ থাকবে।
                                    সার্ভারে <code>php artisan migrate</code> চালান।
                                </div>
                            @endif
                        </div>

                        
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark mb-2">Order Restriction Time <span class="text-danger">*</span></label>
                            
                            <div class="input-group">
                                <input type="number" name="order_limit_time" 
                                       class="form-control form-control-lg-custom" 
                                       placeholder="Enter time in hours"
                                       value="{{ old('order_limit_time', $data->order_limit_time ?? 48) }}"
                                       min="1"
                                       required>
                                <span class="input-group-text bg-light">Hours</span>
                            </div>
                            <small class="text-muted mt-2 d-block">
                                <i class="fe-clock me-1"></i> এই সময়ের মধ্যে একজন কাস্টমার একই প্রোডাক্ট কতবার অর্ডার করতে পারবে তা নির্ধারণ করে। উদাহরণ: 24 ঘন্টা মানে গত 24 ঘন্টার মধ্যে।
                            </small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark mb-2">Max Order Quantity Limit <span class="text-danger">*</span></label>
                            
                            <div class="input-group">
                                <input type="number" name="order_limit_qty" 
                                       class="form-control form-control-lg-custom" 
                                       placeholder="Enter maximum quantity"
                                       value="{{ old('order_limit_qty', $data->order_limit_qty ?? 2) }}"
                                       min="1"
                                       required>
                                <span class="input-group-text bg-light">Times</span>
                            </div>
                            <small class="text-muted mt-2 d-block">
                                <i class="fe-shopping-cart me-1"></i> নির্ধারিত সময়ের মধ্যে একজন কাস্টমার সর্বোচ্চ কতবার অর্ডার করতে পারবে। উদাহরণ: 2 মানে সর্বোচ্চ 2 বার।
                            </small>
                        </div>

                        <button type="submit" class="btn btn-danger btn-save w-100 text-white rounded-pill">
                            <i class="fe-save me-2"></i> সেটিংস আপডেট করুন
                        </button>
                    </form>

                    <div class="info-card">
                        <div class="d-flex">
                            <i class="fe-info text-danger mt-1 me-2"></i>
                            <div>
                                <h6 class="fw-bold mb-2">কিভাবে কাজ করে?</h6>
                                <p class="small text-muted mb-2">
                                    <strong>উদাহরণ:</strong> যদি Order Restriction Time = 24 Hours এবং Max Order Quantity Limit = 2 হয়, তাহলে:
                                </p>
                                <ul class="small text-muted mb-0">
                                    <li>একজন কাস্টমার গত 24 ঘন্টার মধ্যে একই প্রোডাক্ট সর্বোচ্চ 2 বার অর্ডার করতে পারবে</li>
                                    <li>3য় বার অর্ডার করতে চাইলে সিস্টেম তাকে বাধা দেবে</li>
                                    <li>24 ঘন্টা পার হয়ে গেলে আবার অর্ডার করতে পারবে</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card settings-card">
                <div class="settings-card-header">
                    <i class="fe-alert-circle me-2 text-warning"></i> Important Notes
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <h6 class="fw-bold text-dark mb-2">⚙️ Current Settings</h6>
                        <div class="bg-light p-3 rounded">
                            <p class="mb-2"><strong>Restriction Time:</strong> <span class="text-primary">{{ $data->order_limit_time ?? 48 }} Hours</span></p>
                            <p class="mb-0"><strong>Max Quantity:</strong> <span class="text-primary">{{ $data->order_limit_qty ?? 2 }} Times</span></p>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning border-0">
                        <small>
                            <strong>সতর্কতা:</strong> এই সেটিংস পরিবর্তন করলে তা সাথে সাথে কার্যকর হবে। 
                            নতুন অর্ডারগুলো এই নিয়ম অনুসরণ করবে।
                        </small>
                    </div>

                    <div class="mt-3">
                        <h6 class="fw-bold text-dark mb-2">🔔 বর্তমান অবস্থা</h6>
                        @if ($hasSwitch && ($data->order_limit_enabled ?? false))
                            <span class="badge bg-success">চালু আছে</span>
                            <small class="text-muted d-block mt-1">চেকআউটে সীমা প্রয়োগ হচ্ছে।</small>
                        @else
                            <span class="badge bg-secondary">বন্ধ আছে</span>
                            <small class="text-muted d-block mt-1">কোনো অর্ডার আটকানো হচ্ছে না।</small>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ⭐ হোয়াইটলিস্ট — এই নম্বরগুলোর ওপর কোনো সীমা প্রযোজ্য নয় --}}
    <div class="row justify-content-center">
        <div class="col-lg-11">
            <div class="card settings-card mb-4">
                <div class="settings-card-header">
                    <i class="fe-user-check me-2 text-success"></i> হোয়াইটলিস্ট — সীমার আওতামুক্ত নম্বর
                </div>
                <div class="card-body p-4">

                    <p class="text-muted small">
                        পাইকারি ক্রেতা বা নিজেদের টেস্ট নম্বর এখানে যোগ করুন।
                        এই নম্বরগুলো যতবার খুশি অর্ডার করতে পারবে — সীমা তাদের ওপর প্রযোজ্য হবে না।
                    </p>

                    @if (!$hasWhitelistTable)
                        <div class="alert alert-danger">
                            হোয়াইটলিস্ট টেবিলটি এখনো তৈরি হয়নি। সার্ভারে <code>php artisan migrate</code> চালান।
                        </div>
                    @else
                        <form action="{{ route('admin.order.restriction.whitelist.store') }}" method="POST" class="row g-2 align-items-end mb-4">
                            @csrf
                            <div class="col-md-3">
                                <label class="form-label fw-bold small">ফোন নম্বর <span class="text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control" placeholder="01XXXXXXXXX" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold small">নাম</label>
                                <input type="text" name="name" class="form-control" placeholder="যেমন: টেস্ট কাস্টমার">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">নোট</label>
                                <input type="text" name="note" class="form-control" placeholder="কেন ছাড় দেওয়া হচ্ছে">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-success w-100">যোগ করুন</button>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead>
                                    <tr>
                                        <th>ফোন নম্বর</th>
                                        <th>নাম</th>
                                        <th>নোট</th>
                                        <th>যোগ হয়েছে</th>
                                        <th style="width:110px;">ব্যবস্থা</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse ($whitelist as $row)
                                    <tr>
                                        <td><strong>{{ $row->phone }}</strong></td>
                                        <td>{{ $row->name ?: '—' }}</td>
                                        <td>{{ $row->note ?: '—' }}</td>
                                        <td>{{ $row->created_at ? $row->created_at->format('d M, Y') : '—' }}</td>
                                        <td>
                                            <form action="{{ route('admin.order.restriction.whitelist.destroy', $row->id) }}"
                                                  method="POST"
                                                  onsubmit="return confirm('নম্বরটি হোয়াইটলিস্ট থেকে সরাবেন?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">সরান</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            হোয়াইটলিস্টে এখনো কোনো নম্বর নেই।
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if (method_exists($whitelist, 'links'))
                            {{ $whitelist->links() }}
                        @endif
                    @endif

                </div>
            </div>
        </div>
    </div>
</div> 

@endsection
