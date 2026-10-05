@extends('layouts.app')
@section('title', 'Warehouses & Godowns')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Warehouses & Godowns</h4>
        <small class="text-secondary">Physical stock storage facilities and distribution locations</small>
    </div>
    <a class="btn btn-primary" href="{{ route('warehouses.create') }}">
        <i class="bi bi-plus-lg me-1"></i> Add Warehouse
    </a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Code</th>
                    <th>Warehouse Name</th>
                    <th>Contact Person</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($warehouses as $wh)
                    <tr>
                        <td><span class="badge bg-light text-dark border font-monospace">{{ $wh->code }}</span></td>
                        <td>
                            <span class="fw-semibold">{{ $wh->name }}</span>
                            @if($wh->is_default)
                                <span class="badge bg-primary-subtle text-primary ms-1">Default Godown</span>
                            @endif
                        </td>
                        <td>{{ $wh->contact_person ?? '—' }}</td>
                        <td>{{ $wh->phone ?? '—' }}</td>
                        <td><small class="text-muted">{{ $wh->address ?? '—' }}</small></td>
                        <td>
                            @if($wh->status === 'active')
                                <span class="badge bg-success-subtle text-success">Active</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a class="btn btn-outline-primary" href="{{ route('warehouses.edit', $wh) }}" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @if(!$wh->is_default)
                                    <form method="post" action="{{ route('warehouses.destroy', $wh) }}" class="d-inline" data-confirm="Are you sure you want to delete this warehouse?">
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
                        <td colspan="7" class="text-center text-muted py-4">No warehouses found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($warehouses->hasPages())
        <div class="p-3 border-top">
            {{ $warehouses->links() }}
        </div>
    @endif
</div>
@endsection
