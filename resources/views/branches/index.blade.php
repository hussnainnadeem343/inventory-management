@extends('layouts.app')
@section('title', 'Branch Management')
@section('content')
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small text-muted">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('management.index') }}" class="text-decoration-none text-muted">Management</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Branches</li>
        </ol>
    </nav>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h4 class="mb-0 fw-bold">{{ $business->name }} &bull; Branch Network</h4>
            <small class="text-secondary">Manage sub-branches, physical locations, and branch staff allocations</small>
        </div>
        <div>
            @if($canAddBranch)
                <a class="btn btn-primary shadow-sm" href="{{ route('branches.create', ['business_id' => $business->id]) }}">
                    <i class="bi bi-plus-lg me-1"></i>+ Add New Branch
                </a>
            @else
                <button type="button" class="btn btn-secondary shadow-sm" data-bs-toggle="modal" data-bs-target="#branchQuotaModal">
                    <i class="bi bi-lock-fill me-1"></i>Branch Limit Reached
                </button>
            @endif
        </div>
    </div>
</div>

@if(auth()->user()->isSuperAdmin() && isset($allBusinesses) && $allBusinesses->count() > 1)
    <div class="card p-2.5 px-3 mb-3 border shadow-sm bg-white">
        <form method="get" action="{{ route('branches.index') }}" class="d-flex flex-wrap align-items-center gap-2">
            <label for="select_client_business" class="form-label mb-0 fw-semibold text-secondary small text-nowrap">
                <i class="bi bi-building text-primary me-1"></i>Client Business:
            </label>
            <select id="select_client_business" name="business_id" class="form-select form-select-sm" style="min-width: 250px; max-width: 400px;" onchange="this.form.submit()">
                @foreach($allBusinesses as $b)
                    <option value="{{ $b->id }}" @selected($b->id === $business->id)>
                        {{ $b->name }} ({{ $b->code }}) &bull; {{ $b->business_type ?: 'General Retail' }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>
@endif

{{-- Quota Overview Cards --}}
<div class="row g-3 mb-4">
    {{-- Card 1: Branch Quota --}}
    <div class="col-sm-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <span class="text-secondary small fw-semibold text-uppercase">Branch Quota</span>
                    <h3 class="h4 fw-bold mb-0 text-dark">{{ $totalBranchesCount }} <span class="fs-6 text-muted fw-normal">/ {{ $maxBranches }}</span></h3>
                </div>
                <span class="badge rounded-pill {{ $totalBranchesCount >= $maxBranches ? 'text-bg-warning' : 'text-bg-primary' }} px-3 py-2">
                    <i class="bi bi-shop me-1"></i>{{ $maxBranches - $totalBranchesCount > 0 ? ($maxBranches - $totalBranchesCount) . ' Available' : 'Limit Reached' }}
                </span>
            </div>
            @php $branchPercent = min(100, round(($totalBranchesCount / max(1, $maxBranches)) * 100)); @endphp
            <div class="progress mt-2" style="height: 6px;">
                <div class="progress-bar {{ $branchPercent >= 100 ? 'bg-warning' : 'bg-primary' }}" style="width: {{ $branchPercent }}%"></div>
            </div>
            <small class="text-muted mt-2 d-block" style="font-size: 11px;">
                Includes Headquarter (HQ) plus {{ $branches->count() }} active sub-branches.
            </small>
        </div>
    </div>

    {{-- Card 2: Staff Quota --}}
    <div class="col-sm-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <span class="text-secondary small fw-semibold text-uppercase">Staff &amp; Users Quota</span>
                    <h3 class="h4 fw-bold mb-0 text-dark">{{ $totalUsersCount }} <span class="fs-6 text-muted fw-normal">/ {{ $maxUsers }}</span></h3>
                </div>
                <span class="badge rounded-pill {{ $totalUsersCount >= $maxUsers ? 'text-bg-danger' : 'text-bg-info text-white' }} px-3 py-2">
                    <i class="bi bi-people me-1"></i>{{ $maxUsers - $totalUsersCount > 0 ? ($maxUsers - $totalUsersCount) . ' Slots Free' : 'Full' }}
                </span>
            </div>
            @php $userPercent = min(100, round(($totalUsersCount / max(1, $maxUsers)) * 100)); @endphp
            <div class="progress mt-2" style="height: 6px;">
                <div class="progress-bar {{ $userPercent >= 100 ? 'bg-danger' : 'bg-info' }}" style="width: {{ $userPercent }}%"></div>
            </div>
            <small class="text-muted mt-2 d-block" style="font-size: 11px;">
                Total active users allocated across all branches of {{ $business->name }}.
            </small>
        </div>
    </div>

    {{-- Card 3: Business Information --}}
    <div class="col-sm-12 col-lg-4">
        <div class="card h-100 border-0 shadow-sm p-3 bg-light">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-secondary small fw-semibold text-uppercase">Business Profile</span>
                    <h5 class="fw-bold mb-1 text-dark">{{ $business->name }}</h5>
                    <span class="badge text-bg-secondary">{{ $business->business_type ?: 'General Retail' }}</span>
                </div>
                <span class="avatar bg-white text-primary border shadow-sm" style="width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;">
                    <i class="bi bi-building"></i>
                </span>
            </div>
            <p class="text-muted small mt-2 mb-0">
                HQ Code: <strong class="text-dark font-monospace">{{ $business->code }}</strong><br>
                Phone: {{ $business->phone ?: 'N/A' }}
            </p>
        </div>
    </div>
</div>

@if(!$canAddBranch)
    <div class="alert alert-warning d-flex align-items-center justify-content-between shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-info-circle-fill fs-4 text-warning"></i>
            <div>
                <strong>Branch Limit Reached ({{ $totalBranchesCount }} / {{ $maxBranches }} Branches Used)</strong>
                <div class="small">Your business has utilized all available branches allocated under your plan. Contact support or upgrade your subscription to open additional branch locations.</div>
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#branchQuotaModal">
            Upgrade Plan
        </button>
    </div>
@endif

{{-- Branch Network Table --}}
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-diagram-3 text-primary me-2"></i>All Business Locations &amp; Branches</h6>
        <span class="text-muted small">{{ $branches->count() + 1 }} Locations in Total</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Location / Branch</th>
                    <th>Branch Code</th>
                    <th>Role</th>
                    <th>Phone / Contact</th>
                    <th>Active Staff</th>
                    <th>Inventory Items</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                {{-- 1. Main Headquarter Store --}}
                <tr class="table-primary-subtle bg-primary-subtle">
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-building text-primary fs-5"></i>
                            <div>
                                <strong class="text-dark">{{ $business->name }}</strong>
                                <small class="text-muted d-block">{{ $business->address ?: 'Main Headquarter Office' }}</small>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge text-bg-light border font-monospace">{{ $business->code }}</span></td>
                    <td><span class="badge bg-primary text-white">Main Headquarter (HQ)</span></td>
                    <td>{{ $business->phone ?: '-' }}</td>
                    <td><span class="badge text-bg-info text-white">{{ $business->users()->count() }} Staff</span></td>
                    <td><span class="badge text-bg-secondary">{{ $business->inventoryItems()->count() }} Items</span></td>
                    <td>
                        <span class="badge text-bg-{{ $business->status === 'active' ? 'success' : 'danger' }}">
                            {{ ucfirst($business->status) }}
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('dashboard', ['shop_id' => $business->id]) }}" class="btn btn-sm btn-outline-primary" title="View Store Dashboard">
                            <i class="bi bi-speedometer2"></i>
                        </a>
                    </td>
                </tr>

                {{-- 2. Sub-Branches --}}
                @forelse($branches as $branch)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-shop text-secondary fs-5"></i>
                                <div>
                                    <strong>{{ $branch->name }}</strong>
                                    <small class="text-muted d-block">{{ $branch->address ?: 'Branch location' }}</small>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge text-bg-light border font-monospace">{{ $branch->code }}</span></td>
                        <td><span class="badge text-bg-light text-secondary border">Sub-Branch</span></td>
                        <td>{{ $branch->phone ?: '-' }}</td>
                        <td><span class="badge text-bg-info text-white">{{ $branch->users_count }} Staff</span></td>
                        <td><span class="badge text-bg-secondary">{{ $branch->inventory_items_count }} Items</span></td>
                        <td>
                            <span class="badge text-bg-{{ $branch->status === 'active' ? 'success' : 'danger' }}">
                                {{ ucfirst($branch->status) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                <a href="{{ route('dashboard', ['shop_id' => $branch->id]) }}" class="btn btn-sm btn-outline-info" title="View Branch Dashboard">
                                    <i class="bi bi-speedometer2"></i>
                                </a>
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('branches.edit', $branch) }}" title="Edit Branch">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="post" action="{{ route('branches.destroy', $branch) }}" data-confirm="Are you sure you want to remove or deactivate this branch?">
                                    @csrf
                                    @method('delete')
                                    <button class="btn btn-sm btn-outline-danger" title="Deactivate"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-diagram-3 fs-3 d-block mb-1 text-secondary"></i>
                            No sub-branches registered yet. Click <strong>+ Add New Branch</strong> to create your first child branch location.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Quota Upgrade Modal --}}
