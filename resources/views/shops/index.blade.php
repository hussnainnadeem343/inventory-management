@extends('layouts.app')
@section('title', 'Shops Management')
@section('content')
<div class="card p-3 mb-3">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-lg-6 col-md-6">
            <label class="form-label">Search Shops</label>
            <input class="form-control" name="search" value="{{ $search }}" placeholder="Shop name, code, or business type">
        </div>
        <div class="col-sm-6 col-lg-3 col-md-3">
            <label class="form-label">Per page</label>
            <select class="form-select" name="per_page">
                @foreach([10, 20, 30, 50, 100] as $size)
                    <option @selected($perPage === $size)>{{ $size }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-3 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Search</button>
            <a class="btn btn-outline-secondary" href="{{ route('shops.index') }}">Reset</a>
        </div>
    </form>
</div>

<div class="text-end mb-3">
    <a class="btn btn-primary" href="{{ route('shops.create') }}"><i class="bi bi-shop me-1"></i>+ Add New Shop</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Business / Shop Name</th>
                    <th>Type</th>
                    <th>Tier / Hierarchy</th>
                    <th>Branches Quota</th>
                    <th>Staff Quota</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shops as $shop)
                    <tr>
                        <td>
                            <strong>{{ $shop->name }}</strong><br>
                            <span class="badge text-bg-light border font-monospace">{{ $shop->code }}</span>
                            @if($shop->phone)
                                <small class="text-muted d-block"><i class="bi bi-telephone me-1"></i>{{ $shop->phone }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge text-bg-secondary">{{ $shop->business_type ?: 'General' }}</span>
                        </td>
                        <td>
                            @if($shop->isBusiness())
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                    <i class="bi bi-building me-1"></i>Client HQ
                                </span>
                            @else
                                <span class="badge bg-light text-secondary border">
                                    <i class="bi bi-arrow-return-right me-1"></i>Branch of {{ $shop->parent->name ?? 'HQ' }}
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($shop->isBusiness())
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-semibold small">{{ $shop->totalBranchesCount() }} / {{ $shop->max_branches }}</span>
                                    <div class="progress flex-grow-1" style="height: 6px; width: 60px;">
                                        @php $branchPct = min(100, round(($shop->totalBranchesCount() / max(1, $shop->max_branches)) * 100)); @endphp
                                        <div class="progress-bar {{ $branchPct >= 100 ? 'bg-danger' : 'bg-primary' }}" style="width: {{ $branchPct }}%"></div>
                                    </div>
                                </div>
                                <small class="text-muted" style="font-size: 11px;">{{ $shop->branches_count }} sub-branches</small>
                            @else
                                <span class="text-muted small">&mdash;</span>
                            @endif
                        </td>
                        <td>
                            @if($shop->isBusiness())
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-semibold small">{{ $shop->totalUsersCount() }} / {{ $shop->max_users }}</span>
                                    <div class="progress flex-grow-1" style="height: 6px; width: 60px;">
                                        @php $userPct = min(100, round(($shop->totalUsersCount() / max(1, $shop->max_users)) * 100)); @endphp
                                        <div class="progress-bar {{ $userPct >= 100 ? 'bg-danger' : 'bg-info' }}" style="width: {{ $userPct }}%"></div>
                                    </div>
                                </div>
                                <small class="text-muted" style="font-size: 11px;">Across all branches</small>
                            @else
                                <span class="badge text-bg-light border text-muted">{{ $shop->users_count }} users</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge text-bg-{{ $shop->status === 'active' ? 'success' : 'danger' }}">
                                {{ ucfirst($shop->status) }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                @if($shop->isBusiness())
                                    <a class="btn btn-sm btn-outline-info" href="{{ route('branches.index', ['business_id' => $shop->id]) }}" title="Manage Branches">
                                        <i class="bi bi-diagram-3"></i>
                                    </a>
                                @endif
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('shops.edit', $shop) }}" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="post" action="{{ route('shops.destroy', $shop) }}" data-confirm="Are you sure you want to deactivate/delete this shop?">
                                    @csrf
                                    @method('delete')
                                    <button class="btn btn-sm btn-outline-danger" title="Deactivate"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">No shops found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $shops->onEachSide(1)->links() }}</div>
@endsection
