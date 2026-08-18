@extends('backEnd.layouts.master')
@section('title','Incomplete Orders')

@section('css')
<style>
    /* Clean Table Styling */
    .table-custom {
        border-collapse: separate;
        border-spacing: 0 5px;
    }
    .table-custom thead th {
        border: none;
        background: #f1f5f7;
        color: #6c757d;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 12px;
        padding: 15px;
    }
    .table-custom tbody tr.parent-row {
        background: #fff;
        box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        transition: 0.2s;
        cursor: pointer;
    }
    .table-custom tbody tr.parent-row:hover {
        transform: scale(1.005);
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        z-index: 2;
        position: relative;
    }
    .table-custom td {
        vertical-align: middle;
        padding: 15px;
        border: none;
        font-size: 14px;
        color: #333;
    }
    
    /* Expanded Details Section */
    .details-row {
        display: none; /* Hidden by default */
        background: #f9fbfd;
    }
    .details-box {
        padding: 20px;
        border-left: 3px solid #727cf5;
        margin: 5px 0 15px 0;
    }

    /* Status & Amount */
    .amount-tag {
        font-weight: 700;
        color: #0acf97;
        font-size: 15px;
    }
    .date-text {
        font-size: 12px;
        color: #98a6ad;
    }

    /* Buttons */
    .btn-action-group {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
    }
    .btn-icon {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid transparent;
        transition: 0.2s;
    }
    .btn-accept { background: rgba(10, 207, 151, 0.1); color: #0acf97; }
    .btn-accept:hover { background: #0acf97; color: #fff; }

    .btn-delete { background: rgba(250, 92, 124, 0.1); color: #fa5c7c; }
    .btn-delete:hover { background: #fa5c7c; color: #fff; }
    
    .btn-expand { background: #eef2f7; color: #6c757d; transform: rotate(0deg); transition: 0.3s; }
    .parent-row.active .btn-expand { transform: rotate(180deg); background: #343a40; color: #fff; }

    /* ⭐ রিকভারি সামারি কার্ড */
    .stat-card {
        background: #fff; border-radius: 10px; padding: 16px 18px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.04); height: 100%;
    }
    .stat-card .stat-value { font-size: 22px; font-weight: 700; line-height: 1.2; }
    .stat-card .stat-label { font-size: 12px; color: #98a6ad; text-transform: uppercase; font-weight: 600; }
    .filter-pill {
        display: inline-block; padding: 6px 14px; border-radius: 30px;
        font-size: 13px; font-weight: 600; text-decoration: none;
        background: #eef2f7; color: #6c757d; border: 1px solid transparent;
    }
    .filter-pill:hover { background: #dfe6ef; color: #343a40; }
    .filter-pill.active { background: #727cf5; color: #fff; }
    .recovery-form textarea { font-size: 13px; }

    /* Product Mini Table */
    .mini-table th { font-size: 11px; text-transform: uppercase; color: #98a6ad; }
    .mini-table img { width: 40px; height: 40px; border-radius: 4px; border: 1px solid #ddd; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    
    <div class="row mb-3 mt-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="page-title mb-0 fw-bold">
                অসম্পূর্ণ অর্ডার (Abandoned Cart)
                <span class="badge bg-secondary rounded-pill ms-2">{{ $orders->total() }}</span>
            </h4>
        </div>
    </div>

    {{-- ⭐ রিকভারি সামারি --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3 col-xl">
            <div class="stat-card">
                <div class="stat-value text-dark">{{ $stats['total'] }}</div>
                <div class="stat-label">মোট অসম্পূর্ণ</div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="stat-card">
                <div class="stat-value" style="color:#0acf97;">৳{{ number_format($stats['amount'], 0) }}</div>
                <div class="stat-label">সম্ভাব্য বিক্রি</div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="stat-card">
                <div class="stat-value text-warning">{{ $stats['pending'] }}</div>
                <div class="stat-label">ফলোআপ বাকি</div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="stat-card">
                <div class="stat-value text-success">{{ $stats['recovered'] }}</div>
                <div class="stat-label">রিকভার হয়েছে</div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="stat-card">
                <div class="stat-value text-primary">{{ $stats['recovery_rate'] }}%</div>
                <div class="stat-label">রিকভারি রেট</div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="stat-card">
                <div class="stat-value text-dark">{{ $stats['today'] }}</div>
                <div class="stat-label">আজকের</div>
            </div>
        </div>
    </div>

    {{-- ⭐ ফিল্টার + সার্চ --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.incomplete-orders.index') }}"
               class="filter-pill {{ !$status ? 'active' : '' }}">কাজের সারি</a>
            @if($hasRecovery ?? false)
            @foreach(\App\Models\IncompleteOrder::RECOVERY_STATUSES as $key => $meta)
                <a href="{{ route('admin.incomplete-orders.index', ['status' => $key, 'q' => $search]) }}"
                   class="filter-pill {{ $status === $key ? 'active' : '' }}">
                    {{ $meta['label'] }} ({{ $stats[$key] ?? 0 }})
                </a>
            @endforeach
            @endif
            <a href="{{ route('admin.incomplete-orders.index', ['status' => 'all', 'q' => $search]) }}"
               class="filter-pill {{ $status === 'all' ? 'active' : '' }}">সব</a>
        </div>

        <form action="{{ route('admin.incomplete-orders.index') }}" method="GET" class="d-flex gap-2">
            @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <input type="text" name="q" value="{{ $search }}" class="form-control form-control-sm"
                   placeholder="নাম বা ফোন খুঁজুন" style="min-width:200px;">
            <button type="submit" class="btn btn-sm btn-primary px-3">খুঁজুন</button>
        </form>
    </div>

    @if($orders->count() > 0)
    <div class="table-responsive">
        <table class="table table-custom">
            <thead>
                <tr>
                    <th width="50">#</th>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Date</th>
                    <th>Total Amount</th>
                    <th>রিকভারি</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                
                <tr class="parent-row" onclick="toggleDetails({{ $order->id }})" id="row-{{ $order->id }}">
                    <td>
                        <button class="btn btn-icon btn-expand">
                            <i class="fe-chevron-down"></i>
                        </button>
                    </td>
                    <td>
                        <div class="fw-bold">{{ $order->name ?? 'Guest' }}</div>
                        <small class="text-muted">ID: #{{ $order->id }}</small>
                    </td>
                    <td>{{ $order->phone ?? '—' }}</td>
                    <td>
                        <div class="text-dark">{{ optional($order->created_at)->format('d M, Y') }}</div>
                        <div class="date-text">{{ optional($order->created_at)->format('h:i A') }}</div>
                    </td>
                    <td>
                        <span class="amount-tag">৳{{ number_format($order->total_amount, 0) }}</span>
                    </td>
                    <td>
                        <span class="badge bg-{{ $order->status_class }}">{{ $order->status_label }}</span>
                        @if($order->contacted_at)
                            <div class="date-text mt-1">
                                {{ $order->contacted_at->diffForHumans() }}
                            </div>
                        @endif
                    </td>
                    <td>
                        <div class="btn-action-group" onclick="event.stopPropagation();">
                            {{-- Accept --}}
                            <form action="{{ route('admin.incomplete-orders.accept', $order->id) }}" method="POST" onsubmit="return confirm('Accept this order?');">
                                @csrf
                                <button type="submit" class="btn btn-icon btn-accept" title="Accept">
                                    <i class="fe-check"></i>
                                </button>
                            </form>
                            
                            {{-- Delete --}}
                            <form action="{{ route('admin.incomplete-orders.destroy', $order->id) }}" method="POST" onsubmit="return confirm('Delete permanently?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-icon btn-delete" title="Delete">
                                    <i class="fe-trash-2"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>

                <tr id="details-{{ $order->id }}" class="details-row">
                    <td colspan="7" class="p-0 border-0">
                        <div class="details-box">
                            <div class="row">
                                <div class="col-md-4 border-end">
                                    <h6 class="text-uppercase text-muted font-size-12">Shipping Address</h6>
                                    <p class="mb-2 text-dark">
                                        <i class="fe-map-pin me-1 text-primary"></i>
                                        {{ $order->address ?? 'No address provided' }}
                                    </p>
                                    @if($hasCaptureMetadata ?? false)
                                        <div class="small text-muted lh-lg">
                                            <div><i class="fe-monitor me-1"></i> {{ ucfirst($order->device_type ?: 'unknown') }}{{ $order->device_name ? ' — '.$order->device_name : '' }}</div>
                                            <div><i class="fe-globe me-1"></i> IP: {{ $order->ip_address ?: '—' }}</div>
                                            <div><i class="fe-clock me-1"></i> Checkout started: {{ optional($order->checkout_started_at)->format('d M Y, h:i A') ?: optional($order->created_at)->format('d M Y, h:i A') }}</div>
                                            @if($order->last_activity_at)<div><i class="fe-activity me-1"></i> Last activity: {{ $order->last_activity_at->diffForHumans() }}</div>@endif
                                            @if($order->source)<div><i class="fe-link me-1"></i> Source: {{ $order->source }}</div>@endif
                                        </div>
                                    @endif
                                </div>

                                <div class="col-md-8 ps-md-4">
                                    <h6 class="text-uppercase text-muted font-size-12 mb-2">Order Items</h6>
                                    
                                    @if(!empty($order->items) && is_array($order->items))
                                    <table class="table table-sm table-borderless mini-table mb-0">
                                        <thead>
                                            <tr>
                                                <th>Image</th>
                                                <th>Product Name</th>
                                                <th>Qty</th>
                                                <th class="text-end">Price</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($order->items as $it)
                                            <tr>
                                                <td width="50">
                                                    <img src="{{ !empty($it['image']) ? $it['image'] : asset('public/default.png') }}" alt="img">
                                                </td>
                                                <td>{{ \Illuminate\Support\Str::limit($it['name'], 50) }}</td>
                                                <td>x{{ $it['qty'] }}</td>
                                                <td class="text-end fw-bold">৳{{ $it['price'] }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    @elseif($order->product_link)
                                        <div class="d-flex align-items-center bg-white p-2 border rounded">
                                            <img src="{{ asset($order->product_image) }}" style="width:50px; height:50px; object-fit:cover" class="me-2 rounded">
                                            <a href="{{ $order->product_link }}" target="_blank" class="fw-bold">View Product</a>
                                        </div>
                                    @else
                                        <span class="text-muted fst-italic">No product details found.</span>
                                    @endif
                                </div>
                            </div>

                            {{-- ⭐ রিকভারি ফলোআপ — অ্যাডমিন নিজে ফোন করে ফলাফল এখানে লিখে রাখবেন।
                                 এখান থেকে কোনো SMS/WhatsApp পাঠানো হয় না।
                                 মাইগ্রেশন না চালানো থাকলে (কলাম নেই) এই অংশ দেখানো হয় না। --}}
                            @if($hasRecovery ?? false)
                            <div class="row mt-3 pt-3 border-top">
                                <div class="col-12">
                                    <h6 class="text-uppercase text-muted font-size-12 mb-2">রিকভারি ফলোআপ</h6>

                                    <form action="{{ route('admin.incomplete-orders.recovery', $order->id) }}"
                                          method="POST" class="recovery-form row g-2 align-items-start">
                                        @csrf
                                        <div class="col-md-3">
                                            <select name="recovery_status" class="form-select form-select-sm">
                                                @foreach(\App\Models\IncompleteOrder::RECOVERY_STATUSES as $key => $meta)
                                                    <option value="{{ $key }}"
                                                        {{ ($order->recovery_status ?: 'pending') === $key ? 'selected' : '' }}>
                                                        {{ $meta['label'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-7">
                                            <textarea name="recovery_note" rows="1" class="form-control form-control-sm"
                                                      placeholder="নোট — যেমন: ফোন ধরেনি, পরে কল করতে বলেছে">{{ $order->recovery_note }}</textarea>
                                        </div>
                                        <div class="col-md-2 d-grid">
                                            <button type="submit" class="btn btn-sm btn-primary">সেভ</button>
                                        </div>
                                    </form>

                                    <div class="mt-2 d-flex flex-wrap gap-3 align-items-center">
                                        @if($order->phone)
                                            <a href="tel:{{ $order->phone }}" class="btn btn-sm btn-outline-success">
                                                <i class="fe-phone me-1"></i> {{ $order->phone }} এ কল
                                            </a>
                                        @endif
                                        @if($order->recovered_order_id)
                                            <span class="text-success small fw-bold">
                                                অর্ডার #{{ $order->recovered_order_id }} তৈরি হয়েছে
                                            </span>
                                        @endif
                                        @if($order->contacted_at)
                                            <span class="date-text">
                                                প্রথম যোগাযোগ: {{ $order->contacted_at->format('d M, Y h:i A') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <div class="d-flex justify-content-center mt-4">
        {{ $orders->links('pagination::bootstrap-4') }}
    </div>

    @else
    <div class="text-center py-5">
        <h5 class="text-muted">এই ফিল্টারে কোনো অসম্পূর্ণ অর্ডার নেই।</h5>
    </div>
    @endif

</div>
@endsection

@section('script')
<script>
    function toggleDetails(id) {
        // Toggle the hidden row
        let detailsRow = document.getElementById('details-' + id);
        let parentRow = document.getElementById('row-' + id);
        
        if (detailsRow.style.display === "none" || detailsRow.style.display === "") {
            // Close all others first (Optional - if you want only one open at a time)
            // document.querySelectorAll('.details-row').forEach(row => row.style.display = 'none');
            // document.querySelectorAll('.parent-row').forEach(row => row.classList.remove('active'));

            detailsRow.style.display = "table-row";
            parentRow.classList.add('active');
        } else {
            detailsRow.style.display = "none";
            parentRow.classList.remove('active');
        }
    }
</script>
@endsection