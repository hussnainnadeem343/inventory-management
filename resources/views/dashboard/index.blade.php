@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
    <div>
        <!-- <h2 class="h5 fw-bold mb-1">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ str(auth()->user()->name)->before(' ') }}</h2> -->
        <p class="text-secondary small mb-0">
            @if($currentShop)
                Store Dashboard for <strong class="text-dark">{{ $currentShop->name }}</strong> ({{ $currentShop->code }}) &bull; <span class="badge text-bg-secondary">{{ $currentShop->business_type ?? 'Retail' }}</span>
            @else
                Platform Overview across <strong class="text-dark">All Shops (Consolidated)</strong>
            @endif
        </p>
    </div>

    @if(auth()->user()->isSuperAdmin())
        <form method="get" action="{{ route('dashboard') }}" class="d-flex align-items-center gap-2 bg-white px-3 py-1.5 rounded-3 border shadow-sm">
            <label for="dashboard_shop_selector" class="form-label mb-0 fw-semibold text-secondary small text-nowrap">
                <i class="bi bi-shop text-primary me-1"></i>Active Shop:
            </label>
            <select id="dashboard_shop_selector" name="shop_id" class="form-select form-select-sm" style="min-width: 200px;" onchange="this.form.submit()">
                <option value="all" {{ empty($selectedShopId) ? 'selected' : '' }}>All Shops (Consolidated)</option>
                @foreach($shops as $shop)
                    <option value="{{ $shop->id }}" {{ $selectedShopId == $shop->id ? 'selected' : '' }}>
                        {{ $shop->name }} ({{ $shop->code }})
                    </option>
                @endforeach
            </select>
        </form>
    @else
        <div class="d-inline-flex align-items-center gap-2 bg-white px-3 py-1.5 rounded-3 border shadow-sm">
            <span class="avatar bg-primary-subtle text-primary fw-bold" style="width: 28px; height: 28px; font-size: 12px;">
                <i class="bi bi-shop"></i>
            </span>
            <div>
                <strong class="d-block text-dark small" style="line-height: 1.2;">{{ $currentShop->name ?? 'Shop' }}</strong>
                <small class="text-secondary" style="font-size: 11px;">Branch Code: {{ $currentShop->code ?? 'N/A' }}</small>
            </div>
        </div>
    @endif
</div>

{{-- ROW 1: Simple Operational KPI Cards (No verbose descriptions) --}}
<div class="row g-3 mb-3">
    {{-- Card 1: Sales Today --}}
    <div class="col-sm-6 col-xl-2">
        <a href="{{ route('sales.index') }}" class="dashboard-kpi-card shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="kpi-label">Sales Today</div>
                    <div class="kpi-value text-success">Rs. {{ number_format($salesTodayRevenue, 0) }}</div>
                </div>
                <span class="kpi-icon" style="background: #ecfdf5; color: #10b981;">
                    <i class="bi bi-cash-stack"></i>
                </span>
            </div>
        </a>
    </div>

    {{-- Card 2: Total Net Sales --}}
    <div class="col-sm-6 col-xl-2">
        <a href="{{ route('pos') }}" class="dashboard-kpi-card shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="kpi-label">Total Net Sales</div>
                    <div class="kpi-value text-primary">Rs. {{ number_format($netSalesTotal, 0) }}</div>
                </div>
                <span class="kpi-icon" style="background: #eef4ff; color: #2563eb;">
                    <i class="bi bi-wallet2"></i>
                </span>
            </div>
        </a>
    </div>

    {{-- Card 3: Pending Requisitions (PR) --}}
    <div class="col-sm-6 col-xl-2">
        <a href="{{ route('purchases.requisitions.index') }}" class="dashboard-kpi-card shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="kpi-label">Pending PRs</div>
                    <div class="kpi-value {{ $pendingPrCount > 0 ? 'text-warning' : 'text-dark' }}">{{ number_format($pendingPrCount) }}</div>
                </div>
                <span class="kpi-icon" style="background: #faf5ff; color: #8b5cf6;">
                    <i class="bi bi-file-earmark-text"></i>
                </span>
            </div>
        </a>
    </div>

    {{-- Card 4: Pending Purchase Orders (PO) --}}
    <div class="col-sm-6 col-xl-2">
        <a href="{{ route('purchases.orders.index') }}" class="dashboard-kpi-card shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="kpi-label">Active POs</div>
                    <div class="kpi-value {{ $pendingPoCount > 0 ? 'text-primary' : 'text-dark' }}">{{ number_format($pendingPoCount) }}</div>
                </div>
                <span class="kpi-icon" style="background: #fffbeb; color: #f59e0b;">
                    <i class="bi bi-cart-check"></i>
                </span>
            </div>
        </a>
    </div>

    {{-- Card 5: Inward to Bill (GRN) --}}
    <div class="col-sm-6 col-xl-2">
        <a href="{{ route('purchases.invoices.create') }}" class="dashboard-kpi-card shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="kpi-label">Inward to Bill</div>
                    <div class="kpi-value {{ $pendingGrnCount > 0 ? 'text-info' : 'text-dark' }}">{{ number_format($pendingGrnCount) }}</div>
                </div>
                <span class="kpi-icon" style="background: #f0fdfa; color: #0d9488;">
                    <i class="bi bi-box-arrow-in-down"></i>
                </span>
            </div>
        </a>
    </div>

    {{-- Card 6: Unpaid Vendor Bills --}}
    <div class="col-sm-6 col-xl-2">
        <a href="{{ route('purchases.invoices.index', ['payment_status' => 'unpaid']) }}" class="dashboard-kpi-card shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="kpi-label">Unpaid Bills</div>
                    <div class="kpi-value {{ $unpaidInvoicesCount > 0 ? 'text-danger' : 'text-muted' }}">{{ number_format($unpaidInvoicesCount) }}</div>
                </div>
                <span class="kpi-icon" style="background: #fff1f2; color: #e11d48;">
                    <i class="bi bi-receipt-cutoff"></i>
                </span>
            </div>
        </a>
    </div>
