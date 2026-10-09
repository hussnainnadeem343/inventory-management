@extends('layouts.app')
@section('title', 'Inward Gate Passes')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Inward Gate Passes (IGP)</h4>
        <small class="text-secondary">Stage 3: Gate security entry & physical arrival logging</small>
    </div>
    @if(auth()->user()->hasPermission('purchases.igp') || auth()->user()->isShopAdmin() || auth()->user()->isSuperAdmin())
        <a class="btn btn-primary" href="{{ route('purchases.igp.create') }}">
            <i class="bi bi-plus-lg me-1"></i> New Gate Pass
        </a>
    @endif
</div>

<div class="card shadow-sm border-0 mb-3 bg-white">
    <div class="card-body py-2 px-3">
        <form method="get" action="{{ route('purchases.igp.index') }}" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0 text-secondary"><i class="bi bi-search"></i></span>
                    <input type="search" name="search" class="form-control border-start-0" placeholder="Search IGP #, vehicle, bilty, challan, supplier..." value="{{ $search }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="pending_inspection" @selected($status === 'pending_inspection')>Pending Inspection</option>
                    <option value="grn_completed" @selected($status === 'grn_completed')>GRN Completed</option>
                    <option value="cancelled" @selected($status === 'cancelled')>Cancelled</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
                @if($search || $status)
                    <a href="{{ route('purchases.igp.index') }}" class="btn btn-sm btn-link text-decoration-none text-muted">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="small text-secondary bg-light">
                <tr>
                    <th class="ps-3">IGP Number</th>
                    <th>Date & Time</th>
                    <th>Supplier / Vendor</th>
                    <th>Vehicle #</th>
                    <th>Bilty / Tracking #</th>
                    <th>Challan #</th>
                    <th>Status</th>
                    <th class="text-end pe-3">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($passes as $pass)
                    <tr>
                        <td class="ps-3">
                            <a href="{{ route('purchases.igp.show', $pass) }}" class="fw-bold text-decoration-none">
                                {{ $pass->igp_number }}
                            </a>
                            @if($pass->purchaseOrder)
                                <div class="small text-muted">PO: {{ $pass->purchaseOrder->po_number }}</div>
                            @endif
                        </td>
                        <td>
                            <div>{{ $pass->igp_date->format('d M, Y') }}</div>
                            <small class="text-muted">{{ $pass->gate_entry_time ?? '' }}</small>
                        </td>
                        <td class="fw-semibold">{{ $pass->supplier->name }}</td>
                        <td>
                            @if($pass->vehicle_number)
                                <span class="badge bg-light text-dark border font-monospace">{{ $pass->vehicle_number }}</span>
                            @else
                                <span class="text-muted">{{ $pass->received_via }}</span>
                            @endif
                        </td>
                        <td>{{ $pass->bilty_number ?? '-' }}</td>
                        <td>{{ $pass->challan_number ?? '-' }}</td>
                        <td>
                            @if($pass->status === 'pending_inspection')
                                <span class="badge bg-warning-subtle text-warning-emphasis">Pending Inspection</span>
                            @elseif($pass->status === 'grn_completed')
                                <span class="badge bg-success-subtle text-success">GRN Completed</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($pass->status) }}</span>
                            @endif
                        </td>
                        <td class="text-end pe-3">
                            <div class="btn-group btn-group-sm">
                                <a class="btn btn-outline-secondary" href="{{ route('purchases.igp.show', $pass) }}">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if($pass->status === 'pending_inspection')
                                    <a class="btn btn-primary" href="{{ route('purchases.grn.create', ['inward_gate_pass_id' => $pass->id]) }}" title="Generate GRN from this Gate Pass">
                                        <i class="bi bi-box-arrow-in-down me-1"></i> Receive GRN
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-truck fs-3 d-block mb-2"></i>
                            No Inward Gate Passes recorded yet. Click <strong>New Gate Pass</strong> to log arrival.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($passes->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $passes->links() }}
        </div>
    @endif
</div>
@endsection
