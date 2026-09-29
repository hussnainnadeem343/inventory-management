@extends('layouts.app')
@section('title', $user->exists ? 'Edit User' : (auth()->user()->isSuperAdmin() ? 'Add User' : 'Add Staff Member'))
@section('content')
<x-errors />
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card p-4">
            <h2 class="h5 mb-3">{{ $user->exists ? 'Edit Account' : (auth()->user()->isSuperAdmin() ? 'Register New User' : 'Register New Staff Member') }}</h2>
            <form method="post" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}">
                @csrf
                @if($user->exists)
                    @method('put')
                @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $user->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input class="form-control font-monospace @error('username') is-invalid @enderror" name="username" value="{{ old('username', $user->username) }}" autocomplete="username" required>
                        @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Email (Optional)</label>
                        <input class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email', $user->email) }}">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    @php
                        $selectedRole = old('assigned_role');
                        if ($selectedRole === null) {
                            if ($user->exists) {
                                if ($user->role_id) {
                                    $selectedRole = 'role_' . $user->role_id;
                                } elseif ($user->role === 'super_admin') {
                                    $selectedRole = 'system:super_admin';
                                } elseif ($user->role === 'shop_admin') {
                                    $selectedRole = 'system:shop_admin';
                                } else {
                                    $selectedRole = 'system:staff';
                                }
                            } else {
                                if (old('role_id')) {
                                    $selectedRole = 'role_' . old('role_id');
                                } elseif (old('role')) {
                                    $selectedRole = match(old('role')) {
                                        'super_admin' => 'system:super_admin',
                                        'shop_admin' => 'system:shop_admin',
                                        default => 'system:staff',
                                    };
                                } else {
                                    $selectedRole = 'system:staff';
                                }
                            }
                        }
                    @endphp

                    @if(auth()->user()->isSuperAdmin())
                        <div class="col-md-6">
                            <label class="form-label">Assigned Shop</label>
                            <select class="form-select @error('shop_id') is-invalid @enderror" name="shop_id">
                                <option value="">-- No Shop (System / Global) --</option>
                                @foreach($shops as $shop)
                                    <option value="{{ $shop->id }}" @selected(old('shop_id', $user->shop_id) == $shop->id)>
                                        {{ $shop->name }} ({{ $shop->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('shop_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Super Admins do not need an assigned shop.</div>
                        </div>
                    @else
                        <div class="col-md-6">
                            <label class="form-label">Assigned Shop</label>
                            <input class="form-control bg-light text-secondary fw-semibold" value="{{ auth()->user()->shop->name ?? 'Your Shop' }} ({{ auth()->user()->shop->code ?? '' }})" readonly>
                            <div class="form-text">User will be assigned to your shop.</div>
                        </div>
                    @endif

                    <div class="col-md-6">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select @if($errors->has('assigned_role') || $errors->has('role') || $errors->has('role_id')) is-invalid @endif" name="assigned_role" required>
                            @if(auth()->user()->isSuperAdmin())
                                <optgroup label="System Roles">
                                    <option value="system:super_admin" @selected($selectedRole === 'system:super_admin')>Super Admin (Platform Owner)</option>
                                    <option value="system:shop_admin" @selected($selectedRole === 'system:shop_admin')>Shop Admin (Store Owner / Manager)</option>
                                    <option value="system:staff" @selected($selectedRole === 'system:staff')>Staff / Cashier (Standard)</option>
                                </optgroup>
                            @else
                                <optgroup label="System Roles">
                                    <option value="system:shop_admin" @selected($selectedRole === 'system:shop_admin')>Shop Admin (Full Store Access)</option>
                                    <option value="system:staff" @selected($selectedRole === 'system:staff')>Staff / Cashier (Standard)</option>
                                </optgroup>
                            @endif

                            @if($customRoles->isNotEmpty())
                                <optgroup label="Custom Roles (Shop Permissions)">
                                    @foreach($customRoles as $cr)
                                        <option value="role_{{ $cr->id }}" @selected($selectedRole === 'role_' . $cr->id)>
                                            {{ $cr->name }} ({{ $cr->permissions_count ?? $cr->permissions->count() }} permissions)
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                        @error('assigned_role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @error('role_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Select either a standard system role or an existing custom role directly.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                            <option value="active" @selected(old('status', $user->status ?? 'active') === 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $user->status) === 'inactive')>Inactive</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Password {{ $user->exists ? '(leave blank to keep current)' : '*' }}</label>
                        <input class="form-control @error('password') is-invalid @enderror" type="password" name="password" {{ $user->exists ? '' : 'required' }}>
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Confirm Password</label>
                        <input class="form-control" type="password" name="password_confirmation" {{ $user->exists ? '' : 'required' }}>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a class="btn btn-outline-secondary" href="{{ route('users.index') }}">Cancel</a>
                    <button class="btn btn-primary" type="submit">{{ $user->exists ? 'Update User' : 'Save User' }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
