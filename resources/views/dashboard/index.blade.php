@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@if(!empty($isPlatformAdmin))
    {{-- Platform Owner Dashboard (DenFin Super Admin) --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="h5 fw-bold mb-1 text-dark">Platform Administration &bull; DenFin ERP Owner</h4>
            <p class="text-secondary small mb-0">Platform overview: manage client businesses, monitor branch network expansion, and oversee user limits.</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            @if(isset($shops) && $shops->count() > 0)
                <form method="get" action="{{ route('dashboard') }}" class="d-flex align-items-center gap-1.5 bg-white px-2 py-1 rounded-2 border shadow-sm">
                    <label for="super_inspect_shop" class="form-label mb-0 fw-semibold text-secondary small text-nowrap" style="font-size: 12px;">
                        <i class="bi bi-eye text-primary me-1"></i>Inspect Store:
                    </label>
                    <select id="super_inspect_shop" name="shop_id" class="form-select form-select-sm border-0 py-0" style="min-width: 170px; font-size: 12px;" onchange="this.form.submit()">
                        <option value="all">Platform Overview</option>
                        @foreach($shops as $s)
                            <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->code }})</option>
                        @endforeach
                    </select>
                </form>
            @endif
            <a href="{{ route('shops.create') }}" class="btn btn-primary shadow-sm btn-sm py-1.5">
                <i class="bi bi-plus-circle me-1"></i>+ Register New Business
            </a>
            <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary shadow-sm btn-sm py-1.5">
                <i class="bi bi-diagram-3 me-1"></i>All Branches
            </a>
        </div>
    </div>

    {{-- Row 1: Platform KPI Metric Cards --}}
    <div class="row g-3 mb-4">
        {{-- Card 1: Client Businesses --}}
        <div class="col-sm-6 col-xl-3">
            <a href="{{ route('shops.index') }}" class="dashboard-kpi-card shadow-sm h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="kpi-label">Client Businesses</div>
                        <div class="kpi-value text-primary">{{ number_format($totalBusinessesCount) }}</div>
                        <small class="text-muted" style="font-size: 11px;">Registered client shops</small>
                    </div>
                    <span class="kpi-icon" style="background: #eef4ff; color: #2563eb;">
                        <i class="bi bi-building"></i>
                    </span>
                </div>
            </a>
        </div>

        {{-- Card 2: Active Accounts --}}
        <div class="col-sm-6 col-xl-3">
            <a href="{{ route('shops.index') }}" class="dashboard-kpi-card shadow-sm h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="kpi-label">Active Accounts</div>
                        <div class="kpi-value text-success">{{ number_format($activeBusinessesCount) }}</div>
                        <small class="text-muted" style="font-size: 11px;">Operational subscriptions</small>
                    </div>
                    <span class="kpi-icon" style="background: #ecfdf5; color: #10b981;">
                        <i class="bi bi-check-circle"></i>
                    </span>
                </div>
            </a>
        </div>

        {{-- Card 3: Branch Network --}}
        <div class="col-sm-6 col-xl-3">
            <a href="{{ route('branches.index') }}" class="dashboard-kpi-card shadow-sm h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="kpi-label">Sub-Branch Network</div>
                        <div class="kpi-value" style="color: #8b5cf6;">{{ number_format($totalBranchesCount) }}</div>
                        <small class="text-muted" style="font-size: 11px;">Physical sub-locations</small>
                    </div>
                    <span class="kpi-icon" style="background: #faf5ff; color: #8b5cf6;">
                        <i class="bi bi-diagram-3"></i>
                    </span>
                </div>
            </a>
        </div>

        {{-- Card 4: Platform Staff Accounts --}}
        <div class="col-sm-6 col-xl-3">
            <a href="{{ route('users.index') }}" class="dashboard-kpi-card shadow-sm h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="kpi-label">Platform Staff &amp; Users</div>
                        <div class="kpi-value" style="color: #f59e0b;">{{ number_format($totalPlatformUsers) }}</div>
                        <small class="text-muted" style="font-size: 11px;">Across all client businesses</small>
                    </div>
                    <span class="kpi-icon" style="background: #fffbeb; color: #f59e0b;">
                        <i class="bi bi-people"></i>
                    </span>
                </div>
            </a>
        </div>
    </div>

    {{-- Row 2: Platform Management Launchpad Tiles --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <a href="{{ route('shops.index') }}" class="module-launchpad-card card-mgmt shadow-sm h-100">
                <div class="d-flex align-items-center justify-content-between mb-auto">
                    <div class="icon-tile">
                        <i class="bi bi-building"></i>
                    </div>
                    <i class="bi bi-arrow-up-right module-arrow fs-5"></i>
                </div>
                <div class="module-title mt-4">Client Businesses</div>
                <small class="text-secondary d-block mt-1" style="font-size: 12px;">Manage client shops, business models, and subscription quotas.</small>
            </a>
        </div>

        <div class="col-sm-6 col-xl-3">
            <a href="{{ route('shops.create') }}" class="module-launchpad-card card-purchase shadow-sm h-100">
                <div class="d-flex align-items-center justify-content-between mb-auto">
                    <div class="icon-tile">
                        <i class="bi bi-plus-circle"></i>
                    </div>
                    <i class="bi bi-arrow-up-right module-arrow fs-5"></i>
                </div>
                <div class="module-title mt-4">+ Register Business</div>
                <small class="text-secondary d-block mt-1" style="font-size: 12px;">Onboard a new client business, assign business owner, and set tier limits.</small>
            </a>
        </div>

        <div class="col-sm-6 col-xl-3">
            <a href="{{ route('branches.index') }}" class="module-launchpad-card card-inventory shadow-sm h-100">
                <div class="d-flex align-items-center justify-content-between mb-auto">
                    <div class="icon-tile">
                        <i class="bi bi-diagram-3"></i>
                    </div>
                    <i class="bi bi-arrow-up-right module-arrow fs-5"></i>
                </div>
                <div class="module-title mt-4">Branch Network</div>
                <small class="text-secondary d-block mt-1" style="font-size: 12px;">Review physical locations, sub-branches, and branch quotas across clients.</small>
            </a>
        </div>

        <div class="col-sm-6 col-xl-3">
            <a href="{{ route('users.index') }}" class="module-launchpad-card card-hr shadow-sm h-100">
                <div class="d-flex align-items-center justify-content-between mb-auto">
                    <div class="icon-tile">
                        <i class="bi bi-people"></i>
                    </div>
                    <i class="bi bi-arrow-up-right module-arrow fs-5"></i>
                </div>
                <div class="module-title mt-4">Staff Directory</div>
                <small class="text-secondary d-block mt-1" style="font-size: 12px;">Oversee platform-wide administrators, shopkeepers, and staff accounts.</small>
            </a>
        </div>
    </div>

    {{-- Row 3: Client Businesses Overview Table --}}
    <div class="card shadow-sm border-0 bg-white mb-4">
        <div class="card-header bg-white py-3 px-3 d-flex justify-content-between align-items-center border-bottom">
            <div>
                <h6 class="fw-bold mb-0 text-dark">Recent Client Businesses</h6>
                <small class="text-secondary">Businesses onboarded onto DENFIN ERP</small>
            </div>
            <a href="{{ route('shops.index') }}" class="btn btn-sm btn-outline-primary py-1 px-2.5" style="font-size: 12px;">
                View All Businesses &rarr;
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th class="ps-3" style="width: 25%;">Business / Shop</th>
                        <th style="width: 15%;">Business Type</th>
                        <th style="width: 15%;">Branch Quota</th>
                        <th style="width: 15%;">Staff Quota</th>
                        <th style="width: 10%;">Status</th>
                        <th style="width: 10%;">Created</th>
                        <th class="text-end pe-3" style="width: 10%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentBusinesses as $biz)
                        <tr>
                            <td class="ps-3">
                                <div class="fw-bold text-dark">{{ $biz->name }}</div>
                                <code class="small text-secondary">{{ $biz->code }}</code>
                            </td>
                            <td>
                                <span class="badge text-bg-light border text-dark">
                                    {{ $biz->business_type ?: 'General Retail' }}
                                </span>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark">{{ $biz->branches_count + 1 }}</span>
                                <span class="text-muted small">/ {{ $biz->max_branches }}</span>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark">{{ $biz->users_count }}</span>
                                <span class="text-muted small">/ {{ $biz->max_users }}</span>
                            </td>
                            <td>
                                <span class="badge text-bg-{{ $biz->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($biz->status) }}
                                </span>
                            </td>
                            <td class="text-secondary small">
                                {{ $biz->created_at?->format('d M Y') ?? 'N/A' }}
                            </td>
                            <td class="text-end pe-3">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('shops.edit', $biz) }}" class="btn btn-outline-secondary" title="Edit Business">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="{{ route('branches.index', ['business_id' => $biz->id]) }}" class="btn btn-outline-primary" title="View Branches">
                                        <i class="bi bi-diagram-3"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="bi bi-building fs-3 d-block mb-2 text-secondary"></i>
                                No businesses registered yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@else
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
    <div>
        <!-- <h2 class="h5 fw-bold mb-1">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ str(auth()->user()->name)->before(' ') }}</h2> -->
        <p class="text-secondary small mb-0">
            @if($currentShop)
                Store Dashboard for <strong class="text-dark">{{ $currentShop->name }}</strong> ({{ $currentShop->code }}) &bull; <span class="badge text-bg-secondary">{{ $currentShop->business_type ?? 'Retail' }}</span>
            @elseif(auth()->user()->isBusinessOwner())
                Business Overview across <strong class="text-dark">{{ auth()->user()->shop?->getRootBusiness()?->name ?? 'Business' }} (All Branches Consolidated)</strong>
            @else
                Platform Overview across <strong class="text-dark">All Shops (Consolidated)</strong>
            @endif
        </p>
    </div>

    @if(auth()->user()->isSuperAdmin() || (auth()->user()->isBusinessOwner() && $shops->count() > 1))
        <form method="get" action="{{ route('dashboard') }}" class="d-flex align-items-center gap-2 bg-white px-3 py-1.5 rounded-3 border shadow-sm">
            <label for="dashboard_shop_selector" class="form-label mb-0 fw-semibold text-secondary small text-nowrap">
                <i class="bi bi-shop text-primary me-1"></i>{{ auth()->user()->isBusinessOwner() ? 'Active Branch:' : 'Active Shop:' }}
            </label>
            <select id="dashboard_shop_selector" name="shop_id" class="form-select form-select-sm" style="min-width: 200px;" onchange="this.form.submit()">
                <option value="all" {{ empty($selectedShopId) ? 'selected' : '' }}>
                    {{ auth()->user()->isBusinessOwner() ? 'All Branches (Consolidated)' : 'All Shops (Consolidated)' }}
                </option>
                @foreach($shops as $shop)
                    <option value="{{ $shop->id }}" {{ $selectedShopId == $shop->id ? 'selected' : '' }}>
                        {{ $shop->name }} ({{ $shop->code }}){{ $shop->isBusiness() ? ' - HQ' : '' }}
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

