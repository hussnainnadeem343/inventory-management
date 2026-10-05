@extends('layouts.app')
@section('title', 'Units of Measure')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 mb-1">Units of Measure (UOM)</h1>
        <small class="text-secondary">Standard units for products, stock packaging, and billing (e.g. PCS, KG, BOX, LTR)</small>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUnitModal">
        <i class="bi bi-plus-lg me-1"></i> Add New Unit
    </button>
</div>

<x-errors />

<div class="card p-3 mb-3">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-md-{{ auth()->user()->isSuperAdmin() ? '5' : '8' }}">
            <label class="form-label">Search</label>
            <input class="form-control" name="search" value="{{ $search }}" placeholder="Search by unit name, code or description...">
        </div>
        @if(auth()->user()->isSuperAdmin())
            <div class="col-md-3">
                <label class="form-label">Shop</label>
                <select class="form-select" name="shop_id">
                    <option value="">All / Global Units</option>
                    @foreach($shops as $shop)
                        <option value="{{ $shop->id }}" @selected(request('shop_id') == $shop->id)>{{ $shop->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="col-md-4 d-flex gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i> Filter</button>
            <a class="btn btn-outline-secondary" href="{{ route('units.index') }}">Reset</a>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 70px;">#</th>
                    <th>Unit Name</th>
                    <th>Short Code</th>
                    <th>Description</th>
                    <th>Scope</th>
                    <th>Status</th>
                    <th style="width: 120px;" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($units as $unit)
                    <tr>
                        <td>{{ $unit->id }}</td>
                        <td>
                            <strong class="text-dark">{{ $unit->name }}</strong>
                        </td>
                        <td>
                            <span class="badge text-bg-primary font-monospace fs-6 px-2 py-1">{{ $unit->code }}</span>
                        </td>
                        <td class="text-secondary small">{{ $unit->description ?? '—' }}</td>
                        <td>
                            @if($unit->shop_id)
                                <span class="badge text-bg-info text-dark">Shop Custom</span>
                            @else
                                <span class="badge text-bg-secondary">Global System</span>
                            @endif
                        </td>
                        <td>
                            @if($unit->status === 'active')
                                <span class="badge text-bg-success">Active</span>
                            @else
                                <span class="badge text-bg-danger">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editUnitModal{{ $unit->id }}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="post" action="{{ route('units.destroy', $unit) }}" class="d-inline" onsubmit="return confirm('Delete this unit?');">
                                @csrf
                                @method('delete')
                                <button class="btn btn-sm btn-outline-danger" type="submit">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editUnitModal{{ $unit->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="post" action="{{ route('units.update', $unit) }}">
                                    @csrf
                                    @method('put')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Unit: {{ $unit->name }} ({{ $unit->code }})</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body row g-3">
                                        <div class="col-md-7">
                                            <label class="form-label">Unit Full Name <span class="text-danger">*</span></label>
                                            <input class="form-control" name="name" value="{{ old('name', $unit->name) }}" required>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label">Short Code <span class="text-danger">*</span></label>
                                            <input class="form-control font-monospace text-uppercase" name="code" value="{{ old('code', $unit->code) }}" required maxlength="20">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Description (Optional)</label>
                                            <input class="form-control" name="description" value="{{ old('description', $unit->description) }}">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Status <span class="text-danger">*</span></label>
                                            <select class="form-select" name="status" required>
                                                <option value="active" @selected($unit->status === 'active')>Active</option>
                                                <option value="inactive" @selected($unit->status === 'inactive')>Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-secondary">
                            <i class="bi bi-rulers fs-3 d-block mb-1"></i>
                            No units of measure found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($units->hasPages())
        <div class="p-3 border-top">
            {{ $units->links() }}
        </div>
    @endif
</div>

<!-- Create Modal -->
<div class="modal fade" id="createUnitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="{{ route('units.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add New Unit of Measure</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-md-7">
                        <label class="form-label">Unit Full Name <span class="text-danger">*</span></label>
                        <input class="form-control" name="name" required placeholder="e.g. Kilogram, Litre, Carton">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Short Code <span class="text-danger">*</span></label>
                        <input class="form-control font-monospace text-uppercase" name="code" required maxlength="20" placeholder="e.g. KG, LTR, CTN">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description (Optional)</label>
                        <input class="form-control" name="description" placeholder="e.g. Metric weight measurement">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Unit</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
