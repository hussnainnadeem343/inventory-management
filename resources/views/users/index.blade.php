@extends('layouts.app')
@section('title', auth()->user()->isSuperAdmin() ? 'User Management' : 'Shop Users & Staff')
@section('content')
<div class="card p-3 mb-3">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-lg-{{ auth()->user()->isSuperAdmin() ? '3' : '4' }} col-md-6">
            <label class="form-label">Search Users</label>
            <input class="form-control" name="search" value="{{ $search }}" placeholder="Name, username, or email">
        </div>
        @if(auth()->user()->isSuperAdmin())
            <div class="col-sm-6 col-lg-3 col-md-3">
                <label class="form-label">Shop</label>
                <select class="form-select" name="shop_id">
                    <option value="">All Shops</option>
                    @foreach($shops as $shop)
                        <option value="{{ $shop->id }}" @selected($shopId == $shop->id)>{{ $shop->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-lg-2 col-md-3">
                <label class="form-label">Role</label>
                <select class="form-select" name="role">
                    <option value="">All Roles</option>
                    <option value="super_admin" @selected($role === 'super_admin')>Super Admin</option>
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

<div class="text-end mb-3">
    <a class="btn btn-primary" href="{{ route('users.create') }}">
        <i class="bi bi-person-plus me-1"></i>+ {{ auth()->user()->isSuperAdmin() ? 'Add User' : 'Add Shop User' }}
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>User</th>
                    @if(auth()->user()->isSuperAdmin())
                        <th>Shop</th>
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
                        @if(auth()->user()->isSuperAdmin())
                            <td>
                                @if($user->shop)
                                    <span class="badge text-bg-light border">{{ $user->shop->name }}</span>
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
                                    'shop_admin' => 'primary',
                                    default => 'secondary'
                                };
                            @endphp
                            <span class="badge text-bg-{{ $roleBadge }}">
                                {{ str($user->role)->replace('_', ' ')->title() }}
                            </span>
                        </td>
                        <td>
                            <span class="badge text-bg-{{ $user->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($user->status) }}
                            </span>
                        </td>
                        <td>{{ $user->created_at->format('d-M-Y h:i A') }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('users.edit', $user) }}">Edit</a>
                                @if(! auth()->user()->is($user) && ! $user->isSuperAdmin())
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
                        <td colspan="{{ auth()->user()->isSuperAdmin() ? 7 : 6 }}" class="text-center py-4">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $users->onEachSide(1)->links() }}</div>
@endsection