{{-- ROW 1: Simple Operational KPI Cards (Permission-Gated) --}}
@if($canSeeSales || $canSeePurchases)
<div class="row g-3 mb-3">
    @if($canSeeSales)
        {{-- Card 1: Sales Today --}}
        <div class="col-sm-6 col-xl-2">
            <a href="{{ route('sales.index') }}" class="dashboard-kpi-card shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="kpi-label">Sales Today</div>
                        <div class="kpi-value text-success">Rs. {{ number_format($salesTodayRevenue, 0) }}</div>
                        @if(isset($salesTodayCash))
                            <small class="text-secondary d-block" style="font-size: 10px; margin-top: 2px;">
                                Cash: {{ number_format($salesTodayCash, 0) }} &bull; Dig: {{ number_format($salesTodayDigital, 0) }}
                            </small>
                        @endif
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
    @endif

    @if($canSeePurchases)
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
    @endif
</div>
@endif

{{-- ROW 2: Clickable Module Launchpad Boxes (Style A, Same-Tab Navigation, Permission-Gated) --}}
@php
    $dashUser = auth()->user();
    $dashShop = $currentShop ?? $dashUser->shop;
    $canPurchasesModule = ($dashShop ? $dashShop->isModuleAvailable('purchases') : true) && $dashUser->hasAnyPermission('purchases.view', 'purchases.create', 'purchases.requisitions', 'purchases.igp', 'purchases.grn', 'purchases.invoices', 'purchases.return');
    $canSalesModule = ($dashShop ? $dashShop->isModuleAvailable('pos') : true) && $dashUser->hasAnyPermission('pos.access', 'sales.view_invoices');
    $canInventoryModule = ($dashShop ? $dashShop->isModuleAvailable('stock') : true) && $dashUser->hasAnyPermission('stock.view', 'products.view', 'catalog.manage');
    $canFinanceModule = ($dashShop ? $dashShop->isModuleAvailable('accounts') : true) && $dashUser->hasAnyPermission('accounts.view', 'accounts.vouchers', 'accounts.reports', 'accounts.manage');
    $canHrModule = ($dashShop ? $dashShop->isModuleAvailable('hr') : true) && $dashUser->hasAnyPermission('hr.employees', 'hr.attendance', 'hr.payroll');
    $canMgmtModule = $dashUser->isSuperAdmin() || $dashUser->isBusinessOwner() || $dashUser->hasAnyPermission('users.view', 'users.manage', 'roles.manage');
    $canReportsModule = ($dashShop ? $dashShop->isModuleAvailable('reports') : true) && $dashUser->hasAnyPermission('reports.sales', 'reports.stock_movement', 'reports.expiry', 'accounts.reports');
    $canLubeModule = ($dashShop ? $dashShop->isModuleAvailable('lube') : $dashUser->isSuperAdmin()) && $dashUser->hasAnyPermission('lube.pos', 'lube.analytics', 'lube.packages', 'lube.vehicles', 'lube.reminders', 'lube.technicians', 'lube.commissions', 'lube.decanting');
