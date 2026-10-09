@extends('layouts.app')
@section('title', 'Purchase Requisitions')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Purchase Requisitions (PR)</h4>
        <small class="text-secondary">Stage 1: Internal department demands & stock requests</small>
    </div>
    @if(auth()->user()->hasPermission('purchases.requisitions') || auth()->user()->isShopAdmin() || auth()->user()->isSuperAdmin())
        <a class="btn btn-primary" href="{{ route('purchases.requisitions.create') }}">
            <i class="bi bi-plus-lg me-1"></i> New Requisition
        </a>
    @endif
</div>

<div class="card shadow-sm border-0 mb-3 bg-white">
    <div class="card-body py-2 px-3">
        <form method="get" action="{{ route('purchases.requisitions.index') }}" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0 text-secondary"><i class="bi bi-search"></i></span>
                    <input type="search" name="search" class="form-control border-start-0" placeholder="Search PR number, department, notes..." value="{{ $search }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="pending_approval" @selected($status === 'pending_approval')>Pending Approval</option>
                    <option value="approved" @selected($status === 'approved')>Approved</option>
                    <option value="converted_to_po" @selected($status === 'converted_to_po')>Converted to PO</option>
                    <option value="rejected" @selected($status === 'rejected')>Rejected</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
                @if($search || $status)
                    <a href="{{ route('purchases.requisitions.index') }}" class="btn btn-sm btn-link text-decoration-none text-muted">Clear</a>
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
                    <th class="ps-3">PR Number</th>
                    <th>Date</th>
                    <th>Department / Station</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Items</th>
                    <th>Requested By</th>
                    <th class="text-end pe-3">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requisitions as $pr)
                    <tr>
                        <td class="ps-3">
                            <a href="{{ route('purchases.requisitions.show', $pr) }}" class="fw-bold text-decoration-none">
                                {{ $pr->pr_number }}
                            </a>
                        </td>
                        <td>{{ $pr->requisition_date->format('d M, Y') }}</td>
                        <td>{{ $pr->department ?? 'General Store' }}</td>
                        <td>
                            @php
                                $priorityColors = [
                                    'urgent' => 'danger',
                                    'high' => 'warning',
                                    'medium' => 'info',
                                    'low' => 'secondary',
                                ];
                            @endphp
                            <span class="badge bg-{{ $priorityColors[$pr->priority] ?? 'secondary' }}-subtle text-{{ $priorityColors[$pr->priority] ?? 'secondary' }} text-uppercase">
                                {{ $pr->priority }}
                            </span>
                        </td>
                        <td>
                            @php
                                $statusBadges = [
                                    'pending_approval' => ['warning', 'Pending Approval'],
                                    'approved' => ['success', 'Approved'],
                                    'converted_to_po' => ['primary', 'Converted to PO'],
                                    'rejected' => ['danger', 'Rejected'],
                                    'draft' => ['secondary', 'Draft'],
                                ];
                                $sb = $statusBadges[$pr->status] ?? ['secondary', ucfirst($pr->status)];
                            @endphp
                            <span class="badge bg-{{ $sb[0] }}-subtle text-{{ $sb[0] }}">
                                {{ $sb[1] }}
                            </span>
                        </td>
                        <td><span class="badge bg-light text-dark border">{{ $pr->items->count() }} items</span></td>
                        <td><small class="text-secondary">{{ $pr->requester?->name ?? 'Staff' }}</small></td>
                        <td class="text-end pe-3">
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('purchases.requisitions.show', $pr) }}">
                                <i class="bi bi-eye me-1"></i> View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            No purchase requisitions found. Click <strong>New Requisition</strong> to raise a demand.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($requisitions->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $requisitions->links() }}
        </div>
    @endif
</div>
@endsection
