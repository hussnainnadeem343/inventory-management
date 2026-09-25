@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ str(auth()->user()->name)->before(' ') }}</h2>
        <p class="text-secondary mb-0">
            @if($currentShop)
                Store Dashboard for <strong class="text-dark">{{ $currentShop->name }}</strong> ({{ $currentShop->code }}) &bull; <span class="badge text-bg-secondary">{{ $currentShop->business_type ?? 'Retail' }}</span>
            @else
                Platform Overview across <strong class="text-dark">All Shops (Consolidated)</strong>
            @endif
        </p>
    </div>

    @if(auth()->user()->isSuperAdmin())
        <form method="get" action="{{ route('dashboard') }}" class="d-flex align-items-center gap-2 bg-white p-2 rounded-3 border shadow-sm">
            <label for="dashboard_shop_selector" class="form-label mb-0 fw-semibold text-secondary small text-nowrap">
                <i class="bi bi-shop text-primary me-1"></i>Active Shop:
            </label>
            <select id="dashboard_shop_selector" name="shop_id" class="form-select form-select-sm" style="min-width: 210px;" onchange="this.form.submit()">
                <option value="all" {{ empty($selectedShopId) ? 'selected' : '' }}>All Shops (Consolidated)</option>
                @foreach($shops as $shop)
                    <option value="{{ $shop->id }}" {{ $selectedShopId == $shop->id ? 'selected' : '' }}>
                        {{ $shop->name }} ({{ $shop->code }})
                    </option>
                @endforeach
            </select>
        </form>
    @else
        <div class="d-inline-flex align-items-center gap-2 bg-white px-3 py-2 rounded-3 border shadow-sm">
            <span class="avatar bg-primary-subtle text-primary fw-bold" style="width: 32px; height: 32px; font-size: 13px;">
                <i class="bi bi-shop"></i>
            </span>
            <div>
                <strong class="d-block text-dark small" style="line-height: 1.2;">{{ $currentShop->name ?? 'Shop' }}</strong>
                <small class="text-secondary" style="font-size: 11px;">Branch Code: {{ $currentShop->code ?? 'N/A' }}</small>
            </div>
        </div>
    @endif
</div>

