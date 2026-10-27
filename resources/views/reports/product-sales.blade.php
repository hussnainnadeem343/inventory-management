@extends('layouts.app')
@section('title', 'Product Sales & Margin Report')
@section('content')
<div class="mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small text-muted">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('reports.index') }}" class="text-decoration-none text-muted">Reports</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Product Sales &amp; Margin</li>
        </ol>
    </nav>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h4 class="mb-0 fw-bold"><i class="bi bi-boxes text-primary me-2"></i>Product Sales &amp; Profit Margin Report</h4>
            <!-- <small class="text-secondary">Itemized product performance, units sold, gross sales, cost of goods (COGS), gross profit, and margin %</small> -->
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('reports.product-sales.export', request()->all()) }}" class="btn btn-success d-inline-flex align-items-center shadow-sm">
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
        <form method="get" action="{{ route('reports.product-sales') }}" class="d-flex flex-column gap-3">
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
                    <a href="{{ route('reports.product-sales', array_merge(request()->except(['start_date', 'end_date', 'page']), ['preset' => $key])) }}" 
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
                <div class="col-sm-6 col-md-{{ isset($shops) && $shops->count() > 1 ? '3' : '6' }} d-flex gap-2">
                    <button class="btn btn-primary btn-sm flex-grow-1" type="submit"><i class="bi bi-filter me-1"></i>Filter Products</button>
                    @if(request()->hasAny(['preset', 'start_date', 'end_date', 'shop_id']))
                        <a class="btn btn-outline-secondary btn-sm" href="{{ route('reports.product-sales') }}">Reset</a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modern Product Sales KPI Summary --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Units Sold</span>
                <span class="rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: #eff6ff; color: #2563eb; font-size: 18px;">
                    <i class="bi bi-box-seam"></i>
                </span>
            </div>
            <div class="h4 mb-1 fw-bold text-dark">{{ number_format($financialSummary['totalUnitsSold'], 2) }}</div>
            <small class="text-secondary" style="font-size: 11px;">Total product items dispatched</small>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Gross Revenue</span>
                <span class="rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: #ecfdf5; color: #10b981; font-size: 18px;">
                    <i class="bi bi-currency-dollar"></i>
                </span>
            </div>
            <div class="h4 mb-1 fw-bold text-success">Rs. {{ number_format($financialSummary['grossSubtotal'], 2) }}</div>
            <small class="text-secondary" style="font-size: 11px;">Before item &amp; bill discounts</small>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Net Sales Revenue</span>
                <span class="rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: #eff6ff; color: #2563eb; font-size: 18px;">
                    <i class="bi bi-cash-stack"></i>
                </span>
            </div>
            <div class="h4 mb-1 fw-bold text-primary">Rs. {{ number_format($financialSummary['netSales'], 2) }}</div>
            <small class="text-secondary" style="font-size: 11px;">Discounts: Rs. {{ number_format($financialSummary['totalDiscounts'], 2) }}</small>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border shadow-sm rounded-3 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Gross Profit (Margin)</span>
                <span class="rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: #f0fdf4; color: #16a34a; font-size: 18px;">
                    <i class="bi bi-graph-up-arrow"></i>
                </span>
            </div>
            <div class="h4 mb-1 fw-bold text-dark">Rs. {{ number_format($financialSummary['grossProfit'], 2) }}</div>
            <small class="text-success fw-semibold" style="font-size: 11px;">
                {{ number_format($financialSummary['marginPercent'], 1) }}% Margin (COGS: Rs. {{ number_format($financialSummary['cogs'], 2) }})
            </small>
        </div>
    </div>
</div>

