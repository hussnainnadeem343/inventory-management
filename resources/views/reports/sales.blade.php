@extends('layouts.app')
@section('title', 'Sales Invoices Report')
@section('content')
<div class="mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small text-muted">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('reports.index') }}" class="text-decoration-none text-muted">Reports</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Sales Invoices Report</li>
        </ol>
    </nav>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h4 class="mb-0 fw-bold"><i class="bi bi-receipt text-primary me-2"></i>Sales Invoices Report</h4>
            <!-- <small class="text-secondary">Comprehensive date-range ledger of sales bills, customer invoices, payment methods, and discounts</small> -->
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('reports.sales.export', request()->all()) }}" class="btn btn-success d-inline-flex align-items-center shadow-sm">
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
        <form method="get" action="{{ route('reports.sales') }}" class="d-flex flex-column gap-3">
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
                    <a href="{{ route('reports.sales', array_merge(request()->except(['start_date', 'end_date', 'page']), ['preset' => $key])) }}" 
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
                    <label class="form-label small fw-semibold text-secondary mb-1">Search Customer / Invoice</label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" name="search" placeholder="Invoice # or Customer name" value="{{ $search ?? '' }}">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Filter</button>
                        @if(request()->hasAny(['preset', 'start_date', 'end_date', 'shop_id', 'search']))
                            <a class="btn btn-outline-secondary" href="{{ route('reports.sales') }}" title="Reset Filters"><i class="bi bi-x-lg"></i></a>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modern Period Financial KPI Summary --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Completed Invoices</span>
                <span class="rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: #eff6ff; color: #2563eb; font-size: 18px;">
                    <i class="bi bi-receipt"></i>
                </span>
            </div>
            <div class="h4 mb-1 fw-bold text-dark">{{ number_format($financialSummary['totalInvoices']) }}</div>
            <small class="text-secondary" style="font-size: 11px;">Total sales bills generated</small>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Net Sales Revenue</span>
                <span class="rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: #ecfdf5; color: #10b981; font-size: 18px;">
                    <i class="bi bi-cash-coin"></i>
                </span>
            </div>
            <div class="h4 mb-1 fw-bold text-success">Rs. {{ number_format($financialSummary['netSales'], 2) }}</div>
            <small class="text-secondary" style="font-size: 11px;">Gross sales less discounts</small>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Total Discounts Given</span>
                <span class="rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: #fffbeb; color: #f59e0b; font-size: 18px;">
                    <i class="bi bi-tag"></i>
                </span>
            </div>
            <div class="h4 mb-1 fw-bold text-warning">Rs. {{ number_format($financialSummary['totalDiscounts'], 2) }}</div>
            <small class="text-secondary" style="font-size: 11px;">Item &amp; bill-level cuts</small>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Cost of Goods (COGS)</span>
                <span class="rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: #f0fdf4; color: #16a34a; font-size: 18px;">
                    <i class="bi bi-graph-up-arrow"></i>
                </span>
            </div>
            <div class="h4 mb-1 fw-bold text-muted">Rs. {{ number_format($financialSummary['cogs'], 2) }}</div>
            <small class="text-secondary" style="font-size: 11px;">
                <span class="text-success fw-bold">Gross Profit: Rs. {{ number_format($financialSummary['grossProfit'], 2) }}</span> ({{ number_format($financialSummary['marginPercent'], 1) }}%)
            </small>
        </div>
    </div>
</div>

