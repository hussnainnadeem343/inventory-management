@extends('layouts.app')
@section('title', 'Auto Lube Services Report')
@section('content')
<div class="mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small text-muted">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('reports.index') }}" class="text-decoration-none text-muted">Reports</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Auto Lube Services</li>
        </ol>
    </nav>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h4 class="mb-0 fw-bold"><i class="bi bi-droplet-half text-primary me-2"></i>Auto Lube Pit-Stop Services Report</h4>
            <small class="text-secondary">Vehicle oil change service history, labor charges, oil grades, and technician commission tracking</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('reports.lube-services.export', request()->all()) }}" class="btn btn-success d-inline-flex align-items-center shadow-sm">
                <i class="bi bi-file-earmark-excel me-1.5 fs-6"></i>
                <span class="fw-semibold">Download Excel (.xlsx)</span>
            </a>
            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>All Reports
            </a>
        </div>
    </div>
</div>

{{-- Standalone Filter Bar (No Dashboard Widgets) --}}
<div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
    <div class="card-body p-3">
        <form method="get" action="{{ route('reports.lube-services') }}" class="d-flex flex-column gap-3">
            {{-- Date Preset Quick Buttons --}}
            <div class="d-flex flex-wrap align-items-center gap-1.5 border-bottom pb-2.5">
                <span class="text-secondary small fw-semibold me-2"><i class="bi bi-calendar3 me-1"></i>Presets:</span>
                @php
                    $presets = [
                        'today' => 'Today',
                        'yesterday' => 'Yesterday',
                        'this_week' => 'This Week',
                        'this_month' => 'This Month',
                        'last_month' => 'Last Month',
                        'this_year' => 'This Year',
                    ];
                @endphp
                @foreach($presets as $key => $label)
                    <a href="{{ route('reports.lube-services', array_merge(request()->except(['start_date', 'end_date', 'page']), ['preset' => $key])) }}" 
                       class="btn btn-sm {{ ($preset === $key) ? 'btn-primary text-white shadow-sm fw-semibold' : 'btn-outline-secondary' }}"
                       style="font-size: 12px; padding: 4px 10px;">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            {{-- Custom Range & Branch Filter Inputs --}}
            <div class="row g-2 align-items-end">
                <div class="col-sm-6 col-md-3">
                    <label class="form-label small fw-semibold text-secondary mb-1">Start Date</label>
                    <input type="date" class="form-control form-control-sm" name="start_date" value="{{ $startDate }}">
                </div>
                <div class="col-sm-6 col-md-3">
                    <label class="form-label small fw-semibold text-secondary mb-1">End Date</label>
                    <input type="date" class="form-control form-control-sm" name="end_date" value="{{ $endDate }}">
                </div>
                @if(isset($shops) && $shops->count() > 1)
                    <div class="col-sm-6 col-md-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">Branch Scope</label>
                        <select class="form-select form-select-sm" name="shop_id">
                            <option value="all" {{ empty($shopId) ? 'selected' : '' }}>All Branches (Consolidated)</option>
                            @foreach($shops as $s)
                                <option value="{{ $s->id }}" {{ $shopId == $s->id ? 'selected' : '' }}>
                                    {{ $s->name }} ({{ $s->code }}){{ $s->isBusiness() ? ' - HQ' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-sm-6 col-md-{{ isset($shops) && $shops->count() > 1 ? '3' : '6' }}">
                    <label class="form-label small fw-semibold text-secondary mb-1">Search Plate # / Customer</label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" name="search" placeholder="Plate number or customer" value="{{ $search ?? '' }}">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Filter</button>
                        @if(request()->hasAny(['preset', 'start_date', 'end_date', 'shop_id', 'search']))
                            <a class="btn btn-outline-secondary" href="{{ route('reports.lube-services') }}" title="Reset Filters"><i class="bi bi-x-lg"></i></a>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Standalone Lube Service Table --}}
