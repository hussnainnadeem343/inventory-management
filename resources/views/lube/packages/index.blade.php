@extends('layouts.app')
@section('title', 'Service Packages')

@section('content')
<div class="container-fluid px-0">
    {{-- HEADER --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1"><i class="bi bi-boxes text-primary me-2"></i>Service Packages &amp; Combos</h1>
            <p class="text-muted small mb-0">Pre-configured oil, filter, and labor bundles for 1-click counter checkout.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#createPackageModal">
                <i class="bi bi-plus-lg me-1"></i> New Service Package
            </button>
        </div>
    </div>

    {{-- SEARCH & FILTER BAR --}}
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('lube.packages.index') }}" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search package name..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="vehicle_type" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- All Vehicle Types --</option>
                        <option value="sedan" @selected(request('vehicle_type') === 'sedan')>Cars / Sedans / Hatchbacks</option>
                        <option value="suv" @selected(request('vehicle_type') === 'suv')>SUVs / Crossovers</option>
                        <option value="commercial" @selected(request('vehicle_type') === 'commercial')>Commercial / Vans</option>
                        <option value="bike" @selected(request('vehicle_type') === 'bike')>Bikes</option>
                    </select>
                </div>
                <div class="col-md-3 text-md-end">
                    <button type="submit" class="btn btn-sm btn-primary px-3">Filter</button>
                    <a href="{{ route('lube.packages.index') }}" class="btn btn-sm btn-light border">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- PACKAGES TABLE --}}
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary small">
                    <tr>
                        <th>Package Name</th>
                        <th>Vehicle Type</th>
                        <th>Included Items / Consumables</th>
                        <th class="text-end">Labor Charge</th>
                        <th class="text-end">Package Price</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($packages as $pkg)
                        <tr>
                            <td>
                                <strong class="d-block text-dark">{{ $pkg->name }}</strong>
                                @if($pkg->description)
                                    <small class="text-muted">{{ $pkg->description }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border text-uppercase">{{ $pkg->vehicle_type ?? 'All' }}</span>
                            </td>
                            <td>
                                @if($pkg->items->count() > 0)
                                    <ul class="list-unstyled mb-0 small">
                                        @foreach($pkg->items as $it)
                                            <li>
                                                <i class="bi bi-check2 text-success me-1"></i>
                                                {{ $it->inventoryItem->item_name ?? 'Item' }} 
                                                <span class="text-muted">({{ number_format($it->quantity, 2) }} {{ $it->inventoryItem->unit ?? 'PCS' }})</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="text-muted small">No items linked</span>
                                @endif
                            </td>
                            <td class="text-end font-monospace">Rs. {{ number_format($pkg->labor_amount, 2) }}</td>
                            <td class="text-end font-monospace fw-bold text-success fs-6">Rs. {{ number_format($pkg->package_price, 2) }}</td>
                            <td class="text-center">
                                @if($pkg->is_active)
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('lube.packages.destroy', $pkg) }}" class="d-inline" onsubmit="return confirm('Delete this package?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0.5 px-2">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No service packages created yet. Click <strong>New Service Package</strong> above to create one.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($packages->hasPages())
            <div class="card-footer bg-white border-top py-2.5">
                {{ $packages->links() }}
            </div>
        @endif
    </div>
</div>

{{-- MODAL: CREATE PACKAGE --}}
<div class="modal fade" id="createPackageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title h6"><i class="bi bi-boxes text-primary me-2"></i>Create New Service Package</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('lube.packages.store') }}" id="createPkgForm">
                @csrf
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label small fw-semibold">Package Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Sedan 5,000KM Premium Oil & Filter" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Target Vehicle Type</label>
                            <select name="vehicle_type" class="form-select form-select-sm">
                                <option value="sedan">Cars / Sedans / Hatchbacks</option>
                                <option value="suv">SUVs / Crossovers</option>
                                <option value="commercial">Commercial / Vans</option>
                                <option value="bike">Bikes / Motorcycles</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Total Package Price (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" name="package_price" class="form-control form-control-sm font-monospace fw-bold text-success" placeholder="e.g. 8500" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Labor Amount Included (Rs.)</label>
                            <input type="number" name="labor_amount" class="form-control form-control-sm font-monospace" placeholder="e.g. 500" min="0" value="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Description / Notes</label>
                            <textarea name="description" class="form-control form-control-sm" rows="2" placeholder="e.g. Includes 4L synthetic 5W-30, genuine oil filter, and 7-point pit inspection."></textarea>
                        </div>
                    </div>

                    <h6 class="h6 border-top pt-3 text-secondary mb-2">Package Items &amp; Consumables</h6>
                    <div id="pkgItemsContainer" class="d-flex flex-column gap-2 mb-2">
                        <div class="row g-2 align-items-center pkg-item-row">
                            <div class="col-8">
                                <select name="items[0][inventory_item_id]" class="form-select form-select-sm" required>
                                    <option value="">-- Choose Consumable / Oil / Filter --</option>
                                    @foreach($products as $prod)
                                        <option value="{{ $prod->id }}">{{ $prod->item_name }} (Stock: {{ $prod->quantity }} {{ $prod->unit }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-3">
                                <input type="number" name="items[0][quantity]" class="form-control form-control-sm text-center" placeholder="Qty" value="1.00" step="any" min="0" required>
                            </div>
                            <div class="col-1 text-center">
                                <button type="button" class="btn btn-link text-danger p-0" onclick="this.closest('.pkg-item-row').remove();">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddPkgItem">
                        <i class="bi bi-plus-circle me-1"></i> Add Another Item
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold">Save Package</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let itemIdx = 1;
    document.getElementById('btnAddPkgItem').addEventListener('click', function() {
        const container = document.getElementById('pkgItemsContainer');
        const row = document.createElement('div');
        row.className = 'row g-2 align-items-center pkg-item-row';
        row.innerHTML = `
            <div class="col-8">
                <select name="items[${itemIdx}][inventory_item_id]" class="form-select form-select-sm" required>
                    <option value="">-- Choose Consumable / Oil / Filter --</option>
                    @foreach($products as $prod)
                        <option value="{{ $prod->id }}">{{ $prod->item_name }} (Stock: {{ $prod->quantity }} {{ $prod->unit }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-3">
                <input type="number" name="items[${itemIdx}][quantity]" class="form-control form-control-sm text-center" placeholder="Qty" value="1.00" step="any" min="0" required>
            </div>
            <div class="col-1 text-center">
                <button type="button" class="btn btn-link text-danger p-0" onclick="this.closest('.pkg-item-row').remove();">
                    <i class="bi bi-trash"></i>
                </button>
            </div>`;
        container.appendChild(row);
        itemIdx++;
    });
});
</script>
@endpush
@endsection
