@extends('layouts.app')
@section('title', 'Brands')
@section('content')
<div class="card p-3 mb-3">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-lg-5 col-md-6">
            <label class="form-label">Search</label>
            <input class="form-control" name="search" value="{{ $filters['search'] }}" placeholder="Brand name or description">
        </div>
        <div class="col-sm-6 col-lg-3 col-md-3">
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
            <a class="btn btn-outline-secondary" href="{{ route('brands.index') }}">Reset</a>
        </div>
    </form>
</div>
<div class="text-end mb-3">
    <a class="btn btn-primary" href="{{ route('brands.create') }}">+ Add Brand</a>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Brand Name</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Created By</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($brands as $brand)
                    <tr>
                        <td>{{ $brand->id }}</td>
                        <td>{{ $brand->name }}</td>
                        <td>{{ $brand->description ?: '-' }}</td>
                        <td><span class="badge text-bg-{{ $brand->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($brand->status) }}</span></td>
                        <td>{{ $brand->creator->name }}</td>
                        <td>{{ $brand->created_at->format('d-M-Y h:i A') }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('brands.edit', $brand) }}">Edit</a>
                                @if(auth()->user()->isSuperAdmin())
                                    <form method="post" action="{{ route('brands.destroy', $brand) }}" data-confirm="Are you sure you want to delete this brand?">
                                        @csrf
                                        @method('delete')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">No brands found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $brands->onEachSide(1)->links() }}</div>
@endsection
