@extends('layouts.app')
@section('title', auth()->user()->isSuperAdmin() ? 'Business Owners & Shop Admins' : 'Shop Users & Staff')
@section('content')
@if(auth()->user()->isSuperAdmin())
    <div class="mb-3">
        <h4 class="mb-0 fw-bold"><i class="bi bi-person-badge text-primary me-2"></i>Business Owners &amp; Shop Admins</h4>
        <small class="text-secondary">Platform directory to manage client Business Owners and branch Shop Admin accounts</small>
    </div>
@endif

<div class="card p-3 mb-3">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-lg-{{ (auth()->user()->isSuperAdmin() || (auth()->user()->isBusinessOwner() && $shops->count() > 1)) ? '3' : '4' }} col-md-6">
            <label class="form-label">Search Users</label>
            <input class="form-control" name="search" value="{{ $search }}" placeholder="Name, username, or email">
        </div>
        @if(auth()->user()->isSuperAdmin())
            <div class="col-sm-6 col-lg-3 col-md-3">
                <label class="form-label">Business / Branch</label>
                <select class="form-select" name="shop_id">
                    <option value="">All Businesses &amp; Branches</option>
                    @foreach($shops as $shop)
                        <option value="{{ $shop->id }}" @selected($shopId == $shop->id)>
                            {{ $shop->name }} ({{ $shop->code }}){{ $shop->isBusiness() ? ' - HQ' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-lg-2 col-md-3">
                <label class="form-label">Role</label>
                <select class="form-select" name="role">
                    <option value="">Owners &amp; Shop Admins</option>
                    <option value="business_owner" @selected($role === 'business_owner')>Business Owner</option>
                    <option value="shop_admin" @selected($role === 'shop_admin')>Shop Admin</option>
                </select>
            </div>
        @elseif(auth()->user()->isBusinessOwner() && $shops->count() > 1)
            <div class="col-sm-6 col-lg-3 col-md-3">
                <label class="form-label">Branch</label>
                <select class="form-select" name="shop_id">
                    <option value="">All Branches</option>
                    @foreach($shops as $shop)
                        <option value="{{ $shop->id }}" @selected($shopId == $shop->id)>{{ $shop->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-lg-2 col-md-3">
                <label class="form-label">Role</label>
                <select class="form-select" name="role">
                    <option value="">All Roles</option>
                    <option value="shop_admin" @selected($role === 'shop_admin')>Shop Admin</option>
                    <option value="staff" @selected($role === 'staff')>Staff</option>
                </select>
            </div>
        @else
            <div class="col-sm-6 col-lg-3 col-md-3">
                <label class="form-label">Role</label>
                <select class="form-select" name="role">
                    <option value="">All Roles</option>
                    <option value="shop_admin" @selected($role === 'shop_admin')>Shop Admin</option>
                    <option value="staff" @selected($role === 'staff')>Staff</option>
                </select>
            </div>
        @endif
        <div class="col-sm-6 col-lg-2 col-md-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
                <option value="">All Statuses</option>
                <option value="active" @selected($status === 'active')>Active</option>
                <option value="inactive" @selected($status === 'inactive')>Inactive</option>
            </select>
        </div>
        <div class="col-lg-2 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Search</button>
            <a class="btn btn-outline-secondary" href="{{ route('users.index') }}">Reset</a>
        </div>
    </form>
</div>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
    <div>
        @if($business && !auth()->user()->isSuperAdmin())
            <span class="badge bg-white text-dark border p-2 shadow-sm">
                <i class="bi bi-people text-primary me-1"></i>
                Staff Quota: <strong>{{ $business->totalUsersCount() }} / {{ $business->max_users }}</strong> Users Used
                @if(!$canAddUser)
                    <span class="text-danger fw-bold ms-1">&bull; Quota Reached</span>
                @else
                    <span class="text-success ms-1">({{ $business->max_users - $business->totalUsersCount() }} Slots Available)</span>
                @endif
            </span>
        @endif
    </div>
    <div class="text-end">
        @if($canAddUser || auth()->user()->isSuperAdmin())
            <a class="btn btn-primary" href="{{ route('users.create') }}">
                <i class="bi bi-person-plus me-1"></i>+ {{ auth()->user()->isSuperAdmin() ? 'Add Owner / Shop Admin' : 'Add Staff Member' }}
            </a>
        @else
            <button class="btn btn-secondary" disabled title="Staff quota reached">
                <i class="bi bi-lock-fill me-1"></i>Staff Limit Reached ({{ $business->max_users }} Max)
            </button>
        @endif
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>User</th>
                    @if(auth()->user()->isSuperAdmin() || auth()->user()->isBusinessOwner())
                        <th>Location / Branch</th>
                    @endif
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>
                            <strong>{{ $user->name }}</strong><br>
                            <small class="text-secondary font-monospace">{{ $user->username }}</small>
                        </td>
                        @if(auth()->user()->isSuperAdmin() || auth()->user()->isBusinessOwner())
                            <td>
                                @if($user->shop)
                                    <span class="badge text-bg-light border">
                                        {{ $user->shop->name }}
                                        @if($user->shop->isBusiness())
                                            <span class="text-primary fw-semibold ms-1">(HQ)</span>
                                        @endif
                                    </span>
                                @else
                                    <span class="badge text-bg-dark">System Global</span>
                                @endif
                            </td>
                        @endif
                        <td>{{ $user->email ?: '-' }}</td>
                        <td>
                            @php
                                $roleBadge = match($user->role) {
                                    'super_admin' => 'danger',
                                    'business_owner' => 'success',
                                    'shop_admin' => 'primary',
                                    default => 'secondary'
                                };
                            @endphp
                            <span class="badge text-bg-{{ $roleBadge }}">
                                {{ str($user->role)->replace('_', ' ')->title() }}
                            </span>
                            @if($user->customRole)
                                <div>
                                    <span class="badge text-bg-info text-white mt-1" style="font-size: 11px;">
                                        <i class="bi bi-shield-check me-1"></i>{{ $user->customRole->name }}
                                    </span>
                                </div>
                            @endif
                        </td>
                        <td>
                            <span class="badge text-bg-{{ $user->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($user->status) }}
                            </span>
                        </td>
                        <td>{{ $user->created_at->format('d-M-Y h:i A') }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                @php
                                    $currentAuthUser = auth()->user();
                                    $canEdit = $currentAuthUser->isSuperAdmin()
                                        || ($currentAuthUser->isBusinessOwner() && ($currentAuthUser->is($user) || (! $user->isSuperAdmin() && ! $user->isBusinessOwner() && in_array((int) $user->shop_id, $currentAuthUser->shop?->getAllBranchIds() ?? [], true))))
                                        || ($currentAuthUser->isShopAdmin() && $user->shop_id === $currentAuthUser->shop_id && ! in_array($user->role, ['super_admin', 'business_owner'], true));

                                    $canDelete = ! $currentAuthUser->is($user) && (
                                        $currentAuthUser->isSuperAdmin()
                                        || ($currentAuthUser->isBusinessOwner() && ! in_array($user->role, ['super_admin', 'business_owner'], true) && in_array((int) $user->shop_id, $currentAuthUser->shop?->getAllBranchIds() ?? [], true))
                                        || ($currentAuthUser->isShopAdmin() && $user->shop_id === $currentAuthUser->shop_id && ! in_array($user->role, ['super_admin', 'business_owner'], true))
                                    );
                                @endphp

                                @if($canEdit)
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('users.edit', $user) }}">Edit</a>
                                @endif

                                @if($canDelete)
                                    <form method="post" action="{{ route('users.destroy', $user) }}" data-confirm="Are you sure you want to deactivate/delete this user?">
                                        @csrf
                                        @method('delete')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ (auth()->user()->isSuperAdmin() || auth()->user()->isBusinessOwner()) ? 7 : 6 }}" class="text-center py-4">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $users->onEachSide(1)->links() }}</div>
@endsection