{{-- KPI Metric Cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-2">
        <div class="card kpi-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-label">Total Products</div>
                    <div class="kpi-value">{{ number_format($totalItems) }}</div>
                </div>
                <span class="kpi-icon" style="background: #eef4ff; color: #2563eb;"><i class="bi bi-box"></i></span>
            </div>
            <div class="small text-secondary mt-2">In shop catalog</div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-2">
        <div class="card kpi-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-label">Remaining Stock</div>
                    <div class="kpi-value text-success">{{ number_format($totalRemaining) }}</div>
                </div>
                <span class="kpi-icon" style="background: #ecfdf3; color: #16a34a;"><i class="bi bi-layers"></i></span>
            </div>
            <div class="small text-secondary mt-2">Live units on shelf</div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-2">
        <div class="card kpi-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-label">Total Units Sold</div>
                    <div class="kpi-value text-primary">{{ number_format($totalSold) }}</div>
                </div>
                <span class="kpi-icon" style="background: #f0fdf4; color: #0284c7;"><i class="bi bi-cart-check"></i></span>
            </div>
            <div class="small text-secondary mt-2">Counter sales</div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-2">
        <div class="card kpi-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-label">Net Sales</div>
                    <div class="kpi-value" style="font-size: 20px;">Rs. {{ number_format($netRevenue, 0) }}</div>
                </div>
                <span class="kpi-icon" style="background: #fef3c7; color: #d97706;"><i class="bi bi-cash-stack"></i></span>
            </div>
            <div class="small text-secondary mt-2">Revenue (Net)</div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-2">
        <div class="card kpi-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-label">Low Stock</div>
                    <div class="kpi-value {{ $lowStockCount > 0 ? 'text-danger' : 'text-muted' }}">{{ number_format($lowStockCount) }}</div>
                </div>
                <span class="kpi-icon" style="background: #fef2f2; color: #dc2626;"><i class="bi bi-exclamation-triangle"></i></span>
            </div>
            <div class="small text-secondary mt-2">
                @if($lowStockCount > 0)
                    <a href="{{ route('stock.index') }}" class="text-danger fw-semibold text-decoration-none">Needs reorder &rarr;</a>
                @else
                    All stock healthy
                @endif
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-2">
        <div class="card kpi-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-label">Expiring Soon</div>
                    <div class="kpi-value {{ $expiringCount > 0 ? 'text-warning' : 'text-muted' }}">{{ number_format($expiringCount) }}</div>
                </div>
                <span class="kpi-icon" style="background: #fff7ed; color: #ea580c;"><i class="bi bi-clock-history"></i></span>
            </div>
            <div class="small text-secondary mt-2">
                <a href="{{ route('reports.expiry') }}" class="text-secondary text-decoration-none">View alerts &rarr;</a>
            </div>
        </div>
    </div>
</div>

{{-- Stock Movement & Categories Charts --}}
<div class="row g-4 mb-4">
    <div class="col-xl-7">
        <div class="card p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="section-title">Stock Movement</h2>
                    <small class="text-secondary">Stock In vs Counter Sales (Last 14 days)</small>
                </div>
                <span class="badge text-bg-light border">Movement Trend</span>
            </div>
            @if($movement->isNotEmpty())
                <div class="chart-wrap"><canvas id="movementChart"></canvas></div>
            @else
                <div class="empty-state py-5">
                    <i class="bi bi-graph-up text-secondary"></i>
                    <p class="mb-0 text-secondary">No stock movement recorded in the last 14 days for this shop.</p>
                </div>
            @endif
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="section-title">Stock by Category</h2>
                    <small class="text-secondary">Distribution of remaining stock units</small>
                </div>
                <span class="badge text-bg-light border">Top Categories</span>
            </div>
            @if($byCategory->isNotEmpty() && $byCategory->sum('total') > 0)
                <div class="chart-wrap"><canvas id="categoryChart"></canvas></div>
            @else
                <div class="empty-state py-5">
                    <i class="bi bi-collection text-secondary"></i>
                    <p class="mb-0 text-secondary">No categorized stock available for this shop.</p>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Brand Breakdown & Low Stock / Recent Activity --}}