{{-- Auto Lube Service Breakdown (Shown when service jobs exist) --}}
@if(($financialSummary['lubeServicesCount'] ?? 0) > 0)
    <div class="card border shadow-sm rounded-3 mb-4 bg-white p-3">
        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
            <div class="d-flex align-items-center gap-2">
                <span class="rounded-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: #f5f3ff; color: #7c3aed; font-size: 16px;">
                    <i class="bi bi-droplet-half"></i>
                </span>
                <h6 class="fw-bold mb-0 text-dark">Auto Lube Service Breakdown</h6>
            </div>
            <span class="badge text-bg-light border text-secondary small">
                {{ number_format($financialSummary['lubeServicesCount']) }} Service Job{{ $financialSummary['lubeServicesCount'] == 1 ? '' : 's' }}
            </span>
        </div>
        <div class="row g-3">
            <div class="col-sm-4">
                <div class="p-3 rounded-3 bg-light border">
                    <small class="text-secondary d-block text-uppercase fw-semibold" style="font-size: 11px;">Physical Parts &amp; Oil Sales</small>
                    <span class="h5 mb-0 fw-bold text-dark d-block mt-1">Rs. {{ number_format($financialSummary['partsRevenue'], 2) }}</span>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="p-3 rounded-3 bg-light border">
                    <small class="text-secondary d-block text-uppercase fw-semibold" style="font-size: 11px;">Service Labor Fees</small>
                    <span class="h5 mb-0 fw-bold text-success d-block mt-1">Rs. {{ number_format($financialSummary['lubeLaborTotal'], 2) }}</span>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="p-3 rounded-3 bg-light border">
                    <small class="text-secondary d-block text-uppercase fw-semibold" style="font-size: 11px;">Technician Commission</small>
                    <span class="h5 mb-0 fw-bold text-warning d-block mt-1">Rs. {{ number_format($financialSummary['lubeCommissionsTotal'], 2) }}</span>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Multi-Branch Performance Leaderboard (for Business Owner when viewing All Branches) --}}