@endphp

<div class="row g-3 mb-4">
    {{-- Module 1: Auto Lube Station (1st Place for Lube Shops & Super Admin) --}}
    @if($canLubeModule)
        <div class="col-sm-6 col-md-4 col-xl-3">
            <a href="{{ route('lube.hub') }}" class="module-launchpad-card card-lube shadow-sm h-100">
                <div class="d-flex align-items-center justify-content-between mb-auto">
                    <div class="icon-tile">
                        <i class="bi bi-speedometer2"></i>
                    </div>
                    <i class="bi bi-arrow-up-right module-arrow fs-5"></i>
                </div>
                <div class="module-title mt-4">Auto Lube Station</div>
            </a>
        </div>
    @endif

    {{-- Module 2: Purchase --}}
    @if($canPurchasesModule)
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

    {{-- Module 3: Sales & POS --}}
    @if($canSalesModule)
        <div class="col-sm-6 col-md-4 col-xl-3">
            <a href="{{ route('sales.hub') }}" class="module-launchpad-card card-sales shadow-sm h-100">
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

    {{-- Module 4: Inventory --}}
    @if($canInventoryModule)
        <div class="col-sm-6 col-md-4 col-xl-3">
            <a href="{{ route('inventory.hub') }}" class="module-launchpad-card card-inventory shadow-sm h-100">
                <div class="d-flex align-items-center justify-content-between mb-auto">
                    <div class="icon-tile">
                        <i class="bi bi-boxes"></i>
                    </div>
                    <i class="bi bi-arrow-up-right module-arrow fs-5"></i>
                </div>
                <div class="module-title mt-4">Inventory &amp; Warehouses</div>
            </a>
        </div>
    @endif

    {{-- Module 5: Finance & Accounts --}}
    @if($canFinanceModule)
        <div class="col-sm-6 col-md-4 col-xl-3">
            <a href="{{ route('finance.index') }}" class="module-launchpad-card card-finance shadow-sm h-100">
                <div class="d-flex align-items-center justify-content-between mb-auto">
                    <div class="icon-tile">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <i class="bi bi-arrow-up-right module-arrow fs-5"></i>
                </div>
                <div class="module-title mt-4">Finance &amp; Accounts</div>
            </a>
        </div>
    @endif

    {{-- Module 6: HR & Payroll --}}
    @if($canHrModule)
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

    {{-- Module 7: Reports --}}
    @if($canReportsModule)
        <div class="col-sm-6 col-md-4 col-xl-3">
            <a href="{{ route('reports.index') }}" class="module-launchpad-card card-reports shadow-sm h-100">
                <div class="d-flex align-items-center justify-content-between mb-auto">
                    <div class="icon-tile">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <i class="bi bi-arrow-up-right module-arrow fs-5"></i>
                </div>
                <div class="module-title mt-4">Reports &amp; Analytics</div>
            </a>
        </div>
    @endif

    {{-- Module 8: Management & Settings --}}
    @if($canMgmtModule)
        <div class="col-sm-6 col-md-4 col-xl-3">
            <a href="{{ route('management.index') }}" class="module-launchpad-card card-management shadow-sm h-100">
                <div class="d-flex align-items-center justify-content-between mb-auto">
                    <div class="icon-tile">
                        <i class="bi bi-gear"></i>
                    </div>
                    <i class="bi bi-arrow-up-right module-arrow fs-5"></i>
                </div>
                <div class="module-title mt-4">Management &amp; Settings</div>
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
@endif

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

.module-launchpad-card.card-lube {
    background: linear-gradient(145deg, #ecfdf5 0%, #d1fae5 100%);
    border: 1.5px solid #a7f3d0 !important;
    color: #065f46;
}
.module-launchpad-card.card-lube:hover {
    box-shadow: 0 12px 28px rgba(16, 185, 129, 0.18) !important;
    border-color: #6ee7b7 !important;
}
.module-launchpad-card.card-lube .icon-tile {
    color: #059669;
}
</style>
@endsection
