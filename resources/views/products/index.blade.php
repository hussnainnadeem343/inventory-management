@extends('layouts.app')
@section('title', 'Product Catalog')
@section('content')
<div class="card p-3 mb-3">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-lg-{{ auth()->user()->isSuperAdmin() ? '3' : '4' }} col-md-6">
            <label class="form-label">Search Product</label>
            <input class="form-control" name="search" value="{{ $filters['search'] }}" placeholder="Product name or SKU">
        </div>
        @if(auth()->user()->isSuperAdmin())
            <div class="col-sm-6 col-lg-2 col-md-3">
                <label class="form-label">Shop</label>
                <select class="form-select" name="shop_id">
                    <option value="">All Shops</option>
                    @foreach($shops as $shop)
                        <option value="{{ $shop->id }}" @selected(($filters['shop_id'] ?? null) == $shop->id)>{{ $shop->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="col-sm-6 col-lg-2 col-md-3">
            <label class="form-label">Brand</label>
            <select class="form-select" name="brand_id">
                <option value="">All Brands</option>
                @foreach($brands as $brand)
                    <option value="{{ $brand->id }}" @selected($filters['brand_id'] == $brand->id)>{{ $brand->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-6 col-lg-2 col-md-3">
            <label class="form-label">Category</label>
            <select class="form-select" name="category_id">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected($filters['category_id'] == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-6 col-lg-1 col-md-3">
            <label class="form-label">Expiry</label>
            <select class="form-select" name="expiry_status">
                <option value="">All</option>
                <option value="expiring_soon" @selected(($filters['expiry_status'] ?? null) === 'expiring_soon')>Soon</option>
                <option value="expired" @selected(($filters['expiry_status'] ?? null) === 'expired')>Expired</option>
            </select>
        </div>
        <div class="col-sm-6 col-lg-1 col-md-3">
            <label class="form-label">Per page</label>
            <select class="form-select" name="per_page">
                @foreach([10, 20, 30, 50, 100] as $size)
                    <option @selected($perPage === $size)>{{ $size }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-1 d-flex gap-1">
            <button class="btn btn-primary w-100" type="submit">Filter</button>
            <a class="btn btn-outline-secondary" href="{{ route('products.index') }}" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</div>

@if(auth()->user()->isSuperAdmin() || auth()->user()->isShopAdmin())
    <div class="text-end mb-3">
        <a class="btn btn-primary" href="{{ route('products.create') }}"><i class="bi bi-plus-circle me-1"></i>+ Add Product</a>
    </div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Product / SKU</th>
                    @if(auth()->user()->isSuperAdmin())
                        <th>Shop</th>
                    @endif
                    <th>Brand & Category</th>
                    <th>Variant / Pack</th>
                    <th>Expiry Date</th>
                    @if(auth()->user()->isSuperAdmin() || auth()->user()->isShopAdmin())
                        <th>Purchase Price</th>
                    @endif
                    <th>Selling Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    @php
                        $canManage = auth()->user()->isSuperAdmin() || (auth()->user()->isShopAdmin() && auth()->user()->shop_id === $product->shop_id);
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $product->item_name }}</strong><br>
                            <small class="text-secondary font-monospace">{{ $product->sku ?: '-' }}</small>
                        </td>
                        @if(auth()->user()->isSuperAdmin())
                            <td><span class="badge text-bg-light border">{{ $product->shop->name ?? 'Default' }}</span></td>
                        @endif
                        <td>
                            <span>{{ $product->brand->name ?? '-' }}</span><br>
                            <small class="text-muted">{{ $product->category->name ?? '-' }}</small>
                        </td>
                        <td>{{ $product->pack_label ?: '-' }}</td>
                        <td>
                            @if($product->expiry_date)
                                @if($product->expiry_status === 'expired')
                                    <span class="badge text-bg-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $product->expiry_date->format('d-M-Y') }}</span>
                                @elseif($product->expiry_status === 'expiring_soon')
                                    <span class="badge text-bg-warning"><i class="bi bi-clock-history me-1"></i>{{ $product->expiry_date->format('d-M-Y') }}</span>
                                @else
                                    <span class="text-success small"><i class="bi bi-calendar-check me-1"></i>{{ $product->expiry_date->format('d-M-Y') }}</span>
                                @endif
                            @else
                                <span class="text-muted small">No Expiry</span>
                            @endif
                        </td>
                        @if(auth()->user()->isSuperAdmin() || auth()->user()->isShopAdmin())
                            <td class="fw-semibold">
                                {{ $product->purchase_price !== null ? 'Rs. ' . number_format($product->purchase_price, 2) : '-' }}
                            </td>
                        @endif
                        <td class="fw-semibold text-primary">
                            {{ $product->selling_price !== null ? 'Rs. ' . number_format($product->selling_price, 2) : '-' }}
                        </td>
                        <td>
                            <span class="badge text-bg-{{ $product->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($product->status) }}
                            </span>
                        </td>
                        <td>
                            @if($canManage)
                                <div class="d-flex gap-1">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('products.edit', $product) }}">Edit</a>
                                    <form method="post" action="{{ route('products.destroy', $product) }}" data-confirm="Are you sure you want to delete this product?">
                                        @csrf
                                        @method('delete')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            @else
                                <span class="text-muted small">View Only</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isSuperAdmin() ? 9 : 8 }}" class="text-center py-4">No products found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $products->onEachSide(1)->links() }}</div>
@endsection
