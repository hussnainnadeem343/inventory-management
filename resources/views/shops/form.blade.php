@extends('layouts.app')
@section('title', $shop->exists ? 'Edit Shop' : 'Add New Shop')
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card p-4">
            <h2 class="h5 mb-3">{{ $shop->exists ? 'Edit Shop Profile' : 'Register New Shop' }}</h2>
            <form method="post" action="{{ $shop->exists ? route('shops.update', $shop) : route('shops.store') }}">
                @csrf
                @if($shop->exists)
                    @method('put')
                @endif

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Shop Name <span class="text-danger">*</span></label>
                        <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $shop->name) }}" required placeholder="e.g. Glamour Cosmetics, Apex Shoes">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Shop Code <span class="text-danger">*</span></label>
                        <input class="form-control font-monospace @error('code') is-invalid @enderror" name="code" value="{{ old('code', $shop->code) }}" required placeholder="e.g. GLAM-01">
                        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Business Type <span class="text-danger">*</span></label>
                        @php
                            $currentCanonical = \App\Services\ModuleRegistry::normalizeBusinessType(old('business_type', $shop->business_type));
                        @endphp
                        <select name="business_type" class="form-select @error('business_type') is-invalid @enderror" required>
                            <option value="Retail & General Business" @selected($currentCanonical === \App\Services\ModuleRegistry::TYPE_RETAIL)>
                                Retail &amp; General Business (Point of Sale, Inventory, Purchases, Finance, HR)
                            </option>
                            <option value="Lube (Oil Changing) Station" @selected($currentCanonical === \App\Services\ModuleRegistry::TYPE_LUBE)>
                                Lube (Oil Changing) Station (Includes Retail + Auto Lube Pit Stop, Vehicle Records, Oil Reminders)
                            </option>
                        </select>
                        <small class="text-muted d-block mt-1">
                            Defines which modules are available for this business and its staff roles.
                        </small>
                        @error('business_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone Number</label>
                        <input class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $shop->phone) }}" placeholder="e.g. +923114670195">
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Physical Address</label>
                        <textarea class="form-control @error('address') is-invalid @enderror" name="address" rows="2" placeholder="Shop #, Market, City">{{ old('address', $shop->address) }}</textarea>
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                            <option value="active" @selected(old('status', $shop->status ?? 'active') === 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $shop->status) === 'inactive')>Inactive</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <div class="p-3 bg-light rounded-3 border">
                            <h6 class="fw-bold text-dark mb-1"><i class="bi bi-shield-check text-primary me-1"></i>Subscription Quotas &amp; Tier Limits</h6>
                            <small class="text-muted d-block mb-3">Define the maximum number of branches and staff accounts allowed under this business plan.</small>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Max Branches Allowed <span class="text-danger">*</span></label>
                                    <input type="number" min="1" max="100" class="form-control @error('max_branches') is-invalid @enderror" name="max_branches" value="{{ old('max_branches', $shop->max_branches ?? 1) }}" required>
                                    <small class="text-muted">Includes the HQ / main store. (e.g. 1 = Single Shop, 5 = Multi-Branch)</small>
                                    @error('max_branches') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Max Users / Staff Allowed <span class="text-danger">*</span></label>
                                    <input type="number" min="1" max="1000" class="form-control @error('max_users') is-invalid @enderror" name="max_users" value="{{ old('max_users', $shop->max_users ?? 5) }}" required>
                                    <small class="text-muted">Total user accounts across all branches of this client business.</small>
                                    @error('max_users') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if(! $shop->exists)
                    <hr class="my-4">
                    <h3 class="h6 mb-2 text-primary"><i class="bi bi-person-badge me-1"></i>Initial Business / Shop Admin Account (Optional)</h3>
                    <p class="text-muted small mb-3">Create the primary administrative login for this business client now.</p>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Account Role <span class="text-danger">*</span></label>
                            <select class="form-select @error('admin_role') is-invalid @enderror" name="admin_role">
                                <option value="business_owner" @selected(old('admin_role', 'business_owner') === 'business_owner')>
                                    Business Owner (Client Principal - Can self-create sub-branches, manage all staff &amp; view consolidated analytics)
                                </option>
                                <option value="shop_admin" @selected(old('admin_role') === 'shop_admin')>
                                    Shop Admin (Standard branch manager - Restricted to this store)
                                </option>
                            </select>
                            @error('admin_role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Admin Full Name</label>
                            <input class="form-control @error('admin_name') is-invalid @enderror" name="admin_name" value="{{ old('admin_name') }}" placeholder="e.g. Muhammad Ali">
                            @error('admin_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <input class="form-control @error('admin_username') is-invalid @enderror" name="admin_username" value="{{ old('admin_username') }}" placeholder="e.g. ali_glamour">
                            @error('admin_username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email (Optional)</label>
                            <input class="form-control @error('admin_email') is-invalid @enderror" type="email" name="admin_email" value="{{ old('admin_email') }}" placeholder="ali@example.com">
                            @error('admin_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input class="form-control @error('admin_password') is-invalid @enderror" type="password" name="admin_password" placeholder="At least 8 characters">
                            @error('admin_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                @endif

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a class="btn btn-outline-secondary" href="{{ route('shops.index') }}">Cancel</a>
                    <button class="btn btn-primary" type="submit">{{ $shop->exists ? 'Update Shop' : 'Create Shop' }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
