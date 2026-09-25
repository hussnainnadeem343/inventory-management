@extends('layouts.app')
@section('title', 'Shops Management')
@section('content')
<div class="card p-3 mb-3">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-lg-6 col-md-6">
            <label class="form-label">Search Shops</label>
            <input class="form-control" name="search" value="{{ $search }}" placeholder="Shop name, code, or business type">
        </div>
        <div class="col-sm-6 col-lg-3 col-md-3">
            <label class="form-label">Per page</label>
            <select class="form-select" name="per_page">
                @foreach([10, 20, 30, 50, 100] as $size)
                    <option @selected($perPage === $size)>{{ $size }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-3 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Search</button>
            <a class="btn btn-outline-secondary" href="{{ route('shops.index') }}">Reset</a>
        </div>
    </form>
</div>

<div class="text-end mb-3">
    <a class="btn btn-primary" href="{{ route('shops.create') }}"><i class="bi bi-shop me-1"></i>+ Add New Shop</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Shop Name / Code</th>
                    <th>Business Type</th>
                    <th>Phone</th>
                    <th>Users</th>
                    <th>Inventory Items</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shops as $shop)
                    <tr>
                        <td>
                            <strong>{{ $shop->name }}</strong><br>
                            <span class="badge text-bg-light border font-monospace">{{ $shop->code }}</span>
                        </td>
                        <td>{{ $shop->business_type ?: 'General' }}</td>
                        <td>{{ $shop->phone ?: '-' }}</td>
                        <td><span class="badge text-bg-info text-white">{{ $shop->users_count }} Users</span></td>
                        <td><span class="badge text-bg-secondary">{{ $shop->inventory_items_count }} Items</span></td>
                        <td>
                            <span class="badge text-bg-{{ $shop->status === 'active' ? 'success' : 'danger' }}">
                                {{ ucfirst($shop->status) }}
                            </span>
                        </td>
                        <td>{{ $shop->created_at->format('d-M-Y') }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('shops.edit', $shop) }}">Edit</a>
                                <form method="post" action="{{ route('shops.destroy', $shop) }}" data-confirm="Are you sure you want to deactivate/delete this shop?">
                                    @csrf
                                    @method('delete')
                                    <button class="btn btn-sm btn-outline-danger">Deactivate</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">No shops found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $shops->onEachSide(1)->links() }}</div>
@endsection
