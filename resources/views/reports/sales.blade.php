@extends('layouts.app')
@section('title', 'Sales & Net Profit Report')
@section('content')
<div class="card p-3 mb-4">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Start Date</label>
            <input class="form-control" type="date" name="start_date" value="{{ $startDate }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">End Date</label>
            <input class="form-control" type="date" name="end_date" value="{{ $endDate }}">
        </div>
        @if(auth()->user()->isSuperAdmin())
            <div class="col-md-3">
                <label class="form-label">Shop Filter</label>
                <select class="form-select" name="shop_id">
                    <option value="">All Shops Combined</option>
                    @foreach($shops as $shop)
                        <option value="{{ $shop->id }}" @selected($shopId == $shop->id)>{{ $shop->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="col-md-{{ auth()->user()->isSuperAdmin() ? '3' : '6' }} d-flex gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-filter me-1"></i>Generate Report</button>
            <a class="btn btn-outline-secondary" href="{{ route('reports.sales') }}">This Month</a>
        </div>
    </form>
</div>

<!-- Financial Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 border-start border-primary border-4 shadow-sm">
            <div class="text-muted small text-uppercase fw-semibold">Units Sold</div>
            <div class="h3 my-1 fw-bold text-dark">{{ number_format($totalUnitsSold, 2) }}</div>
            <div class="text-secondary small">Gross volume moved</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 border-start border-info border-4 shadow-sm">
            <div class="text-muted small text-uppercase fw-semibold">Net Sales Revenue</div>
            <div class="h3 my-1 fw-bold text-info">Rs. {{ number_format($netRevenue, 2) }}</div>
            <div class="text-muted small">Gross: Rs. {{ number_format($grossRevenue, 2) }} | Refunds: Rs. {{ number_format($totalRefunds, 2) }}</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 border-start border-secondary border-4 shadow-sm">
            <div class="text-muted small text-uppercase fw-semibold">Cost of Goods (COGS)</div>
            <div class="h3 my-1 fw-bold text-secondary">Rs. {{ number_format($cogs, 2) }}</div>
            <div class="text-secondary small">Inventory purchase cost</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 border-start border-success border-4 shadow-sm">
            <div class="text-muted small text-uppercase fw-semibold">Gross Profit</div>
            <div class="h3 my-1 fw-bold text-{{ $grossProfit >= 0 ? 'success' : 'danger' }}">
                Rs. {{ number_format($grossProfit, 2) }}
            </div>
            <div class="small fw-semibold text-{{ $marginPercent >= 0 ? 'success' : 'danger' }}">
                Margin: {{ number_format($marginPercent, 1) }}%
            </div>
        </div>
    </div>
</div>

<!-- Product-Wise Sales Breakdown -->
<div class="card">
    <div class="card-header bg-white py-3">
        <h3 class="h6 mb-0">Product-Wise Sales & Margin Performance</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Product</th>
                    @if(auth()->user()->isSuperAdmin())
                        <th>Shop</th>
                    @endif
                    <th>Units Sold</th>
                    <th>Sales Revenue</th>
                    <th>Cost (COGS)</th>
                    <th>Gross Profit</th>
                    <th>Profit Margin</th>
                </tr>
            </thead>
            <tbody>
                @forelse($productBreakdown as $row)
                    @php
                        $rev = (float) $row->revenue;
                        $profit = (float) $row->profit;
                        $margin = $rev > 0 ? ($profit / $rev) * 100 : 0;
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $row->inventoryItem->item_name ?? 'Deleted Item' }}</strong><br>
                            <small class="text-secondary font-monospace">{{ $row->inventoryItem->sku ?? '-' }}</small>
                        </td>
                        @if(auth()->user()->isSuperAdmin())
                            <td><span class="badge text-bg-light border">{{ $row->inventoryItem->shop->name ?? 'Default' }}</span></td>
                        @endif
                        <td class="fw-semibold">{{ number_format($row->units_sold, 2) }}</td>
                        <td class="fw-bold text-dark">Rs. {{ number_format($rev, 2) }}</td>
                        <td class="text-muted">Rs. {{ number_format($row->cost, 2) }}</td>
                        <td class="fw-bold text-{{ $profit >= 0 ? 'success' : 'danger' }}">
                            Rs. {{ number_format($profit, 2) }}
                        </td>
                        <td>
                            <span class="badge text-bg-{{ $margin >= 20 ? 'success' : ($margin > 0 ? 'warning' : 'danger') }}">
                                {{ number_format($margin, 1) }}%
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isSuperAdmin() ? 7 : 6 }}" class="text-center py-4 text-muted">
                            No sales records found for the selected period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $productBreakdown->onEachSide(1)->links() }}</div>
@endsection