</div>

{{-- ROW 2: Clickable Module Launchpad Boxes (Style A, Same-Tab Navigation, Permission-Gated) --}}
@php
    $dashUser = auth()->user();
    $canPurchases = $dashUser->isSuperAdmin() || $dashUser->isShopAdmin() || $dashUser->hasAnyPermission('purchases.view', 'purchases.create', 'purchases.requisitions', 'purchases.igp', 'purchases.grn', 'purchases.invoices', 'purchases.return');
    $canSales = $dashUser->isSuperAdmin() || $dashUser->isShopAdmin() || $dashUser->hasAnyPermission('pos.access', 'sales.view_invoices', 'products.view');
    $canInventory = $dashUser->isSuperAdmin() || $dashUser->isShopAdmin() || $dashUser->hasAnyPermission('stock.view', 'catalog.manage', 'products.view');
    $canFinance = $dashUser->isSuperAdmin() || $dashUser->isShopAdmin() || $dashUser->hasAnyPermission('accounts.view', 'accounts.vouchers', 'accounts.reports', 'accounts.manage');
    $canHr = $dashUser->isSuperAdmin() || $dashUser->isShopAdmin() || $dashUser->hasAnyPermission('hr.employees', 'hr.attendance', 'hr.payroll');
    $canMgmt = $dashUser->isSuperAdmin() || $dashUser->isShopAdmin() || $dashUser->hasAnyPermission('users.view', 'users.manage', 'roles.manage', 'catalog.manage');
    $canReports = $dashUser->isSuperAdmin() || $dashUser->isShopAdmin() || $dashUser->hasAnyPermission('reports.sales', 'reports.stock_movement', 'reports.expiry', 'accounts.reports');
@endphp