@if(auth()->user()->isBusinessOwner() && ($isConsolidated ?? false) && isset($branchComparison) && $branchComparison->isNotEmpty())
    <div class="card border-0 shadow-sm rounded-3 bg-white mb-3">
        <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-diagram-3 text-primary me-1"></i>Multi-Branch Performance Leaderboard</h6>
            <span class="badge text-bg-light border text-secondary">{{ $branchComparison->count() }} Branches</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Branch</th>
                        <th class="text-center">Invoices</th>
                        <th class="text-end">Net Sales</th>
                        <th class="text-end">COGS</th>
                        <th class="text-end">Gross Profit</th>
                        <th class="text-center">Margin %</th>
                        <th class="text-end pe-3">Network Share</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($branchComparison as $b)
                        <tr>
                            <td class="ps-3">
                                <strong>{{ $b->name }}</strong>
                                <code class="small text-secondary ms-1">{{ $b->code }}</code>
                                @if($b->is_hq)<span class="badge text-bg-primary ms-1" style="font-size: 10px;">HQ</span>@endif
                            </td>
                            <td class="text-center">{{ $b->total_invoices }}</td>
                            <td class="text-end fw-bold text-dark">Rs. {{ number_format($b->net_sales, 2) }}</td>
                            <td class="text-end text-muted">Rs. {{ number_format($b->cogs, 2) }}</td>
                            <td class="text-end text-success fw-bold">Rs. {{ number_format($b->gross_profit, 2) }}</td>
                            <td class="text-center"><span class="badge text-bg-success">{{ number_format($b->margin_percent, 1) }}%</span></td>
                            <td class="text-end pe-3 fw-bold text-primary">{{ number_format($b->share_percent, 1) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

{{-- Standalone Invoices Ledger Table --}}
<div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
    <div class="card-header bg-white py-3 px-3 d-flex justify-content-between align-items-center border-bottom">
        <div>
            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-list-check text-primary me-1"></i>Sales Invoices Ledger</h6>
            <small class="text-secondary">Transactions recorded from {{ $startDate }} to {{ $endDate }}</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge text-bg-light border px-2.5 py-1.5 fw-semibold text-secondary">
                Showing Latest {{ min(10, $invoices->count()) }} of {{ number_format($totalInvoicesCount ?? $invoices->count()) }} Invoices
            </span>
            <a href="{{ route('reports.sales.export', request()->all()) }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1"></i>Export Table
            </a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small">
                <tr>
                    <th class="ps-3">Invoice #</th>
                    <th>Date &amp; Time</th>
                    @if(isset($shops) && $shops->count() > 1)
                        <th>Branch</th>
                    @endif
                    <th>Customer</th>
                    <th class="text-center">Items</th>
                    <th class="text-end">Subtotal</th>
                    <th class="text-end">Discounts</th>
                    <th class="text-end">Net Amount</th>
                    <th class="text-center">Payment</th>
                    <th>Cashier</th>
                    <th class="text-end pe-3">Receipt</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $inv)
                    @php
                        $payClass = match(strtolower($inv->payment_method ?? 'cash')) {
                            'cash' => 'text-bg-success',
                            'card' => 'text-bg-primary',
                            'bank' => 'text-bg-info text-white',
                            'credit' => 'text-bg-warning',
                            default => 'text-bg-secondary'
                        };
                        $totDiscount = ($inv->discount ?? 0) + ($inv->items_discount_total ?? 0);
                    @endphp
                    <tr>
                        <td class="ps-3 fw-bold text-dark">
                            <a href="{{ route('sales.show', $inv) }}" class="text-decoration-none font-monospace fw-semibold">
                                {{ $inv->invoice_no }}
                            </a>
                        </td>
                        <td class="small text-secondary">
                            <div>{{ $inv->created_at?->format('d M Y') }}</div>
                            <small class="text-muted">{{ $inv->created_at?->format('h:i A') }}</small>
                        </td>
                        @if(isset($shops) && $shops->count() > 1)
                            <td>
                                <span class="badge text-bg-light border">{{ $inv->shop->name ?? 'Default' }}</span>
                            </td>
                        @endif
                        <td>
                            <div class="fw-semibold text-dark">{{ $inv->customer_name ?: 'Walk-in Customer' }}</div>
                            <small class="text-muted font-monospace">{{ $inv->customer_phone ?: '-' }}</small>
                        </td>
                        <td class="text-center fw-semibold">{{ $inv->total_items }}</td>
                        <td class="text-end text-muted">Rs. {{ number_format($inv->subtotal, 2) }}</td>
                        <td class="text-end {{ $totDiscount > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                            {{ $totDiscount > 0 ? '- Rs. ' . number_format($totDiscount, 2) : '-' }}
                        </td>
                        <td class="text-end fw-bold text-dark">Rs. {{ number_format($inv->total_amount, 2) }}</td>
                        <td class="text-center">
                            <span class="badge {{ $payClass }} px-2 py-1 text-uppercase" style="font-size: 11px;">
                                {{ $inv->payment_method ?: 'CASH' }}
                            </span>
                        </td>
                        <td class="small text-secondary">{{ $inv->creator->name ?? 'Staff' }}</td>
                        <td class="text-end pe-3">
                            <a href="{{ route('sales.show', $inv) }}" class="btn btn-sm btn-outline-secondary py-0.5 px-2" title="View & Print Invoice">
                                <i class="bi bi-printer"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ (isset($shops) && $shops->count() > 1) ? 11 : 10 }}" class="text-center py-5 text-muted">
                            <i class="bi bi-receipt fs-2 d-block mb-2 text-secondary"></i>
                            No sales invoices found for the selected period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($invoices->count() > 0)
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td class="ps-3" colspan="{{ (isset($shops) && $shops->count() > 1) ? 5 : 4 }}">Period Totals:</td>
                        <td class="text-end">Rs. {{ number_format($financialSummary['grossSubtotal'], 2) }}</td>
                        <td class="text-end text-danger">- Rs. {{ number_format($financialSummary['totalDiscounts'], 2) }}</td>
                        <td class="text-end text-primary">Rs. {{ number_format($financialSummary['netSales'], 2) }}</td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
    @if(($totalInvoicesCount ?? $invoices->count()) > 10)
        <div class="card-footer bg-light py-2.5 px-3 border-top text-secondary small d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
            <span>
                <i class="bi bi-info-circle text-primary me-1"></i>
                Showing latest <strong>{{ $invoices->count() }}</strong> invoices in UI preview. For complete period records, export to Excel.
            </span>
            <a href="{{ route('reports.sales.export', request()->all()) }}" class="btn btn-sm btn-success">
                <i class="bi bi-file-earmark-excel me-1"></i>Download Full Excel (.xlsx)
            </a>
        </div>
    @else
        <div class="card-footer bg-white py-2 px-3 border-top text-secondary small d-flex justify-content-between align-items-center">
            <span>Showing all {{ $invoices->count() }} invoice{{ $invoices->count() === 1 ? '' : 's' }} recorded for this period.</span>
            <a href="{{ route('reports.sales.export', request()->all()) }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1"></i>Export Table
            </a>
        </div>
    @endif
</div>
@endsection
