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

                        <div class="col-md-6">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select @error('role') is-invalid @enderror" name="role" required>
                                <option value="super_admin" @selected(old('role', $user->role) === 'super_admin')>Super Admin (Platform Owner)</option>
                                <option value="shop_admin" @selected(old('role', $user->role) === 'shop_admin')>Shop Admin (Store Owner / Manager)</option>
                                <option value="staff" @selected(old('role', $user->role ?? 'staff') === 'staff' || old('role', $user->role) === 'user')>Staff / Cashier</option>
                            </select>
                            @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @else
                        <div class="col-md-6">
                            <label class="form-label">Assigned Shop</label>
                            <input class="form-control bg-light text-secondary fw-semibold" value="{{ auth()->user()->shop->name ?? 'Your Shop' }} ({{ auth()->user()->shop->code ?? '' }})" readonly>
                            <div class="form-text">User will be assigned to your shop.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select @error('role') is-invalid @enderror" name="role" required>
                                <option value="shop_admin" @selected(old('role', $user->role) === 'shop_admin')>Shop Admin (Store Owner / Manager)</option>
                                <option value="staff" @selected(old('role', $user->role ?? 'staff') === 'staff' || old('role', $user->role) === 'user')>Staff / Cashier (Counter Sales)</option>
                            </select>
                            @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Choose whether this user is a Shop Admin or Staff.</div>
                        </div>
                    @endif

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