{{-- Standalone Product Performance Table --}}
<div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
    <div class="card-header bg-white py-3 px-3 d-flex justify-content-between align-items-center border-bottom">
        <div>
            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-table text-primary me-1"></i>Product Sales &amp; Margin Ledger</h6>
            <small class="text-secondary">Transactions recorded from {{ $startDate }} to {{ $endDate }}</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge text-bg-light border px-2.5 py-1.5 fw-semibold text-secondary">
                Showing Top {{ min(10, $productBreakdown->count()) }} of {{ number_format($totalProductsCount ?? $productBreakdown->count()) }} Products
            </span>
            <a href="{{ route('reports.product-sales.export', request()->all()) }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1"></i>Export Table
            </a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small">
                <tr>
                    <th class="ps-3">Product Item</th>
                    <th>Brand &amp; Category</th>
                    <th class="text-center">Units Sold</th>
                    <th class="text-end">Gross Sales</th>
                    <th class="text-end">Discounts Given</th>
                    <th class="text-end">Net Revenue</th>
                    <th class="text-end">Cost (COGS)</th>
                    <th class="text-end">Gross Profit</th>
                    <th class="text-center">Margin %</th>
                    <th class="text-end pe-3">Stock Left</th>
                </tr>
            </thead>
            <tbody>
                @forelse($productBreakdown as $row)
                    @php
                        $disc = (float) $row->discount_given;
                        $margin = (float) $row->margin_percent;
                        $marginBadge = $margin >= 25 ? 'text-bg-success' : ($margin > 10 ? 'text-bg-info text-white' : ($margin > 0 ? 'text-bg-warning' : 'text-bg-danger'));
                    @endphp
                    <tr>
                        <td class="ps-3">
                            <div class="fw-bold text-dark">{{ $row->item_name }}</div>
                            <code class="small text-secondary">{{ $row->sku ?: 'No SKU' }}</code>
                        </td>
                        <td>
                            <span class="small text-dark">{{ $row->brand_name ?: '-' }}</span><br>
                            <small class="text-muted">{{ $row->category_name ?: '-' }}</small>
                        </td>
                        <td class="text-center fw-semibold">{{ number_format($row->units_sold, 2) }}</td>
                        <td class="text-end text-muted">Rs. {{ number_format($row->gross_revenue, 2) }}</td>
                        <td class="text-end {{ $disc > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                            {{ $disc > 0 ? '- Rs. ' . number_format($disc, 2) : '-' }}
                        </td>
                        <td class="text-end fw-bold text-dark">Rs. {{ number_format($row->net_revenue, 2) }}</td>
                        <td class="text-end text-muted">Rs. {{ number_format($row->cogs, 2) }}</td>
                        <td class="text-end fw-bold {{ $row->gross_profit >= 0 ? 'text-success' : 'text-danger' }}">
                            Rs. {{ number_format($row->gross_profit, 2) }}
                        </td>
                        <td class="text-center">
                            <span class="badge {{ $marginBadge }}">
                                {{ number_format($row->margin_percent, 1) }}%
                            </span>
                        </td>
                        <td class="text-end pe-3 fw-semibold text-secondary">
                            {{ number_format($row->stock_remaining, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted">
                            <i class="bi bi-boxes fs-2 d-block mb-2 text-secondary"></i>
                            No product sales recorded for the selected period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(($totalProductsCount ?? $productBreakdown->count()) > 10)
        <div class="card-footer bg-light py-2.5 px-3 border-top text-secondary small d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
            <span>
                <i class="bi bi-info-circle text-primary me-1"></i>
                Showing top <strong>{{ $productBreakdown->count() }}</strong> product items in UI preview. For complete catalog profitability, export to Excel.
            </span>
            <a href="{{ route('reports.product-sales.export', request()->all()) }}" class="btn btn-sm btn-success">
                <i class="bi bi-file-earmark-excel me-1"></i>Download Full Excel (.xlsx)
            </a>
        </div>
    @else
        <div class="card-footer bg-white py-2 px-3 border-top text-secondary small d-flex justify-content-between align-items-center">
            <span>Showing all {{ $productBreakdown->count() }} product item{{ $productBreakdown->count() === 1 ? '' : 's' }} for this period.</span>
            <a href="{{ route('reports.product-sales.export', request()->all()) }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1"></i>Export Table
            </a>
        </div>
    @endif
</div>
@endsection
