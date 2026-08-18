@extends('backEnd.layouts.master')

@section('title')
    স্টক অ্যালার্ট
@endsection

@section('mainContent')
<style>
    .sa-stat {
        border-radius: 10px;
        padding: 18px 20px;
        color: #fff;
        margin-bottom: 20px;
    }
    .sa-stat h3 { font-size: 26px; margin: 0 0 4px; font-weight: 700; }
    .sa-stat span { font-size: 13px; opacity: .92; }
    .sa-danger  { background: linear-gradient(135deg, #e53935, #b71c1c); }
    .sa-warning { background: linear-gradient(135deg, #fb8c00, #ef6c00); }
    .sa-info    { background: linear-gradient(135deg, #1e88e5, #1565c0); }
    .sa-success { background: linear-gradient(135deg, #43a047, #2e7d32); }
    .sa-pill {
        display: inline-block;
        padding: 6px 14px;
        margin: 0 6px 8px 0;
        border-radius: 20px;
        border: 1px solid #d5d9e0;
        font-size: 13px;
        color: #4a5568;
        background: #fff;
    }
    .sa-pill.active { background: #1e88e5; border-color: #1e88e5; color: #fff; }
    .sa-restock { width: 90px; display: inline-block; }
    .sa-note {
        background: #f7f9fc;
        border-left: 4px solid #1e88e5;
        padding: 14px 18px;
        border-radius: 6px;
        font-size: 13px;
        color: #4a5568;
        line-height: 1.9;
        margin-bottom: 20px;
    }
</style>

<section class="admin-visitor-area up_st_admin_visitor">
    <div class="container-fluid p-0">
        <div class="row">
            <div class="col-lg-12">
                <div class="white-box">

                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                        <h3 class="mb-0">স্টক অ্যালার্ট</h3>
                        <a href="{{ route('admin.stock_alerts.scan') }}" class="primary-btn radius_30px mr-0 fix-gr-bg">
                            <i class="ti-reload"></i> এখনই স্ক্যান করুন
                        </a>
                    </div>

                    <div class="sa-note">
                        <strong>এই পেজটি কী করে?</strong><br>
                        কোনো অর্ডার হওয়ার সাথে সাথেই স্টক কমে যায়। স্টক শূন্য বা প্রায় শেষ হয়ে গেলে
                        এখানে স্বয়ংক্রিয়ভাবে অ্যালার্ট চলে আসে। কোনো প্রোডাক্টের <strong>সব</strong> সাইজ/কালার
                        শেষ হয়ে গেলে প্রোডাক্টটি নিজে থেকেই নিষ্ক্রিয় হয়ে যায়, যাতে কাস্টমার এমন কিছু
                        অর্ডার করতে না পারে যা আপনি ডেলিভারি দিতে পারবেন না।<br>
                        নিচের বক্সে নতুন স্টক লিখে <strong>স্টক আপডেট</strong> চাপলে অ্যালার্টটি বন্ধ হবে
                        এবং প্রোডাক্টটি আবার চালু হয়ে যাবে।
                    </div>

                    <div class="row">
                        <div class="col-md-3 col-6">
                            <div class="sa-stat sa-danger">
                                <h3>{{ $stats['out_of_stock'] }}</h3>
                                <span>স্টক আউট</span>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="sa-stat sa-warning">
                                <h3>{{ $stats['low_stock'] }}</h3>
                                <span>স্টক প্রায় শেষ</span>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="sa-stat sa-info">
                                <h3>{{ $stats['unread'] }}</h3>
                                <span>নতুন (অদেখা)</span>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="sa-stat sa-success">
                                <h3>{{ $stats['resolved'] }}</h3>
                                <span>সমাধান হয়েছে</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <a href="{{ route('admin.stock_alerts.index') }}"
                           class="sa-pill {{ !$filter ? 'active' : '' }}">সব খোলা অ্যালার্ট</a>
                        <a href="{{ route('admin.stock_alerts.index', ['type' => 'out_of_stock']) }}"
                           class="sa-pill {{ $filter === 'out_of_stock' ? 'active' : '' }}">শুধু স্টক আউট</a>
                        <a href="{{ route('admin.stock_alerts.index', ['type' => 'low_stock']) }}"
                           class="sa-pill {{ $filter === 'low_stock' ? 'active' : '' }}">শুধু প্রায় শেষ</a>
                        <a href="{{ route('admin.stock_alerts.index', ['type' => 'resolved']) }}"
                           class="sa-pill {{ $filter === 'resolved' ? 'active' : '' }}">সমাধান হয়েছে</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>প্রোডাক্ট</th>
                                    <th>সাইজ / কালার</th>
                                    <th>অবস্থা</th>
                                    <th>স্টক বাকি</th>
                                    <th>সময়</th>
                                    <th>ব্যবস্থা</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse ($alerts as $key => $alert)
                                <tr>
                                    <td>{{ $alerts->firstItem() + $key }}</td>
                                    <td>
                                        <strong>{{ $alert->product_name ?? optional($alert->product)->name ?? 'মুছে ফেলা প্রোডাক্ট' }}</strong>
                                        @if ($alert->product && (int) $alert->product->status === 0)
                                            <br><small class="text-danger">প্রোডাক্টটি এখন নিষ্ক্রিয়</small>
                                        @endif
                                    </td>
                                    <td>{{ $alert->variant_label ?? '—' }}</td>
                                    <td>
                                        <span class="badge badge-{{ $alert->type_class }}">{{ $alert->type_label }}</span>
                                        @if ($alert->resolved_at)
                                            <br><small class="text-success">সমাধান: {{ $alert->resolved_at->format('d M, Y') }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $alert->stock_left }}</td>
                                    <td>{{ $alert->created_at ? $alert->created_at->format('d M, Y h:i A') : '—' }}</td>
                                    <td>
                                        @if (!$alert->resolved_at)
                                            <form action="{{ route('admin.stock_alerts.restock', $alert->id) }}"
                                                  method="POST" class="form-inline">
                                                @csrf
                                                <input type="number" name="stock" min="0" value="10"
                                                       class="form-control form-control-sm sa-restock mr-1" required>
                                                <button type="submit" class="btn btn-sm btn-success mr-1">স্টক আপডেট</button>
                                            </form>
                                            <a href="{{ route('admin.stock_alerts.dismiss', $alert->id) }}"
                                               class="btn btn-sm btn-link text-muted px-0"
                                               onclick="return confirm('অ্যালার্টটি বন্ধ করবেন?')">বাতিল করুন</a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        কোনো অ্যালার্ট নেই — সব প্রোডাক্টের স্টক ঠিক আছে।
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $alerts->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>
@endsection
