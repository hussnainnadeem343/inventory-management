@extends('layouts.app')
@section('title', 'Pit Technicians')

@section('content')
<div class="container-fluid px-0">
    {{-- HEADER --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1"><i class="bi bi-people text-primary me-2"></i>Pit Technicians &amp; Bay Crew</h1>
            <p class="text-muted small mb-0">Manage service bay mechanics, commission schemes, and performance logs.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#createTechModal">
                <i class="bi bi-person-plus me-1"></i> Register Technician
            </button>
            <a href="{{ route('lube.commissions.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-wallet2 me-1"></i> Commission Payouts
            </a>
        </div>
    </div>

    {{-- SEARCH & FILTER BAR --}}
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('lube.technicians.index') }}" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search mechanic name, phone, or bay..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- All Statuses --</option>
                        <option value="active" @selected(request('status') === 'active')>Active Crew</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3 text-md-end">
                    <button type="submit" class="btn btn-sm btn-primary px-3">Filter</button>
                    <a href="{{ route('lube.technicians.index') }}" class="btn btn-sm btn-light border">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- TECHNICIANS TABLE --}}
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary small">
                    <tr>
                        <th>Mechanic Name</th>
                        <th>Bay Assigned</th>
                        <th>Contact Phone</th>
                        <th>Commission Scheme</th>
                        <th class="text-center">Jobs Completed</th>
                        <th class="text-end">Total Commission</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($technicians as $tech)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2.5">
                                    <span class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center" style="width: 34px; height: 34px; font-size: 13px;">
                                        {{ substr($tech->name, 0, 1) }}
                                    </span>
                                    <div>
                                        <strong class="d-block text-dark">{{ $tech->name }}</strong>
                                        <small class="text-muted" style="font-size: 11px;">ID: #{{ $tech->id }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace">{{ $tech->bay_number ? 'Bay ' . $tech->bay_number : 'Pit Bay' }}</span>
                            </td>
                            <td class="font-monospace small">
                                {{ $tech->phone ?? '-' }}
                            </td>
                            <td>
                                @if($tech->commission_type === 'fixed')
                                    <span class="badge bg-info-subtle text-info font-monospace">Rs. {{ number_format($tech->commission_rate, 2) }} / Job</span>
                                @elseif($tech->commission_type === 'percentage')
                                    <span class="badge bg-warning-subtle text-warning-emphasis font-monospace">{{ number_format($tech->commission_rate, 1) }}% of Labor</span>
                                @else
                                    <span class="badge bg-light text-secondary border">No Commission</span>
                                @endif
                            </td>
                            <td class="text-center font-monospace fw-semibold">
                                {{ number_format($tech->service_records_count) }}
                            </td>
                            <td class="text-end font-monospace fw-bold text-success">
                                Rs. {{ number_format($tech->service_records_sum_technician_commission ?? 0, 2) }}
                            </td>
                            <td class="text-center">
                                @if($tech->status === 'active')
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1.5">
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0.5 px-2" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editTechModal{{ $tech->id }}" 
                                            title="Edit Profile">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="{{ route('lube.technicians.destroy', $tech) }}" class="d-inline" onsubmit="return confirm('Remove this technician?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger py-0.5 px-2" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>

                                {{-- EDIT MODAL --}}
                                <div class="modal fade text-start" id="editTechModal{{ $tech->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title h6">Edit Technician - {{ $tech->name }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form method="POST" action="{{ route('lube.technicians.update', $tech) }}">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Mechanic Name <span class="text-danger">*</span></label>
                                                        <input type="text" name="name" class="form-control form-control-sm" value="{{ $tech->name }}" required>
                                                    </div>
                                                    <div class="row g-2 mb-3">
                                                        <div class="col-6">
                                                            <label class="form-label small fw-semibold">Phone Number</label>
                                                            <input type="text" name="phone" class="form-control form-control-sm" value="{{ $tech->phone }}">
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label small fw-semibold">Bay / Pit Assigned</label>
                                                            <input type="text" name="bay_number" class="form-control form-control-sm" value="{{ $tech->bay_number }}">
                                                        </div>
                                                    </div>
                                                    <div class="row g-2 mb-3">
                                                        <div class="col-6">
                                                            <label class="form-label small fw-semibold">Commission Type</label>
                                                            <select name="commission_type" class="form-select form-select-sm">
                                                                <option value="fixed" @selected($tech->commission_type === 'fixed')>Fixed Rs per Job</option>
                                                                <option value="percentage" @selected($tech->commission_type === 'percentage')>% of Labor Fee</option>
                                                                <option value="none" @selected($tech->commission_type === 'none')>No Commission</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label small fw-semibold">Commission Rate</label>
                                                            <input type="number" name="commission_rate" class="form-control form-control-sm" value="{{ $tech->commission_rate }}" min="0">
                                                        </div>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label small fw-semibold">Status</label>
                                                        <select name="status" class="form-select form-select-sm">
                                                            <option value="active" @selected($tech->status === 'active')>Active</option>
                                                            <option value="inactive" @selected($tech->status === 'inactive')>Inactive</option>
                                                        </select>
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
                            <td colspan="8" class="text-center text-muted py-4">No pit technicians registered yet. Click <strong>Register Technician</strong> above.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($technicians->hasPages())
            <div class="card-footer bg-white border-top py-2.5">
                {{ $technicians->links() }}
            </div>
        @endif
    </div>
</div>

{{-- MODAL: CREATE TECHNICIAN --}}
<div class="modal fade" id="createTechModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title h6"><i class="bi bi-person-plus text-primary me-2"></i>Register New Pit Technician</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('lube.technicians.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Mechanic Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Muhammad Aslam" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Phone Number</label>
                            <input type="text" name="phone" class="form-control form-control-sm" placeholder="0300-1234567">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Bay / Pit Assigned</label>
                            <input type="text" name="bay_number" class="form-control form-control-sm" placeholder="e.g. Pit 1">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Commission Type</label>
                            <select name="commission_type" class="form-select form-select-sm">
                                <option value="fixed" selected>Fixed Rs per Job</option>
                                <option value="percentage">% of Labor Fee</option>
                                <option value="none">No Commission</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Commission Rate</label>
                            <input type="number" name="commission_rate" class="form-control form-control-sm font-monospace" value="100" min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold">Register Technician</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