<div class="modal fade" id="branchQuotaModal" tabindex="-1" aria-labelledby="branchQuotaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="branchQuotaModalLabel"><i class="bi bi-rocket-takeoff me-2"></i>Upgrade Subscription Plan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="avatar bg-primary-subtle text-primary rounded-circle mx-auto mb-3" style="width: 60px; height: 60px; display: inline-flex; align-items: center; justify-content: center; font-size: 28px;">
                    <i class="bi bi-diagram-3"></i>
                </div>
                <h5 class="fw-bold mb-2">Need More Branches or Users?</h5>
                <p class="text-secondary small mb-4">
                    Your current business plan is configured for up to <strong>{{ $maxBranches }} branches</strong> and <strong>{{ $maxUsers }} staff accounts</strong>.
                    To unlock higher multi-tier branch capacity and centralized inventory transfers, please reach out to our platform administrator.
                </p>
                <div class="p-3 bg-light rounded-3 text-start small mb-3 border">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Business:</span>
                        <strong class="text-dark">{{ $business->name }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Current Branches Limit:</span>
                        <strong class="text-dark">{{ $maxBranches }} Branches</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Current Staff Limit:</span>
                        <strong class="text-dark">{{ $maxUsers }} Users</strong>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <a href="mailto:support@denfin.com?subject=Quota%20Upgrade%20Request%20for%20{{ urlencode($business->name) }}" class="btn btn-primary">
                    <i class="bi bi-envelope me-1"></i>Contact Support
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
