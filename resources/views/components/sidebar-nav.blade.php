<a class="brand" href="{{ route('dashboard') }}">
    <span class="brand-mark"><i class="bi bi-box-seam"></i></span>
    <span class="brand-copy">
        <strong>{{ auth()->user()->shop ? auth()->user()->shop->name : 'Inventory' }}</strong>
        <small>{{ auth()->user()->shop ? 'SHOP' : 'MANAGEMENT' }}</small>
    </span>
</a>
<nav class="app-nav">
    <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
        <i class="bi bi-grid-1x2"></i><span>Dashboard</span>
    </a>

    @php
        $user = auth()->user();
        $canPos = $user->hasPermission('pos.access');
        $canInvoices = $user->hasPermission('sales.view_invoices');
        $canProducts = $user->hasPermission('products.view');
        $canStock = $user->hasPermission('stock.view');
        $hasMain = $canPos || $canInvoices || $canProducts || $canStock;

        $canCatalog = $user->hasPermission('catalog.manage');
        $canUsers = $user->hasAnyPermission('users.view', 'users.manage');
        $canRoles = $user->hasPermission('roles.manage');
        $hasManagement = $user->isSuperAdmin() || $canCatalog || $canUsers || $canRoles;

        $canSalesReport = $user->hasPermission('reports.sales');
        $canStockReport = $user->hasPermission('reports.stock_movement');
        $canExpiryReport = $user->hasPermission('reports.expiry');
        $hasReports = $canSalesReport || $canStockReport || $canExpiryReport;

        $canPurchases = $user->hasPermission('purchases.view');
        $canCreatePurchases = $user->hasPermission('purchases.create');
        $canGrn = $user->hasPermission('purchases.grn');
        $hasPurchaseModule = $user->isSuperAdmin() || $user->isShopAdmin() || $canPurchases || $canCreatePurchases || $canGrn;

        $canAccounts = $user->hasPermission('accounts.view');
        $canVouchers = $user->hasPermission('accounts.vouchers');
        $canFinReports = $user->hasPermission('accounts.reports');
        $hasFinanceModule = $user->isSuperAdmin() || $user->isShopAdmin() || $canAccounts || $canVouchers || $canFinReports;

        $canEmployees = $user->hasPermission('hr.employees');
        $canAttendance = $user->hasPermission('hr.attendance');
        $canPayroll = $user->hasPermission('hr.payroll');
        $hasHrModule = $user->isSuperAdmin() || $user->isShopAdmin() || $canEmployees || $canAttendance || $canPayroll;
    @endphp

    @if($hasMain)
        <div class="nav-label">MAIN</div>
        @if($canPos)
            <a class="{{ request()->routeIs('pos') || request()->routeIs('sales.create') ? 'active' : '' }}" href="{{ route('pos') }}">
                <i class="bi bi-cart3"></i><span>POS Billing</span>
            </a>
        @endif
        @if($canInvoices)
            <a class="{{ request()->routeIs('sales.index') || request()->routeIs('sales.show') ? 'active' : '' }}" href="{{ route('sales.index') }}">
                <i class="bi bi-receipt"></i><span>Invoices</span>
            </a>
        @endif
        @if($canProducts)
            <a class="{{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">
                <i class="bi bi-box"></i><span>Products</span>
            </a>
        @endif
        @if($canStock)
            <a class="{{ request()->routeIs('stock.*') || request()->routeIs('inventory.*') ? 'active' : '' }}" href="{{ route('stock.index') }}">
                <i class="bi bi-layers"></i><span>Stock</span>
            </a>
            <a class="{{ request()->routeIs('warehouses.*') ? 'active' : '' }}" href="{{ route('warehouses.index') }}">
                <i class="bi bi-buildings"></i><span>Godowns / Warehouses</span>
            </a>
            <a class="{{ request()->routeIs('transfers.*') ? 'active' : '' }}" href="{{ route('transfers.index') }}">
                <i class="bi bi-arrow-left-right"></i><span>Stock Transfers</span>
            </a>
            <a class="{{ request()->routeIs('adjustments.*') ? 'active' : '' }}" href="{{ route('adjustments.index') }}">
                <i class="bi bi-clipboard2-check"></i><span>Stock Adjustments</span>
            </a>
        @endif
    @endif

    @if($hasPurchaseModule)
        <div class="nav-label">PURCHASE & PROCUREMENT</div>
        <a class="{{ request()->routeIs('purchases.suppliers.*') ? 'active' : '' }}" href="{{ route('purchases.suppliers.index') }}">
            <i class="bi bi-truck"></i><span>Suppliers</span>
        </a>
        <a class="{{ request()->routeIs('purchases.orders.*') ? 'active' : '' }}" href="{{ route('purchases.orders.index') }}">
            <i class="bi bi-cart-check"></i><span>Purchase Orders</span>
        </a>
        <a class="{{ request()->routeIs('purchases.grn.*') ? 'active' : '' }}" href="{{ route('purchases.grn.index') }}">
            <i class="bi bi-box-arrow-in-down"></i><span>Stock Inward (GRN)</span>
        </a>
    @endif

    @if($hasFinanceModule)
        <div class="nav-label">FINANCE & ACCOUNTS</div>
        <a class="{{ request()->routeIs('finance.accounts.*') ? 'active' : '' }}" href="{{ route('finance.accounts.index') }}">
            <i class="bi bi-diagram-3"></i><span>Chart of Accounts</span>
        </a>
        <a class="{{ request()->routeIs('finance.expenses.*') ? 'active' : '' }}" href="{{ route('finance.expenses.index') }}">
            <i class="bi bi-cash-coin"></i><span>Daily Expenses</span>
        </a>
        <a class="{{ request()->routeIs('finance.payments.*') ? 'active' : '' }}" href="{{ route('finance.payments.index') }}">
            <i class="bi bi-credit-card"></i><span>Payments & Receipts</span>
        </a>
        <a class="{{ request()->routeIs('finance.journal.*') ? 'active' : '' }}" href="{{ route('finance.journal.index') }}">
            <i class="bi bi-journal-bookmark"></i><span>Journal Vouchers</span>
        </a>
        @if($canFinReports || $user->isSuperAdmin() || $user->isShopAdmin())
            <a class="{{ request()->routeIs('finance.reports.profit-loss') ? 'active' : '' }}" href="{{ route('finance.reports.profit-loss') }}">
                <i class="bi bi-file-earmark-bar-graph"></i><span>Profit & Loss</span>
            </a>
            <a class="{{ request()->routeIs('finance.reports.balance-sheet') ? 'active' : '' }}" href="{{ route('finance.reports.balance-sheet') }}">
                <i class="bi bi-bank"></i><span>Balance Sheet</span>
            </a>
            <a class="{{ request()->routeIs('finance.reports.trial-balance') ? 'active' : '' }}" href="{{ route('finance.reports.trial-balance') }}">
                <i class="bi bi-card-checklist"></i><span>Trial Balance</span>
            </a>
        @endif
    @endif

    @if($hasHrModule)
        <div class="nav-label">HR & PAYROLL</div>
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
                <i class="bi bi-wallet2"></i><span>Payroll & Salaries</span>
            </a>
        @endif
    @endif

    @if($hasManagement)
        <div class="nav-label">MANAGEMENT</div>
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
                <i class="bi bi-people"></i><span>{{ $user->isSuperAdmin() ? 'Users' : 'Shop Users' }}</span>
            </a>
        @endif
        @if($canRoles)
            <a class="{{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}">
                <i class="bi bi-shield-lock"></i><span>Roles & Rights</span>
            </a>
        @endif
    @endif

    @if($hasReports)
        <div class="nav-label">REPORTS</div>
        @if($canSalesReport)
            <a class="{{ request()->routeIs('reports.sales') ? 'active' : '' }}" href="{{ route('reports.sales') }}">
                <i class="bi bi-graph-up-arrow"></i><span>Sales & Profit</span>
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
    @endif

    <div class="nav-label">ACCOUNT</div>
    <form method="post" action="{{ route('logout') }}">
        @csrf
        <button class="logout-btn">
            <i class="bi bi-box-arrow-right"></i><span>Logout</span>
        </button>
    </form>
</nav>
