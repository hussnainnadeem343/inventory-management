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

    <div class="nav-label">MAIN</div>
    <a class="{{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">
        <i class="bi bi-box"></i><span>Products</span>
    </a>
    <a class="{{ request()->routeIs('stock.*') || request()->routeIs('inventory.*') ? 'active' : '' }}" href="{{ route('stock.index') }}">
        <i class="bi bi-layers"></i><span>Stock</span>
    </a>

    <div class="nav-label">MANAGEMENT</div>
    @if(auth()->user()->isSuperAdmin())
        <a class="{{ request()->routeIs('shops.*') ? 'active' : '' }}" href="{{ route('shops.index') }}">
            <i class="bi bi-shop"></i><span>Shops</span>
        </a>
    @endif
    <a class="{{ request()->routeIs('brands.*') ? 'active' : '' }}" href="{{ route('brands.index') }}">
        <i class="bi bi-tags"></i><span>Brands</span>
    </a>
    <a class="{{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}">
        <i class="bi bi-collection"></i><span>Categories</span>
    </a>
    @if(auth()->user()->isSuperAdmin() || auth()->user()->isShopAdmin())
        <a class="{{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
            <i class="bi bi-people"></i><span>{{ auth()->user()->isSuperAdmin() ? 'Users' : 'Shop Users' }}</span>
        </a>
    @endif

    <div class="nav-label">REPORTS</div>
    @if(auth()->user()->isSuperAdmin() || auth()->user()->isShopAdmin())
        <a class="{{ request()->routeIs('reports.sales') ? 'active' : '' }}" href="{{ route('reports.sales') }}">
            <i class="bi bi-graph-up-arrow"></i><span>Sales & Profit</span>
        </a>
        <a class="{{ request()->routeIs('reports.stock') ? 'active' : '' }}" href="{{ route('reports.stock') }}">
            <i class="bi bi-arrow-left-right"></i><span>Stock Movement</span>
        </a>
    @endif
    <a class="{{ request()->routeIs('reports.expiry') ? 'active' : '' }}" href="{{ route('reports.expiry') }}">
        <i class="bi bi-clock-history"></i><span>Expiry Alerts</span>
    </a>

    <div class="nav-label">ACCOUNT</div>
    <form method="post" action="{{ route('logout') }}">
        @csrf
        <button class="logout-btn">
            <i class="bi bi-box-arrow-right"></i><span>Logout</span>
        </button>
    </form>
</nav>
