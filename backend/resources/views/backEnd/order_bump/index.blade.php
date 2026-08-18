@extends('backEnd.layouts.master')
@section('title', 'Order Bumps')

@section('css')
<style>
    .card-modern { border: none; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,.03); background: #fff; }
    .table-modern th {
        background-color: #fff; color: #64748b; font-size: .75rem; font-weight: 700;
        text-transform: uppercase; padding: 1rem; border-bottom: 2px solid #f1f5f9; white-space: nowrap;
    }
    .table-modern td { padding: 1rem; vertical-align: middle; font-size: .875rem; color: #334155; border-bottom: 1px solid #f1f5f9; }
    .table-modern tr:last-child td { border-bottom: none; }
    .table-modern tr:hover td { background-color: #f8fafc; }
    .bump-thumb { width: 42px; height: 42px; border-radius: 8px; object-fit: cover; border: 1px solid #e2e8f0; }
    .stat-pill { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: .75rem; font-weight: 700; }
    .stat-imp { color: #475569; background: #f1f5f9; }
    .stat-conv { color: #15803d; background: #dcfce7; }
    .stat-rate { color: #b45309; background: #fef3c7; }
</style>
@endsection

@section('content')
<div class="container-fluid py-4">
    <div class="card card-modern">
        <div class="card-header bg-white d-flex justify-content-between align-items-center p-4 border-0">
            <div>
                <h5 class="mb-1 fw-bold text-dark">Order Bumps</h5>
                <p class="text-muted small mb-0">চেকআউটে দেখানো অ্যাড-অন অফার — গড় অর্ডার ভ্যালু বাড়ানোর জন্য।</p>
            </div>
            <a href="{{ route('admin.order_bumps.create') }}" class="btn btn-primary btn-sm rounded-pill px-3">
                <i data-feather="plus" style="width:14px;" class="me-1"></i> New Bump
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-modern mb-0">
                <thead>
                    <tr>
                        <th>Offer</th>
                        <th>Product</th>
                        <th>Discount</th>
                        <th>Scope</th>
                        <th>Performance</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($bumps as $bump)
                    @php
                        $rate = $bump->impressions > 0
                            ? round(($bump->conversions / $bump->impressions) * 100, 1)
                            : 0;
                    @endphp
                    <tr>
                        <td>
                            <strong class="d-block text-dark">{{ $bump->title ?: '— (ডিফল্ট টেক্সট)' }}</strong>
                            @if($bump->subtitle)
                                <small class="text-muted">{{ Str::limit($bump->subtitle, 50) }}</small>
                            @endif
                        </td>
                        <td>
                            @if($bump->product)
                                <div class="d-flex align-items-center gap-2">
                                    @if(optional($bump->product->image)->image)
                                        <img class="bump-thumb" src="{{ asset($bump->product->image->image) }}" alt="">
                                    @endif
                                    <div>
                                        <span class="d-block">{{ Str::limit($bump->product->name, 30) }}</span>
                                        <small class="text-muted">
                                            নিয়মিত ৳{{ number_format((float) $bump->product->new_price, 0) }}
                                            → <b class="text-success">৳{{ number_format($bump->offerPrice(), 0) }}</b>
                                        </small>
                                    </div>
                                </div>
                            @else
                                <span class="text-danger small">প্রোডাক্টটি মুছে ফেলা হয়েছে</span>
                            @endif
                        </td>
                        <td>
                            <span class="fw-bold">
                                {{ $bump->discount_type === 'percent'
                                    ? rtrim(rtrim(number_format($bump->discount_value, 2), '0'), '.') . '%'
                                    : '৳' . number_format($bump->discount_value, 0) }}
                            </span>
                            @if($bump->min_cart_amount)
                                <small class="d-block text-muted">সর্বনিম্ন কার্ট ৳{{ number_format($bump->min_cart_amount, 0) }}</small>
                            @endif
                        </td>
                        <td>
                            @if($bump->campaign)
                                <span class="badge bg-info-subtle text-info-emphasis">{{ Str::limit($bump->campaign->name, 22) }}</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary-emphasis">সব ক্যাম্পেইন</span>
                            @endif
                        </td>
                        <td>
                            <span class="stat-pill stat-imp">{{ number_format($bump->impressions) }} বার দেখানো</span>
                            <span class="stat-pill stat-conv">{{ number_format($bump->conversions) }} গ্রহণ</span>
                            <span class="stat-pill stat-rate">{{ $rate }}%</span>
                        </td>
                        <td>
                            <form action="{{ route('admin.order_bumps.toggle', $bump->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $bump->status ? 'btn-success' : 'btn-outline-secondary' }} rounded-pill px-3">
                                    {{ $bump->status ? 'Active' : 'Off' }}
                                </button>
                            </form>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.order_bumps.edit', $bump->id) }}" class="btn btn-sm btn-light border">
                                <i data-feather="edit-2" style="width:14px;"></i>
                            </a>
                            <form action="{{ route('admin.order_bumps.destroy', $bump->id) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('এই অর্ডার বাম্পটি মুছে ফেলবেন?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light border text-danger">
                                    <i data-feather="trash-2" style="width:14px;"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i data-feather="shopping-bag" style="width:32px;" class="mb-2 d-block mx-auto opacity-50"></i>
                            এখনো কোনো অর্ডার বাম্প নেই।
                            <a href="{{ route('admin.order_bumps.create') }}">প্রথমটি তৈরি করুন</a>।
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
