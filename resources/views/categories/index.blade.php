@extends('layouts.app')
@section('title', 'Categories')
@section('content')
<div class="card p-3 mb-3">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-lg-{{ auth()->user()->isSuperAdmin() ? '3' : '5' }} col-md-6">
            <label class="form-label">Search</label>
            <input class="form-control" name="search" value="{{ $filters['search'] }}" placeholder="Category name or description">
        </div>
        @if(auth()->user()->isSuperAdmin())
            <div class="col-sm-6 col-lg-3 col-md-3">
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
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
                <option value="">All Statuses</option>
                <option value="active" @selected($filters['status'] === 'active')>Active</option>
                <option value="inactive" @selected($filters['status'] === 'inactive')>Inactive</option>
            </select>
        </div>
        <div class="col-sm-6 col-lg-2 col-md-3">
            <label class="form-label">Per page</label>
            <select class="form-select" name="per_page">
                @foreach([10, 20, 30, 50, 100] as $size)
                    <option @selected($perPage === $size)>{{ $size }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Search</button>
            <a class="btn btn-outline-secondary" href="{{ route('categories.index') }}">Reset</a>
        </div>
    </form>
</div>
<div class="text-end mb-3">
    <a class="btn btn-primary" href="{{ route('categories.create') }}">+ Add Category</a>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Category Name</th>
                    @if(auth()->user()->isSuperAdmin())
                        <th>Shop</th>
                    @endif
                    <th>Description</th>
                    <th>Status</th>
                    <th>Created By</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                    @php
                        $canManage = auth()->user()->isSuperAdmin() || (auth()->user()->isShopAdmin() && auth()->user()->shop_id === $category->shop_id);
                    @endphp
                    <tr>
                        <td>{{ $category->id }}</td>
                        <td><strong>{{ $category->name }}</strong></td>
                        @if(auth()->user()->isSuperAdmin())
                            <td><span class="badge text-bg-light border">{{ $category->shop->name ?? 'Default' }}</span></td>
                        @endif
                        <td>{{ $category->description ?: '-' }}</td>
                        <td><span class="badge text-bg-{{ $category->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($category->status) }}</span></td>
                        <td>{{ $category->creator->name }}</td>
                        <td>{{ $category->created_at->format('d-M-Y h:i A') }}</td>
                        <td>
                            @if($canManage)
                                <div class="d-flex gap-1">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('categories.edit', $category) }}">Edit</a>
                                    <form method="post" action="{{ route('categories.destroy', $category) }}" data-confirm="Are you sure you want to delete this category?">
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
                        <td colspan="{{ auth()->user()->isSuperAdmin() ? 8 : 7 }}" class="text-center py-4">No categories found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $categories->onEachSide(1)->links() }}</div>
@endsection
