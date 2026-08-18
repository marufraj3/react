@extends('backEnd.layouts.master')
@section('title', 'ক্যাম্পেইন অ্যানালিটিক্স')

@section('css')
<style>
    .metric-card {
        background: #fff; border-radius: 12px; padding: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05); height: 100%;
    }
    .metric-value { font-size: 26px; font-weight: 700; line-height: 1.2; }
    .metric-label { font-size: 12px; color: #98a6ad; text-transform: uppercase; font-weight: 600; letter-spacing: .3px; }
    .metric-sub   { font-size: 12px; color: #6c757d; margin-top: 4px; }

    /* ফানেল বার — প্রতিটি ধাপ আগের ধাপের অনুপাতে চওড়া */
    .funnel-step { margin-bottom: 14px; }
    .funnel-bar {
        height: 34px; border-radius: 6px; background: #727cf5;
        display: flex; align-items: center; padding: 0 12px;
        color: #fff; font-weight: 600; font-size: 13px; min-width: 60px;
        transition: width .3s;
    }
    .funnel-bar.step-cart  { background: #39afd1; }
    .funnel-bar.step-order { background: #0acf97; }
    .funnel-meta { font-size: 12px; color: #6c757d; margin-top: 4px; }

    .range-pill {
        display:inline-block; padding:6px 14px; border-radius:30px; font-size:13px;
        font-weight:600; text-decoration:none; background:#eef2f7; color:#6c757d;
    }
    .range-pill.active { background:#727cf5; color:#fff; }
    .table-daily th { font-size:12px; text-transform:uppercase; color:#98a6ad; }
    .table-daily td { font-size:13px; }
</style>
@endsection

@section('content')
<div class="container-fluid">

    <div class="row mb-3 mt-4">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="page-title mb-1 fw-bold">{{ $campaign->name }}</h4>
                <a href="{{ url('campaign', $campaign->slug) }}" target="_blank" class="text-muted small">
                    <i class="fe-external-link me-1"></i> {{ url('campaign', $campaign->slug) }}
                </a>
            </div>
            <a href="{{ route('campaign.index') }}" class="btn btn-light btn-sm rounded-pill px-3">
                <i class="fe-arrow-left me-1"></i> সব ক্যাম্পেইন
            </a>
        </div>
    </div>

    {{-- সময়সীমা --}}
    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach([7 => 'গত ৭ দিন', 30 => 'গত ৩০ দিন', 90 => 'গত ৯০ দিন', 365 => 'গত এক বছর'] as $d => $label)
            <a href="{{ route('campaign.analytics', ['id' => $campaign->id, 'days' => $d]) }}"
               class="range-pill {{ $days === $d ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    {{-- মূল মেট্রিক --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="metric-card">
                <div class="metric-value text-dark">{{ number_format($summary['unique_visits'] ?: $summary['visits']) }}</div>
                <div class="metric-label">ভিজিটর</div>
                <div class="metric-sub">মোট ভিউ {{ number_format($summary['visits']) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="metric-card">
                <div class="metric-value" style="color:#39afd1;">{{ number_format($summary['add_to_carts']) }}</div>
                <div class="metric-label">কার্টে যোগ</div>
                <div class="metric-sub">{{ $summary['cart_rate'] }}% ভিজিটর</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="metric-card">
                <div class="metric-value text-success">{{ number_format($summary['orders']) }}</div>
                <div class="metric-label">অর্ডার</div>
                <div class="metric-sub">সফল অর্ডার</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="metric-card">
                <div class="metric-value text-primary">{{ $summary['conversion_rate'] }}%</div>
                <div class="metric-label">কনভার্শন রেট</div>
                <div class="metric-sub">ভিজিটর → অর্ডার</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="metric-card">
                <div class="metric-value" style="color:#0acf97;">৳{{ number_format($summary['revenue'], 0) }}</div>
                <div class="metric-label">মোট বিক্রি</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="metric-card">
                <div class="metric-value text-dark">৳{{ number_format($summary['aov'], 0) }}</div>
                <div class="metric-label">গড় অর্ডার মূল্য</div>
            </div>
        </div>
    </div>

    {{-- ফানেল --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="metric-card">
                <h5 class="fw-bold mb-3">ফানেল</h5>

                @php
                    $visitBase = $summary['unique_visits'] ?: $summary['visits'];
                    // প্রস্থ সবসময় ভিজিটরের অনুপাতে; ভিজিটর শূন্য হলে বার দেখানোর মানে নেই
                    $pct = function ($n) use ($visitBase) {
                        return $visitBase > 0 ? max(4, round(($n / $visitBase) * 100)) : 0;
                    };
                @endphp

                @if($visitBase < 1)
                    <p class="text-muted mb-0">এই সময়সীমায় এখনো কোনো ভিজিট রেকর্ড হয়নি।</p>
                @else
                    <div class="funnel-step">
                        <div class="funnel-bar" style="width:100%;">
                            ভিজিটর — {{ number_format($visitBase) }}
                        </div>
                    </div>

                    <div class="funnel-step">
                        <div class="funnel-bar step-cart" style="width:{{ $pct($summary['add_to_carts']) }}%;">
                            কার্টে যোগ — {{ number_format($summary['add_to_carts']) }}
                        </div>
                        <div class="funnel-meta">
                            ভিজিটরের {{ $summary['cart_rate'] }}% কার্টে যোগ করেছে
                        </div>
                    </div>

                    <div class="funnel-step">
                        <div class="funnel-bar step-order" style="width:{{ $pct($summary['orders']) }}%;">
                            অর্ডার — {{ number_format($summary['orders']) }}
                        </div>
                        <div class="funnel-meta">
                            ভিজিটরের {{ $summary['conversion_rate'] }}% অর্ডার সম্পন্ন করেছে
                            @if($summary['add_to_carts'] > 0)
                                • কার্ট থেকে
                                {{ round(($summary['orders'] / $summary['add_to_carts']) * 100, 1) }}%
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-6">
            <div class="metric-card">
                <h5 class="fw-bold mb-3">কীভাবে পড়বেন</h5>
                <ul class="text-muted mb-0 ps-3" style="font-size:13px; line-height:1.9;">
                    <li><strong>ভিজিটর</strong> — একই সেশনে বারবার রিফ্রেশ করলে একবারই গোনা হয়।</li>
                    <li><strong>কনভার্শন রেট</strong> ২–৩% এর নিচে থাকলে পেজের অফার/দাম দেখুন।</li>
                    <li><strong>কার্টে যোগ বেশি কিন্তু অর্ডার কম</strong> মানে চেকআউটে সমস্যা —
                        ডেলিভারি চার্জ বা ফর্ম বড় হয়ে যাচ্ছে কিনা দেখুন।</li>
                    <li>অসম্পূর্ণ অর্ডারগুলো
                        <a href="{{ route('admin.incomplete-orders.index') }}">অসম্পূর্ণ অর্ডার</a> পেজ থেকে ফলোআপ করুন।</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- দৈনিক ভাঙানো হিসাব --}}
    <div class="card">
        <div class="card-body">
            <h5 class="fw-bold mb-3">দৈনিক হিসাব</h5>

            @if($daily->isEmpty())
                <p class="text-muted mb-0">এখনো কোনো ডেটা নেই। ক্যাম্পেইন পেজে ভিজিট এলে এখানে দেখা যাবে।</p>
            @else
            <div class="table-responsive">
                <table class="table table-hover table-daily mb-0">
                    <thead>
                        <tr>
                            <th>তারিখ</th>
                            <th class="text-end">ভিজিটর</th>
                            <th class="text-end">মোট ভিউ</th>
                            <th class="text-end">কার্টে যোগ</th>
                            <th class="text-end">অর্ডার</th>
                            <th class="text-end">কনভার্শন</th>
                            <th class="text-end">বিক্রি</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($daily->sortByDesc('stat_date') as $row)
                            @php
                                $base = $row->unique_visits ?: $row->visits;
                                $rate = $base > 0 ? round(($row->orders / $base) * 100, 2) : 0;
                            @endphp
                            <tr>
                                <td>{{ optional($row->stat_date)->format('d M, Y') }}</td>
                                <td class="text-end">{{ number_format($base) }}</td>
                                <td class="text-end text-muted">{{ number_format($row->visits) }}</td>
                                <td class="text-end">{{ number_format($row->add_to_carts) }}</td>
                                <td class="text-end fw-bold text-success">{{ number_format($row->orders) }}</td>
                                <td class="text-end">{{ $rate }}%</td>
                                <td class="text-end">৳{{ number_format($row->revenue, 0) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

</div>
@endsection
