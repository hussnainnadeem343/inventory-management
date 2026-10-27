@extends('layouts.app')
@section('title', $branch->exists ? 'Edit Branch' : 'Add New Branch')
@section('content')
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small text-muted">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('branches.index') }}" class="text-decoration-none text-muted">Branches</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">{{ $branch->exists ? 'Edit Branch' : 'New Branch' }}</li>
        </ol>
    </nav>
    <h4 class="mb-0 fw-bold">{{ $branch->exists ? 'Edit Branch: ' . $branch->name : 'Open New Branch Location' }}</h4>
    <small class="text-secondary">Sub-branch under parent business: <strong class="text-dark">{{ $business->name }}</strong></small>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card p-4 shadow-sm border-0">
            <form method="post" action="{{ $branch->exists ? route('branches.update', $branch) : route('branches.store') }}">
                @csrf
                <input type="hidden" name="business_id" value="{{ $business->id }}">
                @if($branch->exists)
                    @method('put')
                @endif

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Branch Name <span class="text-danger">*</span></label>
                        <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $branch->name) }}" required placeholder="e.g. {{ $business->name }} - Gulberg Branch">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Branch Code <span class="text-danger">*</span></label>
                        <input class="form-control font-monospace @error('code') is-invalid @enderror" name="code" value="{{ old('code', $branch->code) }}" required placeholder="e.g. BR-02">
                        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Business Vertical / Model</label>
                        <input class="form-control bg-light" value="{{ $business->business_type ?: 'General Retail' }}" disabled>
                        <small class="text-muted">Inherited automatically from parent business.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Phone / Contact Number</label>
                        <input class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $branch->phone) }}" placeholder="e.g. +92 300 9876543">
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Physical Location / Address</label>
                        <textarea class="form-control @error('address') is-invalid @enderror" name="address" rows="2" placeholder="e.g. Plot 14, Main Boulevard, Gulberg III, Lahore">{{ old('address', $branch->address) }}</textarea>
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Branch Status <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                            <option value="active" @selected(old('status', $branch->status ?? 'active') === 'active')>Active (Operational)</option>
                            <option value="inactive" @selected(old('status', $branch->status) === 'inactive')>Inactive (Suspended)</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                @if(! $branch->exists)
                    <hr class="my-4">
                    <h5 class="h6 mb-2 text-primary fw-bold"><i class="bi bi-person-badge me-1"></i>Initial Branch Manager Account (Optional)</h5>
                    <p class="text-muted small mb-3">You can immediately create a Shop Admin login dedicated to this branch, or assign staff later from the Staff Management section.</p>

                    @if($canAddUser)
                        <div class="row g-3 p-3 bg-light rounded-3 border">
                            <div class="col-md-6">
                                <label class="form-label">Manager Full Name</label>
                                <input class="form-control @error('admin_name') is-invalid @enderror" name="admin_name" value="{{ old('admin_name') }}" placeholder="e.g. Tariq Mahmood">
                                @error('admin_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input class="form-control @error('admin_username') is-invalid @enderror" name="admin_username" value="{{ old('admin_username') }}" placeholder="e.g. tariq_gulberg">
                                @error('admin_username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email (Optional)</label>
                                <input class="form-control @error('admin_email') is-invalid @enderror" type="email" name="admin_email" value="{{ old('admin_email') }}" placeholder="tariq@example.com">
                                @error('admin_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password</label>
                                <input class="form-control @error('admin_password') is-invalid @enderror" type="password" name="admin_password" placeholder="At least 8 characters">
                                @error('admin_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    @else
                        <div class="alert alert-info small mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            <strong>Staff Quota Full:</strong> Your business has used all allowed staff accounts ({{ $business->totalUsersCount() }} / {{ $business->max_users }}).
                            You can create this branch now and assign existing staff members to it, or upgrade your plan to add new user accounts.
                        </div>
                    @endif
                @endif

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a class="btn btn-outline-secondary" href="{{ route('branches.index') }}">Cancel</a>
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-check2-circle me-1"></i>{{ $branch->exists ? 'Update Branch' : 'Create Branch' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
