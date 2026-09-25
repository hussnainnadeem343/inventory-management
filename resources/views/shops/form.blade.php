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
                        <label class="form-label">Business Type</label>
                        <input class="form-control @error('business_type') is-invalid @enderror" name="business_type" value="{{ old('business_type', $shop->business_type) }}" placeholder="e.g. Cosmetics, Shoes, Watches, General">
                        @error('business_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone Number</label>
                        <input class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $shop->phone) }}" placeholder="e.g. +92 300 1234567">
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
                </div>

                @if(! $shop->exists)
                    <hr class="my-4">
                    <h3 class="h6 mb-2 text-primary"><i class="bi bi-person-badge me-1"></i>Initial Shop Admin Account (Optional)</h3>
                    <p class="text-muted small mb-3">You can create the initial Shop Admin login for this shop right now.</p>

                    <div class="row g-3">
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