<div class="row g-3 mb-4">
    {{-- Module 1: Purchase --}}
    @if($canPurchases)
        <div class="col-sm-6 col-md-4 col-xl-3">
            <a href="{{ route('purchases.index') }}" class="module-launchpad-card card-purchase shadow-sm h-100">
                <div class="d-flex align-items-center justify-content-between mb-auto">
                    <div class="icon-tile">
                        <i class="bi bi-truck"></i>
                    </div>
                    <i class="bi bi-arrow-up-right module-arrow fs-5"></i>
                </div>
                <div class="module-title mt-4">Purchase</div>
            </a>
        </div>
    @endif

    {{-- Module 2: Sale & POS --}}
    @if($canSales)
        <div class="col-sm-6 col-md-4 col-xl-3">
            <a href="{{ route('sales.hub') }}" class="module-launchpad-card card-sale shadow-sm h-100">
                <div class="d-flex align-items-center justify-content-between mb-auto">
                    <div class="icon-tile">
                        <i class="bi bi-cart3"></i>
                    </div>
                    <i class="bi bi-arrow-up-right module-arrow fs-5"></i>
                </div>
                <div class="module-title mt-4">Sales &amp; POS</div>
            </a>
        </div>
    @endif

    {{-- Module 3: Inventory & Stock --}}
    @if($canInventory)
        <div class="col-sm-6 col-md-4 col-xl-3">
            <a href="{{ route('inventory.hub') }}" class="module-launchpad-card card-inventory shadow-sm h-100">
                <div class="d-flex align-items-center justify-content-between mb-auto">
                    <div class="icon-tile">
                        <i class="bi bi-boxes"></i>
                    </div>
                    <i class="bi bi-arrow-up-right module-arrow fs-5"></i>
                </div>
                <div class="module-title mt-4">Inventory</div>
            </a>
        </div>
    @endif

    {{-- Module 4: Finance --}}
    @if($canFinance)
        <div class="col-sm-6 col-md-4 col-xl-3">
            <a href="{{ route('finance.index') }}" class="module-launchpad-card card-finance shadow-sm h-100">
                <div class="d-flex align-items-center justify-content-between mb-auto">
                    <div class="icon-tile">
                        <i class="bi bi-diagram-3"></i>
                    </div>
                    <i class="bi bi-arrow-up-right module-arrow fs-5"></i>
                </div>
                <div class="module-title mt-4">Finance</div>
            </a>
        </div>
    @endif

    {{-- Module 5: HR --}}
    @if($canHr)
        <div class="col-sm-6 col-md-4 col-xl-3">
            <a href="{{ route('hr.index') }}" class="module-launchpad-card card-hr shadow-sm h-100">
                <div class="d-flex align-items-center justify-content-between mb-auto">
                    <div class="icon-tile">
                        <i class="bi bi-people"></i>
                    </div>
                    <i class="bi bi-arrow-up-right module-arrow fs-5"></i>
                </div>
                <div class="module-title mt-4">HR &amp; Payroll</div>
            </a>
        </div>
    @endif

    {{-- Module 6: Management --}}
    @if($canMgmt)
        <div class="col-sm-6 col-md-4 col-xl-3">
            <a href="{{ route('management.index') }}" class="module-launchpad-card card-mgmt shadow-sm h-100">
                <div class="d-flex align-items-center justify-content-between mb-auto">
                    <div class="icon-tile">
                        <i class="bi bi-gear-wide-connected"></i>
                    </div>
                    <i class="bi bi-arrow-up-right module-arrow fs-5"></i>
                </div>
                <div class="module-title mt-4">Management</div>
            </a>
        </div>
    @endif

    {{-- Module 7: Reports --}}
    @if($canReports)
        <div class="col-sm-6 col-md-4 col-xl-3">
            <a href="{{ route('reports.index') }}" class="module-launchpad-card card-reports shadow-sm h-100">
                <div class="d-flex align-items-center justify-content-between mb-auto">
                    <div class="icon-tile">
                        <i class="bi bi-file-earmark-bar-graph"></i>
                    </div>
                    <i class="bi bi-arrow-up-right module-arrow fs-5"></i>
                </div>
                <div class="module-title mt-4">Reports</div>
            </a>
        </div>
    @endif
</div>

