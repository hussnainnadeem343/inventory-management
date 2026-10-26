@extends('layouts.app')
@section('title', 'Labor & Pit Services')

@section('content')
<div class="container-fluid px-0">
    {{-- HEADER --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1"><i class="bi bi-wrench-adjustable text-primary me-2"></i>Labor &amp; Pit Services Catalog</h1>
            <p class="text-muted small mb-0">Configure labor fees and pit services available at the service counter.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#createServiceModal">
                <i class="bi bi-plus-lg me-1"></i> Add New Labor Service
            </button>
            <a href="{{ route('lube.pos') }}" class="btn btn-outline-secondary">
                <i class="bi bi-cart3 me-1"></i> Open POS
            </a>
        </div>
    </div>

    {{-- SEARCH & FILTER BAR --}}
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('lube.services.index') }}" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search service name, code, or description..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- All Categories --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 text-md-end">
                    <button type="submit" class="btn btn-sm btn-primary px-3">Filter</button>
                    <a href="{{ route('lube.services.index') }}" class="btn btn-sm btn-light border">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- SERVICES TABLE --}}
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary small">
                    <tr>
                        <th>Service Name</th>
                        <th>Category</th>
                        <th>Service Code</th>
                        <th class="text-end">Standard Charge</th>
                        <th class="text-center">Status</th>
                        <th>Description</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($services as $srv)
                        <tr>
                            <td>
                                <strong class="d-block text-dark">{{ $srv->name }}</strong>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $srv->category }}</span>
                            </td>
                            <td>
                                <span class="font-monospace text-muted small">{{ $srv->code ?? '-' }}</span>
                            </td>
                            <td class="text-end font-monospace fw-bold text-success fs-6">
                                Rs. {{ number_format($srv->price, 2) }}
                            </td>
                            <td class="text-center">
                                @if($srv->is_active)
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="small text-muted">
                                {{ $srv->description ?? '-' }}
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1.5">
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0.5 px-2" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editServiceModal{{ $srv->id }}" 
                                            title="Edit Service">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="{{ route('lube.services.destroy', $srv) }}" class="d-inline" onsubmit="return confirm('Delete this labor service?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger py-0.5 px-2" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>

                                {{-- EDIT MODAL --}}
                                <div class="modal fade text-start" id="editServiceModal{{ $srv->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title h6">Edit Service - {{ $srv->name }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form method="POST" action="{{ route('lube.services.update', $srv) }}">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Service Name <span class="text-danger">*</span></label>
                                                        <input type="text" name="name" class="form-control form-control-sm" value="{{ $srv->name }}" required>
                                                    </div>
                                                    <div class="row g-2 mb-3">
                                                        <div class="col-6">
                                                            <label class="form-label small fw-semibold">Category <span class="text-danger">*</span></label>
                                                            <input type="text" name="category" class="form-control form-control-sm" value="{{ $srv->category }}" list="serviceCategoryList" required>
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label small fw-semibold">Service Code</label>
                                                            <input type="text" name="code" class="form-control form-control-sm font-monospace" value="{{ $srv->code }}">
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Standard Charge (Rs.) <span class="text-danger">*</span></label>
                                                        <input type="number" name="price" class="form-control form-control-sm font-monospace" value="{{ $srv->price }}" min="0" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Description</label>
                                                        <textarea name="description" class="form-control form-control-sm" rows="2">{{ $srv->description }}</textarea>
                                                    </div>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="is_active" id="activeSw{{ $srv->id }}" value="1" @checked($srv->is_active)>
                                                        <label class="form-check-label small fw-semibold" for="activeSw{{ $srv->id }}">Active on POS Counter</label>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary btn-sm fw-semibold">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No labor services configured yet. Click <strong>Add New Labor Service</strong> above.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($services->hasPages())
            <div class="card-footer bg-white border-top py-2.5">
                {{ $services->links() }}
            </div>
        @endif
    </div>
</div>

{{-- MODAL: CREATE SERVICE --}}
<div class="modal fade" id="createServiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title h6"><i class="bi bi-wrench-adjustable text-primary me-2"></i>Add New Labor Service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('lube.services.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Service Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Engine Oil Drain &amp; Replace Labor" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Category <span class="text-danger">*</span></label>
                            <input type="text" name="category" class="form-control form-control-sm" placeholder="Pick or type category" list="serviceCategoryList" required>
                            <datalist id="serviceCategoryList">
                                <option value="Oil &amp; Filter Service">
                                <option value="Engine Tuning &amp; Throttle">
                                <option value="Cooling System &amp; AC">
                                <option value="Brakes &amp; Tires">
                                <option value="Transmission &amp; Gearbox">
                                <option value="Pit Inspection">
                            </datalist>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Service Code</label>
                            <input type="text" name="code" class="form-control form-control-sm font-monospace" placeholder="e.g. SRV-OIL">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Standard Charge (Rs.) <span class="text-danger">*</span></label>
                        <input type="number" name="price" class="form-control form-control-sm font-monospace" placeholder="e.g. 500" min="0" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Description</label>
                        <textarea name="description" class="form-control form-control-sm" rows="2" placeholder="e.g. Labor charge when customer provides own oil or custom request"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold">Save Service</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
