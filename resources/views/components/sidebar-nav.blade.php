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
