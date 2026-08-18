@extends('backEnd.layouts.master')
@section('title','Manage Landing Pages')

@section('css')
<link href="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<link href="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<link href="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<link href="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-select-bs5/css/select.bootstrap5.min.css" rel="stylesheet" type="text/css" />

<style>
    /* Premium Card & Table */
    .card {
        border: none;
        box-shadow: 0 0 20px rgba(18, 38, 63, 0.03);
        border-radius: 12px;
        overflow: hidden;
    }
    .card-body { padding: 25px; }

    /* Table Styling */
    .table thead th {
        background-color: #f9fbfd;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 11px;
        color: #8391a2;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #eef2f7;
        padding: 12px 15px;
    }
    .table tbody td {
        vertical-align: middle;
        padding: 15px;
        border-bottom: 1px solid #f1f5f7;
        color: #313b5e;
        font-size: 14px;
    }

    /* Landing Page Title Style */
    .campaign-title {
        font-weight: 600;
        color: #343a40;
        font-size: 14px;
    }
    .campaign-link {
        font-size: 12px;
        color: #727cf5;
        text-decoration: none;
    }
    .campaign-link:hover { text-decoration: underline; }

    /* Action Buttons */
    .action-btn {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        color: #6c757d;
        transition: all 0.2s;
        border: 1px solid transparent;
        background: #f9fbfd;
    }
    .action-btn:hover { background-color: #eef2f7; color: #343a40; }
    
    .btn-view:hover { background-color: rgba(10, 207, 151, 0.1); color: #0acf97; }
    .btn-builder { color: #7c3aed; background: rgba(124, 58, 237, 0.08); }
    .btn-builder:hover { color: #fff; background: #7c3aed; box-shadow: 0 7px 16px rgba(124, 58, 237, .24); }
    .btn-edit:hover { background-color: rgba(114, 124, 245, 0.1); color: #727cf5; }
    .btn-delete:hover { background-color: rgba(250, 92, 124, 0.1); color: #fa5c7c; }
    .builder-status { display: inline-flex; width: fit-content; margin-top: 5px; padding: 3px 8px; border-radius: 999px; color: #6d28d9; background: #f3e8ff; font-size: 10px; font-weight: 700; }
    .publish-status { display:inline-flex; width:fit-content; margin-top:5px; padding:3px 8px; border-radius:999px; font-size:10px; font-weight:800; }
    .publish-live { color:#166534; background:#dcfce7; }
    .publish-off { color:#991b1b; background:#fee2e2; }
    .btn-code { color:#0369a1; background:#e0f2fe; }
    .btn-code:hover { color:#fff; background:#0284c7; }
    .btn-copy { color:#92400e; background:#fef3c7; }
    .btn-copy:hover { color:#fff; background:#d97706; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    
    <div class="row mb-3 mt-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="page-title mb-0" style="font-weight: 700; color: #2d3436;">Landing Pages</h4>
                <p class="text-muted font-size-13 mb-0">Manage your marketing campaign pages.</p>
            </div>
            <a href="{{route('campaign.create')}}" class="btn btn-primary rounded-pill shadow-sm px-4">
                <i class="fe-plus me-1"></i> Create New Page
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table id="datatable-buttons" class="table table-hover w-100 dt-responsive nowrap">
                        <thead>
                            <tr>
                                <th style="width: 50px;">SL</th>
                                <th>Landing Page Title</th>
                                <th style="width: 110px;">ভিজিট</th>
                                <th style="width: 110px;">অর্ডার</th>
                                <th style="width: 120px;">কনভার্শন</th>
                                <th class="text-end" style="width: 180px;">Action</th>
                            </tr>
                        </thead>                
                        <tbody>
                            @foreach($show_data as $key=>$value)
                            <tr>
                                <td>{{$loop->iteration}}</td>
                                
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="campaign-title">{{$value->name}}</span>
                                        <a href="{{url('campaign',$value->slug)}}" target="_blank" class="campaign-link">
                                            <i class="fe-external-link me-1"></i> {{url('campaign',$value->slug)}}
                                        </a>
                                        @if($value->custom_page_published_at && $value->custom_html)
                                            <span class="builder-status"><i class="fe-code me-1"></i> Custom code design</span>
                                        @elseif(!empty($value->page_html))
                                            <span class="builder-status"><i class="fe-layout me-1"></i> Visual design active</span>
                                        @else
                                            <span class="builder-status"><i class="fe-zap me-1"></i> Premium template</span>
                                        @endif
                                        <span class="publish-status {{ $value->is_published && $value->status ? 'publish-live' : 'publish-off' }}">
                                            {{ $value->is_published && $value->status ? '● Published' : '● Unpublished' }}
                                        </span>
                                    </div>
                                </td>

                                {{-- ⭐ গত ৩০ দিনের পারফরম্যান্স --}}
                                @php $st = $stats[$value->id] ?? null; @endphp

                                <td>
                                    <span class="fw-bold">{{ $st ? number_format($st['unique_visits'] ?: $st['visits']) : 0 }}</span>
                                    <div class="text-muted" style="font-size:11px;">৩০ দিনে</div>
                                </td>
                                <td>
                                    <span class="fw-bold text-success">{{ $st ? number_format($st['orders']) : 0 }}</span>
                                    @if($st && $st['revenue'] > 0)
                                        <div class="text-muted" style="font-size:11px;">৳{{ number_format($st['revenue'], 0) }}</div>
                                    @endif
                                </td>
                                <td>
                                    @php $cr = $st['conversion_rate'] ?? 0; @endphp
                                    <span class="badge bg-{{ $cr >= 3 ? 'success' : ($cr > 0 ? 'warning' : 'secondary') }}">
                                        {{ $cr }}%
                                    </span>
                                </td>

                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">

                                        {{-- ⭐ অ্যানালিটিক্স --}}
                                        <a href="{{ route('campaign.analytics', $value->id) }}" class="action-btn btn-view" title="Analytics">
                                            <i class="fe-bar-chart-2"></i>
                                        </a>

                                        
                                        {{-- View Live --}}
                                        @if($value->is_published && $value->status)
                                            <a href="{{url('campaign',$value->slug)}}" target="_blank" class="action-btn btn-view" title="View Live">
                                                <i class="fe-eye"></i>
                                            </a>
                                        @endif

                                        {{-- Elementor-style visual builder --}}
                                        <a href="{{ route('campaign.builder', $value->id) }}" class="action-btn btn-builder" title="Open Visual Builder">
                                            <i class="fe-layout"></i>
                                        </a>

                                        {{-- Raw HTML/CSS/JavaScript builder --}}
                                        <a href="{{ route('campaign.custom-builder', $value->id) }}" class="action-btn btn-code" title="Custom Code Builder">
                                            <i class="fe-code"></i>
                                        </a>

                                        {{-- One-click complete campaign duplication --}}
                                        <form method="post" action="{{ route('campaign.duplicate', $value->id) }}" class="d-inline" onsubmit="return confirm('Duplicate this campaign and all of its content?');">
                                            @csrf
                                            <button type="submit" class="action-btn btn-copy" title="Duplicate Campaign"><i class="fe-copy"></i></button>
                                        </form>

                                        {{-- Edit campaign settings and products --}}
                                        <a href="{{route('campaign.edit',$value->id)}}" class="action-btn btn-edit" title="Edit Campaign Settings">
                                            <i class="fe-edit"></i>
                                        </a>

                                        {{-- Publication state --}}
                                        @if($value->is_published && $value->status)
                                            <form method="post" action="{{ route('campaign.unpublish', $value->id) }}" class="d-inline" onsubmit="return confirm('Unpublish this page?');">
                                                @csrf
                                                <button type="submit" class="action-btn btn-delete" title="Unpublish"><i class="fe-eye-off"></i></button>
                                            </form>
                                        @else
                                            <form method="post" action="{{ route('campaign.active') }}" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="hidden_id" value="{{ $value->id }}">
                                                <button type="submit" class="action-btn btn-view" title="Publish"><i class="fe-upload-cloud"></i></button>
                                            </form>
                                        @endif

                                        {{-- Delete --}}
                                        <form method="post" action="{{ route('campaign.destroy') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="hidden_id" value="{{ $value->id }}">
                                            <button type="submit" class="action-btn btn-delete delete-confirm" title="Delete">
                                                <i class="fe-trash-2"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div> </div> </div></div>
</div>
@endsection

@section('script')
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-buttons-bs5/js/buttons.bootstrap5.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-buttons/js/buttons.html5.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-buttons/js/buttons.flash.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-buttons/js/buttons.print.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-keytable/js/dataTables.keyTable.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/datatables.net-select/js/dataTables.select.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/pdfmake/build/pdfmake.min.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/libs/pdfmake/build/vfs_fonts.js"></script>
<script src="{{asset('/public/backEnd/')}}/assets/js/pages/datatables.init.js"></script>
@endsection