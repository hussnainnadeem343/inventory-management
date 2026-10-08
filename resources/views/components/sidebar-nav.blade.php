@props(['prefix' => 'sb'])

<a class="brand" href="{{ route('dashboard') }}">
    <span class="brand-mark"><i class="bi bi-box-seam"></i></span>
    <span class="brand-copy">
        <strong>{{ auth()->user()->shop ? auth()->user()->shop->name : 'Inventory' }}</strong>
        <small>{{ auth()->user()->shop ? 'SHOP' : 'MANAGEMENT' }}</small>
    </span>
</a>

<nav class="app-nav">
    {{-- Direct Link: Dashboard --}}
    <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
        <i class="bi bi-grid-1x2"></i><span>Dashboard</span>
    </a>

    @php
        $user = auth()->user();

        // 1. Sales & POS
        $canPos = $user->hasPermission('pos.access');
        $canInvoices = $user->hasPermission('sales.view_invoices');
        $canProducts = $user->hasPermission('products.view');
        $hasSalesGroup = $canPos || $canInvoices || $canProducts;
        $isSalesActive = request()->routeIs('pos') || request()->routeIs('sales.*') || request()->routeIs('products.*');

        // 2. Inventory & Stock
        $canStock = $user->hasPermission('stock.view');
        $hasInventoryGroup = $canStock;
        $isInventoryActive = request()->routeIs('stock.*') || request()->routeIs('inventory.*') || request()->routeIs('warehouses.*') || request()->routeIs('transfers.*') || request()->routeIs('adjustments.*');

        // 3. Purchase & Procurement
        $canPurchases = $user->hasPermission('purchases.view');
        $canCreatePurchases = $user->hasPermission('purchases.create');
        $canGrn = $user->hasPermission('purchases.grn');
        $hasPurchaseGroup = $user->isSuperAdmin() || $user->isShopAdmin() || $canPurchases || $canCreatePurchases || $canGrn;
        $isPurchaseActive = request()->routeIs('purchases.*');

        // 4. Finance & Accounts
        $canAccounts = $user->hasPermission('accounts.view');
        $canVouchers = $user->hasPermission('accounts.vouchers');
        $canFinReports = $user->hasPermission('accounts.reports');
        $hasFinanceGroup = $user->isSuperAdmin() || $user->isShopAdmin() || $canAccounts || $canVouchers || $canFinReports;
        $isFinanceActive = request()->routeIs('finance.*');

        // 5. HR & Payroll
        $canEmployees = $user->hasPermission('hr.employees');
        $canAttendance = $user->hasPermission('hr.attendance');
        $canPayroll = $user->hasPermission('hr.payroll');
        $hasHrGroup = $user->isSuperAdmin() || $user->isShopAdmin() || $canEmployees || $canAttendance || $canPayroll;
        $isHrActive = request()->routeIs('hr.*');

        // 6. Management
        $canCatalog = $user->hasPermission('catalog.manage');
        $canUsers = $user->hasAnyPermission('users.view', 'users.manage');
        $canRoles = $user->hasPermission('roles.manage');
        $hasManagementGroup = $user->isSuperAdmin() || $canCatalog || $canUsers || $canRoles;
        $isManagementActive = request()->routeIs('shops.*') || request()->routeIs('brands.*') || request()->routeIs('categories.*') || request()->routeIs('units.*') || request()->routeIs('users.*') || request()->routeIs('roles.*');

        // 7. Reports
        $canSalesReport = $user->hasPermission('reports.sales');
        $canStockReport = $user->hasPermission('reports.stock_movement');
        $canExpiryReport = $user->hasPermission('reports.expiry');
        $hasReportsGroup = $canSalesReport || $canStockReport || $canExpiryReport;
        $isReportsActive = request()->routeIs('reports.*');
    @endphp

    {{-- GROUP 1: SALES & POS --}}
    @if($hasSalesGroup)
        <div class="nav-group-item">
            <button class="nav-group-btn {{ $isSalesActive ? 'has-active' : '' }}" 
                    type="button" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#{{ $prefix }}_sales" 
                    aria-expanded="{{ $isSalesActive ? 'true' : 'false' }}">
                <span class="d-flex align-items-center gap-2">
                    <i class="bi bi-cart3"></i>
                    <span>Sales &amp; POS</span>
                </span>
                <i class="bi bi-chevron-right nav-group-chevron"></i>
            </button>
            <div class="collapse {{ $isSalesActive ? 'show' : '' }}" id="{{ $prefix }}_sales">
                <div class="nav-submenu">
                    @if($canPos)
                        <a class="{{ request()->routeIs('pos') || request()->routeIs('sales.create') ? 'active' : '' }}" href="{{ route('pos') }}">
                            <i class="bi bi-upc-scan"></i><span>POS Billing</span>
                        </a>
                    @endif
                    @if($canInvoices)
                        <a class="{{ request()->routeIs('sales.index') || request()->routeIs('sales.show') ? 'active' : '' }}" href="{{ route('sales.index') }}">
                            <i class="bi bi-receipt"></i><span>Sales Invoices</span>
                        </a>
                    @endif
                    @if($canProducts)
                        <a class="{{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">
                            <i class="bi bi-box"></i><span>Products</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- GROUP 2: INVENTORY & WAREHOUSES --}}
    @if($hasInventoryGroup)
        <div class="nav-group-item">
            <button class="nav-group-btn {{ $isInventoryActive ? 'has-active' : '' }}" 
                    type="button" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#{{ $prefix }}_inventory" 
                    aria-expanded="{{ $isInventoryActive ? 'true' : 'false' }}">
                <span class="d-flex align-items-center gap-2">
                    <i class="bi bi-layers"></i>
                    <span>Inventory</span>
                </span>
                <i class="bi bi-chevron-right nav-group-chevron"></i>
            </button>
            <div class="collapse {{ $isInventoryActive ? 'show' : '' }}" id="{{ $prefix }}_inventory">
                <div class="nav-submenu">
                    <a class="{{ request()->routeIs('stock.*') || request()->routeIs('inventory.*') ? 'active' : '' }}" href="{{ route('stock.index') }}">
                        <i class="bi bi-stack"></i><span>Stock Overview</span>
                    </a>
                    <a class="{{ request()->routeIs('warehouses.*') ? 'active' : '' }}" href="{{ route('warehouses.index') }}">
                        <i class="bi bi-buildings"></i><span>Godowns / Stores</span>
                    </a>
                    <a class="{{ request()->routeIs('transfers.*') ? 'active' : '' }}" href="{{ route('transfers.index') }}">
                        <i class="bi bi-arrow-left-right"></i><span>Stock Transfers</span>
                    </a>
                    <a class="{{ request()->routeIs('adjustments.*') ? 'active' : '' }}" href="{{ route('adjustments.index') }}">
                        <i class="bi bi-clipboard2-check"></i><span>Stock Adjustments</span>
                    </a>
                </div>
            </div>
        </div>
    @endif

    {{-- GROUP 3: PURCHASE & PROCUREMENT --}}
    @if($hasPurchaseGroup)
        <div class="nav-group-item">
            <button class="nav-group-btn {{ $isPurchaseActive ? 'has-active' : '' }}" 
                    type="button" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#{{ $prefix }}_purchases" 
                    aria-expanded="{{ $isPurchaseActive ? 'true' : 'false' }}">
                <span class="d-flex align-items-center gap-2">
                    <i class="bi bi-truck"></i>
                    <span>Purchases</span>
                </span>
                <i class="bi bi-chevron-right nav-group-chevron"></i>
            </button>
            <div class="collapse {{ $isPurchaseActive ? 'show' : '' }}" id="{{ $prefix }}_purchases">
                <div class="nav-submenu">
                    <a class="{{ request()->routeIs('purchases.suppliers.*') ? 'active' : '' }}" href="{{ route('purchases.suppliers.index') }}">
                        <i class="bi bi-person-lines-fill"></i><span>Suppliers</span>
                    </a>
                    <a class="{{ request()->routeIs('purchases.orders.*') ? 'active' : '' }}" href="{{ route('purchases.orders.index') }}">
                        <i class="bi bi-cart-check"></i><span>Purchase Orders</span>
                    </a>
                    <a class="{{ request()->routeIs('purchases.grn.*') ? 'active' : '' }}" href="{{ route('purchases.grn.index') }}">
                        <i class="bi bi-box-arrow-in-down"></i><span>Stock Inward (GRN)</span>
                    </a>
                </div>
            </div>
        </div>
    @endif

    {{-- GROUP 4: FINANCE & ACCOUNTS --}}
    @if($hasFinanceGroup)
        <div class="nav-group-item">
            <button class="nav-group-btn {{ $isFinanceActive ? 'has-active' : '' }}" 
                    type="button" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#{{ $prefix }}_finance" 
                    aria-expanded="{{ $isFinanceActive ? 'true' : 'false' }}">
                <span class="d-flex align-items-center gap-2">
                    <i class="bi bi-diagram-3"></i>
                    <span>Finance &amp; Accounts</span>
                </span>
                <i class="bi bi-chevron-right nav-group-chevron"></i>
            </button>
            <div class="collapse {{ $isFinanceActive ? 'show' : '' }}" id="{{ $prefix }}_finance">
                <div class="nav-submenu">
                    <a class="{{ request()->routeIs('finance.accounts.*') ? 'active' : '' }}" href="{{ route('finance.accounts.index') }}">
                        <i class="bi bi-list-check"></i><span>Chart of Accounts</span>
                    </a>
                    <a class="{{ request()->routeIs('finance.expenses.*') ? 'active' : '' }}" href="{{ route('finance.expenses.index') }}">
                        <i class="bi bi-cash-coin"></i><span>Daily Expenses</span>
                    </a>
                    <a class="{{ request()->routeIs('finance.payments.*') ? 'active' : '' }}" href="{{ route('finance.payments.index') }}">
                        <i class="bi bi-credit-card"></i><span>Payments &amp; Receipts</span>
                    </a>
                    <a class="{{ request()->routeIs('finance.journal.*') ? 'active' : '' }}" href="{{ route('finance.journal.index') }}">
                        <i class="bi bi-journal-bookmark"></i><span>Journal Vouchers</span>
                    </a>
                    @if($canFinReports || $user->isSuperAdmin() || $user->isShopAdmin())
                        <a class="{{ request()->routeIs('finance.reports.profit-loss') ? 'active' : '' }}" href="{{ route('finance.reports.profit-loss') }}">
                            <i class="bi bi-file-earmark-bar-graph"></i><span>Profit &amp; Loss</span>
                        </a>
                        <a class="{{ request()->routeIs('finance.reports.balance-sheet') ? 'active' : '' }}" href="{{ route('finance.reports.balance-sheet') }}">
                            <i class="bi bi-bank"></i><span>Balance Sheet</span>
                        </a>
                        <a class="{{ request()->routeIs('finance.reports.trial-balance') ? 'active' : '' }}" href="{{ route('finance.reports.trial-balance') }}">
                            <i class="bi bi-card-checklist"></i><span>Trial Balance</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- GROUP 5: HR & PAYROLL --}}
    @if($hasHrGroup)
        <div class="nav-group-item">
            <button class="nav-group-btn {{ $isHrActive ? 'has-active' : '' }}" 
                    type="button" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#{{ $prefix }}_hr" 
                    aria-expanded="{{ $isHrActive ? 'true' : 'false' }}">
                <span class="d-flex align-items-center gap-2">
                    <i class="bi bi-people"></i>
                    <span>HR &amp; Payroll</span>
                </span>
                <i class="bi bi-chevron-right nav-group-chevron"></i>
            </button>
            <div class="collapse {{ $isHrActive ? 'show' : '' }}" id="{{ $prefix }}_hr">
                <div class="nav-submenu">
                    @if($canEmployees || $user->isSuperAdmin() || $user->isShopAdmin())
                        <a class="{{ request()->routeIs('hr.departments.*') ? 'active' : '' }}" href="{{ route('hr.departments.index') }}">
                            <i class="bi bi-diagram-2"></i><span>Departments</span>
                        </a>
                        <a class="{{ request()->routeIs('hr.employees.*') ? 'active' : '' }}" href="{{ route('hr.employees.index') }}">
                            <i class="bi bi-person-badge"></i><span>Employees</span>
                        </a>
                    @endif
                    @if($canAttendance || $user->isSuperAdmin() || $user->isShopAdmin())
                        <a class="{{ request()->routeIs('hr.attendance.*') ? 'active' : '' }}" href="{{ route('hr.attendance.index') }}">
                            <i class="bi bi-calendar-check"></i><span>Daily Attendance</span>
                        </a>
                    @endif
                    @if($canPayroll || $user->isSuperAdmin() || $user->isShopAdmin())
                        <a class="{{ request()->routeIs('hr.payroll.*') ? 'active' : '' }}" href="{{ route('hr.payroll.index') }}">
                            <i class="bi bi-wallet2"></i><span>Payroll &amp; Salaries</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- GROUP 6: MANAGEMENT --}}
    @if($hasManagementGroup)
        <div class="nav-group-item">
            <button class="nav-group-btn {{ $isManagementActive ? 'has-active' : '' }}" 
                    type="button" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#{{ $prefix }}_mgmt" 
                    aria-expanded="{{ $isManagementActive ? 'true' : 'false' }}">
                <span class="d-flex align-items-center gap-2">
                    <i class="bi bi-gear"></i>
                    <span>Management</span>
                </span>
                <i class="bi bi-chevron-right nav-group-chevron"></i>
            </button>
            <div class="collapse {{ $isManagementActive ? 'show' : '' }}" id="{{ $prefix }}_mgmt">
                <div class="nav-submenu">
                    @if($user->isSuperAdmin())
                        <a class="{{ request()->routeIs('shops.*') ? 'active' : '' }}" href="{{ route('shops.index') }}">
                            <i class="bi bi-shop"></i><span>Shops</span>
                        </a>
                    @endif
                    @if($canCatalog)
                        <a class="{{ request()->routeIs('brands.*') ? 'active' : '' }}" href="{{ route('brands.index') }}">
                            <i class="bi bi-tags"></i><span>Brands</span>
                        </a>
                        <a class="{{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}">
                            <i class="bi bi-collection"></i><span>Categories</span>
                        </a>
                        <a class="{{ request()->routeIs('units.*') ? 'active' : '' }}" href="{{ route('units.index') }}">
                            <i class="bi bi-rulers"></i><span>Units of Measure</span>
                        </a>
                    @endif
                    @if($canUsers)
                        <a class="{{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                            <i class="bi bi-person-gear"></i><span>{{ $user->isSuperAdmin() ? 'Users' : 'Shop Users' }}</span>
                        </a>
                    @endif
                    @if($canRoles)
                        <a class="{{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}">
                            <i class="bi bi-shield-lock"></i><span>Roles &amp; Rights</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- GROUP 7: REPORTS --}}
    @if($hasReportsGroup)
        <div class="nav-group-item">
            <button class="nav-group-btn {{ $isReportsActive ? 'has-active' : '' }}" 
                    type="button" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#{{ $prefix }}_reports" 
                    aria-expanded="{{ $isReportsActive ? 'true' : 'false' }}">
                <span class="d-flex align-items-center gap-2">
                    <i class="bi bi-bar-chart"></i>
                    <span>Reports</span>
                </span>
                <i class="bi bi-chevron-right nav-group-chevron"></i>
            </button>
            <div class="collapse {{ $isReportsActive ? 'show' : '' }}" id="{{ $prefix }}_reports">
                <div class="nav-submenu">
                    @if($canSalesReport)
                        <a class="{{ request()->routeIs('reports.sales') ? 'active' : '' }}" href="{{ route('reports.sales') }}">
                            <i class="bi bi-graph-up-arrow"></i><span>Sales &amp; Profit</span>
                        </a>
                    @endif
                    @if($canStockReport)
                        <a class="{{ request()->routeIs('reports.stock') ? 'active' : '' }}" href="{{ route('reports.stock') }}">
                            <i class="bi bi-arrow-left-right"></i><span>Stock Movement</span>
                        </a>
                    @endif
                    @if($canExpiryReport)
                        <a class="{{ request()->routeIs('reports.expiry') ? 'active' : '' }}" href="{{ route('reports.expiry') }}">
                            <i class="bi bi-clock-history"></i><span>Expiry Alerts</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Direct Link: Logout --}}
    <div class="nav-label mt-2">ACCOUNT</div>
    <form method="post" action="{{ route('logout') }}">
        @csrf
        <button class="logout-btn" type="submit">
            <i class="bi bi-box-arrow-right"></i><span>Logout</span>
        </button>
    </form>
