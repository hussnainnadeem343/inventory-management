@extends('layouts.app')
@section('title', 'Receive Via - Transport Modes')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Receive Via Modes</h4>
        <!-- <small class="text-secondary">Manage receiving channels, transport types, and logistics modes for GRN & IGP</small> -->
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModeModal">
        <i class="bi bi-plus-lg me-1"></i> Add Transport Mode
    </button>
</div>

<div class="card shadow-sm border-0 mb-3 bg-white">
    <div class="card-body py-2 px-3">
        <form method="get" action="{{ route('purchases.transport_modes.index') }}" class="row g-2 align-items-center">
            <div class="col-md-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0 text-secondary"><i class="bi bi-search"></i></span>
                    <input type="search" name="search" class="form-control border-start-0" placeholder="Search transport mode name or description..." value="{{ $search }}">
                </div>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
                @if($search)
                    <a href="{{ route('purchases.transport_modes.index') }}" class="btn btn-sm btn-link text-decoration-none text-muted">Clear</a>
                @endif
            </div>
            <div class="col-auto ms-auto">
                <x-per-page-select :paginator="$modes" />
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="small text-secondary bg-light">
                <tr>
                    <th class="ps-3" style="width: 25%;">Mode Name</th>
                    <th style="width: 45%;">Description / Logistics Note</th>
                    <th style="width: 15%;">Status</th>
                    <th class="text-end pe-3" style="width: 15%;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($modes as $mode)
                    <tr>
                        <td class="ps-3">
                            <span class="fw-bold text-dark"><i class="bi bi-truck me-2 text-primary"></i>{{ $mode->name }}</span>
                        </td>
                        <td class="text-secondary small">{{ $mode->description ?? 'Standard transport mode' }}</td>
                        <td>
                            @if($mode->is_active)
                                <span class="badge bg-success-subtle text-success">Active</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end pe-3">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModeModal{{ $mode->id }}" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            @if($mode->shop_id !== null)
                                <form method="post" action="{{ route('purchases.transport_modes.destroy', $mode) }}" class="d-inline" onsubmit="return confirm('Delete transport mode {{ $mode->name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>

                    {{-- Edit Modal for each mode --}}
                    <div class="modal fade" id="editModeModal{{ $mode->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="post" action="{{ route('purchases.transport_modes.update', $mode) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">Edit Receive Via Mode</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Mode Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="name" value="{{ old('name', $mode->name) }}" required placeholder="e.g. Courier / Cargo, Suzuki Pickup">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Description</label>
                                            <textarea class="form-control" name="description" rows="2" placeholder="Brief note on when this mode is used...">{{ old('description', $mode->description) }}</textarea>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="activeSwitch{{ $mode->id }}" @checked(old('is_active', $mode->is_active))>
                                            <label class="form-check-label fw-semibold" for="activeSwitch{{ $mode->id }}">Active for GRN & IGP selection</label>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-5">
                            <i class="bi bi-truck fs-3 d-block mb-2"></i>
                            No transport modes found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white border-top-0 py-3">
        <x-table-pagination :paginator="$modes" />
    </div>
</div>

{{-- Create Modal --}}
<div class="modal fade" id="createModeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="{{ route('purchases.transport_modes.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi text-primary me-2"></i>New Transpot Mode</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mode Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required placeholder="e.g. Railway Cargo, Third-Party Courier">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea class="form-control" name="description" rows="2" placeholder="Brief note on when this mode is used..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Mode</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
