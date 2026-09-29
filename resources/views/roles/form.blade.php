@extends('layouts.app')
@section('title', $role->exists ? 'Edit Role: ' . $role->name : 'Create Custom Role')
@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1">
            <i class="bi bi-shield-lock text-primary me-2"></i>{{ $role->exists ? 'Edit Role: ' . $role->name : 'Create Custom Role' }}
        </h2>
        <p class="text-secondary mb-0">Define role details and check the specific screen rights for this role.</p>
    </div>
    <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Roles
    </a>
</div>

<x-errors />

<form method="post" action="{{ $role->exists ? route('roles.update', $role) : route('roles.store') }}">
    @csrf
    @if($role->exists)
        @method('put')
    @endif

    <div class="row g-3">
        {{-- Left: Role Meta Details --}}
        <div class="col-lg-4">
            <div class="card p-3 shadow-sm border-0 sticky-top" style="top: 85px;">
                <h5 class="fw-bold mb-3"><i class="bi bi-info-circle text-primary me-2"></i>Role Details</h5>

                @if(auth()->user()->isSuperAdmin() && $shops->count() > 0 && ! $role->exists)
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Target Store <span class="text-danger">*</span></label>
                        <select name="shop_id" class="form-select" required>
                            @foreach($shops as $shop)
                                <option value="{{ $shop->id }}" @selected(old('shop_id', $role->shop_id) == $shop->id)>{{ $shop->name }} ({{ $shop->code }})</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="mb-3">
                    <label class="form-label fw-semibold">Role Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $role->name) }}" placeholder="e.g. Counter Cashier, Shift Supervisor" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Brief summary of duties and responsibilities...">{{ old('description', $role->description) }}</textarea>
                </div>

                <div class="border-top pt-3 mt-2">
                    <button type="button" id="btn_select_all_global" class="btn btn-sm btn-outline-secondary w-100 mb-2">
                        <i class="bi bi-check-all me-1"></i>Select All Permissions
                    </button>
                    <button type="button" id="btn_deselect_all_global" class="btn btn-sm btn-outline-danger w-100 mb-3">
                        <i class="bi bi-x-circle me-1"></i>Deselect All
                    </button>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                        <i class="bi bi-save me-1"></i>{{ $role->exists ? 'Update Role & Rights' : 'Save New Role' }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Right: Module Permissions Grid --}}
        <div class="col-lg-8">
            @php
                $moduleLabels = [
                    'pos' => ['title' => 'POS & Counter Sales', 'icon' => 'cart3', 'badge' => 'primary'],
                    'products' => ['title' => 'Products Catalog', 'icon' => 'box-seam', 'badge' => 'info'],
                    'stock' => ['title' => 'Stock & Inventory Operations', 'icon' => 'boxes', 'badge' => 'success'],
                    'reports' => ['title' => 'Financial & Stock Reports', 'icon' => 'graph-up-arrow', 'badge' => 'warning'],
                    'catalog' => ['title' => 'Brands & Categories', 'icon' => 'tags', 'badge' => 'secondary'],
                    'users' => ['title' => 'Staff Directory & Accounts', 'icon' => 'people', 'badge' => 'dark'],
                    'roles' => ['title' => 'Roles & Security Governance', 'icon' => 'shield-lock', 'badge' => 'danger'],
                ];
            @endphp

            @foreach($permissionsByModule as $module => $modulePermissions)
                @php
                    $meta = $moduleLabels[$module] ?? ['title' => ucfirst($module), 'icon' => 'gear', 'badge' => 'secondary'];
                @endphp
                <div class="card mb-3 shadow-sm border-0 permission-module-card">
                    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                        <div class="fw-bold d-flex align-items-center gap-2">
                            <i class="bi bi-{{ $meta['icon'] }} text-{{ $meta['badge'] }} fs-5"></i>
                            <span>{{ $meta['title'] }}</span>
                            <span class="badge text-bg-light border small">{{ $modulePermissions->count() }} rights</span>
                        </div>
                        <div class="form-check mb-0">
                            <input class="form-check-input select-all-module" type="checkbox" id="select_all_{{ $module }}">
                            <label class="form-check-label small text-secondary fw-semibold" for="select_all_{{ $module }}">
                                Select All
                            </label>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2">
                            @foreach($modulePermissions as $perm)
                                @php
                                    $isChecked = in_array($perm->id, old('permissions', $rolePermissionIds), false);
                                @endphp
                                <div class="col-md-6">
                                    <div class="border rounded-2 p-2 h-100 bg-light-subtle">
                                        <div class="form-check">
                                            <input class="form-check-input perm-checkbox" type="checkbox" name="permissions[]" value="{{ $perm->id }}" id="perm_{{ $perm->id }}" @checked($isChecked)>
                                            <label class="form-check-label fw-semibold text-dark" for="perm_{{ $perm->id }}">
                                                {{ $perm->name }}
                                            </label>
                                        </div>
                                        @if($perm->description)
                                            <div class="text-secondary small ms-4 mt-1" style="font-size: 11px;">
                                                {{ $perm->description }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Module level select-all
    document.querySelectorAll('.select-all-module').forEach(toggle => {
        toggle.addEventListener('change', function () {
            const card = this.closest('.permission-module-card');
            if (card) {
                card.querySelectorAll('.perm-checkbox').forEach(cb => {
                    cb.checked = toggle.checked;
                });
            }
        });
    });

    // Global Select All
    const btnSelectAll = document.getElementById('btn_select_all_global');
    if (btnSelectAll) {
        btnSelectAll.addEventListener('click', function () {
            document.querySelectorAll('.perm-checkbox').forEach(cb => cb.checked = true);
            document.querySelectorAll('.select-all-module').forEach(cb => cb.checked = true);
        });
    }

    // Global Deselect All
    const btnDeselectAll = document.getElementById('btn_deselect_all_global');
    if (btnDeselectAll) {
        btnDeselectAll.addEventListener('click', function () {
            document.querySelectorAll('.perm-checkbox').forEach(cb => cb.checked = false);
            document.querySelectorAll('.select-all-module').forEach(cb => cb.checked = false);
        });
    }
});
</script>
@endpush