<div class="row g-4">
    <div class="col-xl-5">
        <div class="card p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="section-title">Stock by Brand</h2>
                    <small class="text-secondary">Remaining volume by manufacturer/brand</small>
                </div>
                <span class="badge text-bg-light border">Top Brands</span>
            </div>
            @if($byBrand->isNotEmpty() && $byBrand->sum('total') > 0)
                <div class="chart-wrap"><canvas id="brandChart"></canvas></div>
            @else
                <div class="empty-state py-5">
                    <i class="bi bi-tags text-secondary"></i>
                    <p class="mb-0 text-secondary">No branded stock available for this shop.</p>
                </div>
            @endif
        </div>
    </div>

    <div class="col-xl-7">
        {{-- Urgent Low Stock Watchlist (if any) --}}
        @if($lowStockItems->isNotEmpty())
            <div class="card mb-4 border-warning">
                <div class="card-header bg-warning-subtle d-flex justify-content-between align-items-center py-2">
                    <div class="fw-bold text-warning-emphasis small">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Low Stock Watchlist (Needs Restock)
                    </div>
                    <a href="{{ route('stock.index') }}" class="btn btn-sm btn-outline-dark py-0" style="font-size: 11px;">Restock in Stock &rarr;</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product / SKU</th>
                                <th>Brand</th>
                                <th class="text-center">Remaining</th>
                                <th class="text-center">Alert Limit</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lowStockItems as $item)
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $item->item_name }}</div>
                                        <code class="small text-secondary">{{ $item->sku }}</code>
                                    </td>
                                    <td><span class="badge text-bg-light">{{ $item->brand?->name ?? 'N/A' }}</span></td>
                                    <td class="text-center">
                                        <span class="badge text-bg-{{ $item->quantity <= 0 ? 'danger' : 'warning' }} fs-6">
                                            {{ number_format($item->quantity, 0) }}
                                        </span>
                                    </td>
                                    <td class="text-center text-secondary">{{ number_format($item->alert_quantity ?? 5, 0) }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('stock.index', ['search' => $item->sku]) }}" class="btn btn-sm btn-outline-primary py-0" style="font-size: 11px;">
                                            <i class="bi bi-plus-circle me-1"></i>Add Stock
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Recent Stock Activity Ledger --}}
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="section-title">Recent Stock Activity</h2>
                <a href="{{ route('stock.index') }}" class="small text-decoration-none">View All Stock &rarr;</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Action</th>
                            <th class="text-end">Quantity</th>
                            <th>Staff</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentActivity as $activity)
                            @php
                                $badgeColor = match($activity->transaction_type) {
                                    'STOCK_IN' => 'success',
                                    'SALE' => 'primary',
                                    'CUSTOMER_RETURN' => 'info',
                                    'EXCHANGE_IN' => 'success',
                                    'EXCHANGE_OUT' => 'warning',
                                    'DAMAGE_LOSS' => 'danger',
                                    default => 'secondary'
                                };
                                $isPositive = in_array($activity->transaction_type, ['STOCK_IN', 'CUSTOMER_RETURN', 'EXCHANGE_IN']);
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $activity->inventoryItem?->item_name ?? 'Item' }}</div>
                                    @if($activity->inventoryItem?->sku)
                                        <small class="text-muted">{{ $activity->inventoryItem->sku }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge text-bg-{{ $badgeColor }}">
                                        {{ str($activity->transaction_type)->replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="text-end stock-number text-{{ $isPositive ? 'success' : 'danger' }}">
                                    {{ $isPositive ? '+' : '-' }}{{ number_format($activity->quantity, 2) }}
                                </td>
                                <td><small class="text-secondary">{{ $activity->creator?->name ?? 'System' }}</small></td>
                                <td class="text-secondary small">{{ $activity->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state py-4">
                                        <i class="bi bi-clock-history"></i>
                                        <p class="mb-0">No stock activity recorded for this shop yet.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = 'Inter, sans-serif';
Chart.defaults.color = '#667085';
const grid = { color: '#edf0f4' };

@if($movement->isNotEmpty())
const mEl = document.getElementById('movementChart');
if (mEl) {
    new Chart(mEl, {
        type: 'line',
        data: {
            labels: @json($movement->pluck('movement_date')),
            datasets: [
                {
                    label: 'Stock In',
                    data: @json($movement->pluck('stock_in')),
                    borderColor: '#16a34a',
                    backgroundColor: 'rgba(22, 163, 74, .08)',
                    fill: true,
                    tension: .35
                },
                {
                    label: 'Sales',
                    data: @json($movement->pluck('sold')),
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, .08)',
                    fill: true,
                    tension: .35
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, grid }
            },
            plugins: {
                legend: { labels: { usePointStyle: true, boxWidth: 7 } }
            }
        }
    });
}
@endif

@if($byCategory->isNotEmpty() && $byCategory->sum('total') > 0)
const cEl = document.getElementById('categoryChart');
if (cEl) {
    new Chart(cEl, {
        type: 'doughnut',
        data: {
            labels: @json($byCategory->pluck('label')),
            datasets: [{
                data: @json($byCategory->pluck('total')),
                backgroundColor: ['#2563eb', '#16a34a', '#d97706', '#7c3aed', '#0891b2', '#dc2626'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 7, padding: 14 } }
            }
        }
    });
}
@endif

@if($byBrand->isNotEmpty() && $byBrand->sum('total') > 0)
const bEl = document.getElementById('brandChart');
if (bEl) {
    new Chart(bEl, {
        type: 'bar',
        data: {
            labels: @json($byBrand->pluck('label')),
            datasets: [{
                data: @json($byBrand->pluck('total')),
                backgroundColor: '#4f79dd',
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            scales: {
                x: { beginAtZero: true, grid },
                y: { grid: { display: false } }
            },
            plugins: { legend: { display: false } }
        }
    });
}
@endif
</script>
@endpush