<div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
    <div class="card-header bg-white py-3 px-3 d-flex justify-content-between align-items-center border-bottom">
        <div>
            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-fuel-pump text-primary me-1"></i>Pit-Stop Service Records Ledger</h6>
            <small class="text-secondary">Jobs recorded from {{ $startDate }} to {{ $endDate }}</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if($records)
                <span class="badge text-bg-light border px-2.5 py-1.5 fw-semibold text-secondary">
                    Showing Latest {{ min(10, $records->count()) }} of {{ number_format($totalRecordsCount ?? $records->count()) }} Service Jobs
                </span>
                <a href="{{ route('reports.lube-services.export', request()->all()) }}" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-file-earmark-excel me-1"></i>Export Table
                </a>
            @endif
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small">
                <tr>
                    <th class="ps-3">Date &amp; Time</th>
                    <th>Vehicle Plate</th>
                    <th>Vehicle Model</th>
                    @if(isset($shops) && $shops->count() > 1)
                        <th>Branch</th>
                    @endif
                    <th>Customer</th>
                    <th>Oil Grade</th>
                    <th class="text-end">Labor Charge</th>
                    <th>Technician</th>
                    <th class="text-center">Commission</th>
                    <th class="text-end pe-3">Invoice Total</th>
                </tr>
            </thead>
            <tbody>
                @if($records)
                    @forelse($records as $rec)
                        <tr>
                            <td class="ps-3 small text-secondary">
                                <div>{{ \Carbon\Carbon::parse($rec->created_at)->format('d M Y') }}</div>
                                <small class="text-muted">{{ \Carbon\Carbon::parse($rec->created_at)->format('h:i A') }}</small>
                            </td>
                            <td>
                                <span class="badge text-bg-dark font-monospace px-2 py-1">
                                    {{ $rec->plate_number }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $rec->make }} {{ $rec->model }}</div>
                                <small class="text-muted">{{ $rec->current_odometer ? number_format($rec->current_odometer) . ' KM' : '-' }}</small>
                            </td>
                            @if(isset($shops) && $shops->count() > 1)
                                <td>
                                    <span class="badge text-bg-light border">{{ $rec->shop_name }}</span>
                                </td>
                            @endif
                            <td>
                                <div class="fw-semibold text-dark">{{ $rec->customer_name ?: 'Walk-in Customer' }}</div>
                                <small class="text-muted">{{ $rec->customer_phone ?: '-' }}</small>
                            </td>
                            <td>
                                <span class="badge text-bg-secondary">{{ $rec->oil_grade ?: '5W-30' }}</span>
                            </td>
                            <td class="text-end text-success fw-bold">
                                Rs. {{ number_format($rec->labor_amount, 2) }}
                            </td>
                            <td class="small text-dark">{{ $rec->technician_name ?: 'Staff' }}</td>
                            <td class="text-center">
                                <span class="badge {{ $rec->commission_status === 'paid' ? 'text-bg-success' : 'text-bg-warning' }}">
                                    Rs. {{ number_format($rec->technician_commission, 2) }} ({{ ucfirst($rec->commission_status) }})
                                </span>
                            </td>
                            <td class="text-end pe-3 fw-bold text-dark">
                                Rs. {{ number_format($rec->invoice_total ?? $rec->labor_amount, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ (isset($shops) && $shops->count() > 1) ? 10 : 9 }}" class="text-center py-5 text-muted">
                                <i class="bi bi-droplet-half fs-2 d-block mb-2 text-secondary"></i>
                                No auto lube service records found for the selected period.
                            </td>
                        </tr>
                    @endforelse
                @else
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted">
                            Auto Lube Station module is not active for this shop.
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
    @if($records)
        @if(($totalRecordsCount ?? $records->count()) > 10)
            <div class="card-footer bg-light py-2.5 px-3 border-top text-secondary small d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                <span>
                    <i class="bi bi-info-circle text-primary me-1"></i>
                    Showing latest <strong>{{ $records->count() }}</strong> pit-stop services in UI preview. For complete vehicle service history, export to Excel.
                </span>
                <a href="{{ route('reports.lube-services.export', request()->all()) }}" class="btn btn-sm btn-success">
                    <i class="bi bi-file-earmark-excel me-1"></i>Download Full Excel (.xlsx)
                </a>
            </div>
        @else
            <div class="card-footer bg-white py-2 px-3 border-top text-secondary small d-flex justify-content-between align-items-center">
                <span>Showing all {{ $records->count() }} service job{{ $records->count() === 1 ? '' : 's' }} for this period.</span>
                <a href="{{ route('reports.lube-services.export', request()->all()) }}" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-file-earmark-excel me-1"></i>Export Table
                </a>
            </div>
        @endif
    @endif
</div>
@endsection
