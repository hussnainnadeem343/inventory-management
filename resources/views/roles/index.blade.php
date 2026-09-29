@extends('layouts.app')
@section('title', 'Roles & Permissions')
@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1"><i class="bi bi-shield-lock text-primary me-2"></i>Roles & Permissions</h2>
        <p class="text-secondary mb-0">Create custom employee roles and configure module access rights for your store staff.</p>
    </div>

    <div class="d-flex gap-2">
        <a class="btn btn-primary" href="{{ route('roles.create') }}">
            <i class="bi bi-plus-circle me-1"></i>+ Create Custom Role
        </a>
    </div>
</div>

@if(auth()->user()->isSuperAdmin() && $shops->count() > 1)
    <div class="card p-3 mb-3">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small text-secondary">Filter by Store:</label>
                <select name="shop_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Stores Combined</option>
                    @foreach($shops as $s)
                        <option value="{{ $s->id }}" @selected($shopId == $s->id)>{{ $s->name }} ({{ $s->code }})</option>
                    @endforeach
                </select>
            </div>
            @if($shopId)
                <div class="col-md-2">
                    <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            @endif
        </form>
    </div>
@endif

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 25%;">Role Name</th>
                    @if(auth()->user()->isSuperAdmin())
                        <th style="width: 15%;">Store</th>
                    @endif
                    <th style="width: 30%;">Description</th>
                    <th style="width: 12%;" class="text-center">Staff Count</th>
                    <th style="width: 12%;" class="text-center">Permissions</th>
                    <th style="width: 16%;" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $role)
                    @php
                        $canManage = auth()->user()->isSuperAdmin() || (auth()->user()->isShopAdmin() && $role->shop_id === auth()->user()->shop_id);
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-bold text-dark fs-6">{{ $role->name }}</div>
                            <code class="small text-secondary">{{ $role->slug }}</code>
                        </td>
                        @if(auth()->user()->isSuperAdmin())
                            <td>
                                <span class="badge text-bg-light border">
                                    {{ $role->shop->name ?? 'System Default' }}
                                </span>
                            </td>
                        @endif
                        <td>
                            <span class="text-secondary small">{{ $role->description ?: 'No description provided.' }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge text-bg-{{ $role->users_count > 0 ? 'primary' : 'light border' }} px-2 py-1">
                                <i class="bi bi-people me-1"></i>{{ $role->users_count }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge text-bg-success px-2 py-1">
                                <i class="bi bi-check2-circle me-1"></i>{{ $role->permissions_count }} rights
                            </span>
                        </td>
                        <td class="text-end">
                            @if($canManage)
                                <div class="d-inline-flex gap-1">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('roles.edit', $role) }}">
                                        <i class="bi bi-pencil-square me-1"></i>Edit
                                    </a>
                                    <form method="post" action="{{ route('roles.destroy', $role) }}" data-confirm="Are you sure you want to delete role '{{ $role->name }}'?">
                                        @csrf
                                        @method('delete')
                                        <button class="btn btn-sm btn-outline-danger" {{ $role->users_count > 0 ? 'disabled title="Cannot delete role with assigned users"' : '' }}>
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                </div>
                            @else
                                <span class="text-muted small">View Only</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isSuperAdmin() ? 6 : 5 }}" class="text-center py-5 text-muted">
                            <i class="bi bi-shield-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            <strong>No custom roles configured yet.</strong>
                            <div class="small">Click "+ Create Custom Role" above to set up roles like Cashier, Manager or Stock Clerk.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