</nav>

<style>
.nav-group-item {
    margin-bottom: 2px;
}
.nav-group-btn {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 9px 12px;
    border: 0;
    border-radius: 8px;
    color: #b8c5d9;
    background: transparent;
    text-decoration: none;
    font-weight: 500;
    font-size: 13.5px;
    text-align: left;
    cursor: pointer;
    transition: all .16s ease;
}
.nav-group-btn:hover {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.08);
}
.nav-group-btn.has-active {
    color: #ffffff;
    font-weight: 600;
    background: rgba(255, 255, 255, 0.06);
}
.nav-group-chevron {
    font-size: 11px;
    transition: transform 0.2s ease;
    margin-left: auto;
    color: #6f84a7;
}
.nav-group-btn[aria-expanded="true"] .nav-group-chevron {
    transform: rotate(90deg);
    color: #ffffff;
}
.nav-submenu {
    padding-left: 6px;
    margin: 3px 0 6px 14px;
    border-left: 2px solid rgba(255, 255, 255, 0.12);
    display: grid;
    gap: 2px;
}
.nav-submenu a {
    padding: 7px 10px 7px 10px;
    font-size: 12.5px;
    color: #9bb0cc;
    border-radius: 6px;
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    transition: all .15s ease;
}
.nav-submenu a i {
    font-size: 13px;
    width: 16px;
    text-align: center;
    opacity: 0.85;
}
.nav-submenu a:hover {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.08);
}
.nav-submenu a.active {
    color: #ffffff;
    background: var(--primary);
    font-weight: 600;
    box-shadow: 0 2px 6px rgba(37, 99, 235, 0.28);
}
.nav-submenu a.active i {
    opacity: 1;
}
</style>