{{-- ROW 3: Recent Stock Activity Ledger (Strictly top 5 records) --}}
<div class="card shadow-sm border-0 bg-white">
    <div class="card-header bg-white py-2.5 px-3 d-flex justify-content-between align-items-center border-bottom">
        <h6 class="fw-bold mb-0 text-dark">Recent Stock Activity</h6>
        <a href="{{ route('stock.index') }}" class="btn btn-sm btn-outline-secondary py-1 px-2.5" style="font-size: 12px;">
            View Stock Ledger &rarr;
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small">
                <tr>
                    <th class="ps-3" style="width: 35%;">Product Item</th>
                    <th style="width: 20%;">Movement Type</th>
                    <th class="text-end" style="width: 15%;">Quantity</th>
                    <th style="width: 15%;">Staff User</th>
                    <th class="text-end pe-3" style="width: 15%;">Timestamp</th>
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
                        <td class="ps-3">
                            <div class="fw-semibold text-dark">{{ $activity->inventoryItem?->item_name ?? 'Product' }}</div>
                            @if($activity->inventoryItem?->sku)
                                <code class="small text-secondary">{{ $activity->inventoryItem->sku }}</code>
                            @endif
                        </td>
                        <td>
                            <span class="badge text-bg-{{ $badgeColor }} text-uppercase">
                                {{ str($activity->transaction_type)->replace('_', ' ') }}
                            </span>
                        </td>
                        <td class="text-end fw-bold text-{{ $isPositive ? 'success' : 'danger' }}">
                            {{ $isPositive ? '+' : '-' }}{{ number_format($activity->quantity, 2) }}
                        </td>
                        <td>
                            <span class="small text-secondary">{{ $activity->creator?->name ?? 'System' }}</span>
                        </td>
                        <td class="text-end pe-3 text-secondary small">
                            {{ $activity->created_at->diffForHumans() }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            <i class="bi bi-clock-history fs-3 d-block mb-2"></i>
                            No recent stock transactions recorded for this store.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<style>
.dashboard-kpi-card {
    display: block;
    height: 100%;
    padding: 14px 16px;
    background: #fff;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    text-decoration: none;
    color: inherit;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.dashboard-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 18px rgba(15, 31, 61, 0.08) !important;
    border-color: #cbd5e1;
}
.dashboard-kpi-card .kpi-label {
    font-size: 12px;
    color: var(--muted);
    font-weight: 600;
}
.dashboard-kpi-card .kpi-value {
    font-size: 20px;
    font-weight: 700;
    margin-top: 3px;
    line-height: 1.2;
}
.dashboard-kpi-card .kpi-icon {
    width: 38px;
    height: 38px;
    border-radius: 9px;
    display: grid;
    place-items: center;
    font-size: 18px;
    flex-shrink: 0;
}
.module-launchpad-card {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 140px;
    border-radius: var(--radius) !important;
    padding: 22px 20px;
    text-decoration: none;
    position: relative;
    overflow: hidden;
    transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
}
.module-launchpad-card:hover {
    transform: translateY(-4px);
}
.module-launchpad-card .icon-tile {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: #ffffff;
    box-shadow: 0 4px 12px rgba(15, 31, 61, 0.08);
    display: grid;
    place-items: center;
    font-size: 22px;
    transition: transform 0.2s ease;
}
.module-launchpad-card:hover .icon-tile {
    transform: scale(1.08);
}
.module-launchpad-card .module-title {
    font-size: 19px;
    font-weight: 700;
    line-height: 1.2;
}
.module-launchpad-card .module-arrow {
    opacity: 0.65;
    transition: transform 0.2s ease, opacity 0.2s ease;
}
.module-launchpad-card:hover .module-arrow {
    opacity: 1;
    transform: translate(2px, -2px);
}

/* Theme Color Variants for Highlighting */
.module-launchpad-card.card-purchase {
    background: linear-gradient(145deg, #f0f7ff 0%, #e0effe 100%);
    border: 1.5px solid #bfdbfe !important;
    color: #1e40af;
}
.module-launchpad-card.card-purchase:hover {
    box-shadow: 0 12px 28px rgba(37, 99, 235, 0.16) !important;
    border-color: #93c5fd !important;
}
.module-launchpad-card.card-purchase .icon-tile {
    color: #1d4ed8;
}

.module-launchpad-card.card-sale {
    background: linear-gradient(145deg, #f0fdf4 0%, #dcfce7 100%);
    border: 1.5px solid #bbf7d0 !important;
    color: #166534;
}
.module-launchpad-card.card-sale:hover {
    box-shadow: 0 12px 28px rgba(22, 163, 74, 0.16) !important;
    border-color: #86efac !important;
}
.module-launchpad-card.card-sale .icon-tile {
    color: #15803d;
}

.module-launchpad-card.card-finance {
    background: linear-gradient(145deg, #faf5ff 0%, #f3e8ff 100%);
    border: 1.5px solid #e9d5ff !important;
    color: #6b21a8;
}
.module-launchpad-card.card-finance:hover {
    box-shadow: 0 12px 28px rgba(126, 34, 206, 0.16) !important;
    border-color: #d8b4fe !important;
}
.module-launchpad-card.card-finance .icon-tile {
    color: #7e22ce;
}

.module-launchpad-card.card-hr {
    background: linear-gradient(145deg, #fff7ed 0%, #ffedd5 100%);
    border: 1.5px solid #fed7aa !important;
    color: #9a3412;
}
.module-launchpad-card.card-hr:hover {
    box-shadow: 0 12px 28px rgba(234, 88, 12, 0.16) !important;
    border-color: #fdba74 !important;
}
.module-launchpad-card.card-hr .icon-tile {
    color: #c2410c;
}

.module-launchpad-card.card-inventory {
    background: linear-gradient(145deg, #f0f9ff 0%, #e0f2fe 100%);
    border: 1.5px solid #bae6fd !important;
    color: #0369a1;
}
.module-launchpad-card.card-inventory:hover {
    box-shadow: 0 12px 28px rgba(2, 132, 199, 0.16) !important;
    border-color: #7dd3fc !important;
}
.module-launchpad-card.card-inventory .icon-tile {
    color: #0284c7;
}

.module-launchpad-card.card-mgmt {
    background: linear-gradient(145deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1.5px solid #cbd5e1 !important;
    color: #334155;
}
.module-launchpad-card.card-mgmt:hover {
    box-shadow: 0 12px 28px rgba(71, 85, 105, 0.16) !important;
    border-color: #94a3b8 !important;
}
.module-launchpad-card.card-mgmt .icon-tile {
    color: #475569;
}

.module-launchpad-card.card-reports {
    background: linear-gradient(145deg, #fdf2f8 0%, #fce7f3 100%);
    border: 1.5px solid #fbcfe8 !important;
    color: #be185d;
}
.module-launchpad-card.card-reports:hover {
    box-shadow: 0 12px 28px rgba(219, 39, 119, 0.16) !important;
    border-color: #f472b6 !important;
}
.module-launchpad-card.card-reports .icon-tile {
    color: #db2777;
}
</style>
@endsection
