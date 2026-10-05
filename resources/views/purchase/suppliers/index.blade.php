@extends('layouts.app')
@section('title', 'Suppliers & Vendors')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Suppliers & Vendors</h4>
        <small class="text-secondary">Manage vendors, contact details, and payables</small>
    </div>
    @if(auth()->user()->hasPermission('purchases.create'))
        <a class="btn btn-primary" href="{{ route('purchases.suppliers.create') }}">
            <i class="bi bi-plus-lg me-1"></i> Add Supplier
        </a>
    @endif
</div>

<div class="card p-3 mb-3">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-lg-5 col-md-6">
            <label class="form-label">Search</label>
            <input class="form-control" name="search" value="{{ $search }}" placeholder="Supplier name, company, phone, email...">
        </div>
        <div class="col-sm-6 col-lg-3 col-md-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
                <option value="">All Statuses</option>
                <option value="active" @selected($status === 'active')>Active</option>
                <option value="inactive" @selected($status === 'inactive')>Inactive</option>
            </select>
        </div>
        <div class="col-lg-4 d-flex gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i> Search</button>
            <a class="btn btn-outline-secondary" href="{{ route('purchases.suppliers.index') }}">Reset</a>
        </div>
    </form>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Supplier Name</th>
                    <th>Company</th>
                    <th>Phone / Email</th>
                    <th>Current Balance</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($suppliers as $supplier)
                    <tr>
                        <td>{{ $supplier->id }}</td>
                        <td>
                            <a href="{{ route('purchases.suppliers.show', $supplier) }}" class="fw-semibold text-decoration-none">
                                {{ $supplier->name }}
                            </a>
                            @if($supplier->shop)
                                <div><small class="text-muted"><i class="bi bi-shop me-1"></i>{{ $supplier->shop->name }}</small></div>
                            @endif
                        </td>
                        <td>{{ $supplier->company_name ?? '—' }}</td>
                        <td>
                            <div>{{ $supplier->phone ?? '—' }}</div>
                            @if($supplier->email)
                                <small class="text-secondary">{{ $supplier->email }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="fw-bold {{ $supplier->current_balance > 0 ? 'text-danger' : 'text-success' }}">
                                Rs. {{ number_format($supplier->current_balance, 2) }}
                            </span>
                            @if($supplier->current_balance > 0)
                                <small class="badge bg-danger-subtle text-danger ms-1">Payable</small>
                            @endif
                        </td>
                        <td>
                            @if($supplier->status === 'active')
                                <span class="badge bg-success-subtle text-success">Active</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a class="btn btn-outline-secondary" href="{{ route('purchases.suppliers.show', $supplier) }}" title="View Ledger">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if(auth()->user()->hasPermission('purchases.create'))
                                    <a class="btn btn-outline-primary" href="{{ route('purchases.suppliers.edit', $supplier) }}" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endif
                                @if(auth()->user()->hasPermission('purchases.delete'))
                                    <form method="post" action="{{ route('purchases.suppliers.destroy', $supplier) }}" class="d-inline" data-confirm="Are you sure you want to delete this supplier?">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger" type="submit" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-secondary">
                            <i class="bi bi-inbox fs-2 d-block mb-1"></i>
                            No suppliers found. Click "Add Supplier" to get started.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($suppliers->hasPages())
        <div class="p-3 border-top">
            {{ $suppliers->links() }}
        </div>
    @endif
</div>
@endsection
