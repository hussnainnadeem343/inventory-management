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
                            $currentType = old('business_type', $shop->business_type ?? 'General Retail');
                            $presets = [
                                'General Retail' => 'General Retail / Counter POS',
                                'Lube (Oil Changing)' => 'Lube (Oil Changing) Station',
                                'Cosmetics & Beauty' => 'Cosmetics & Beauty',
                                'Pharmacy / Healthcare' => 'Pharmacy & Healthcare',
                                'Auto Spare Parts' => 'Auto Spare Parts & Hardware',
                                'Footwear & Apparel' => 'Footwear & Apparel',
                                'Electronics & Mobile' => 'Electronics & Mobile',
                            ];
                            $isOther = !empty($currentType) && !array_key_exists($currentType, $presets) && !$shop->isLube();
                        @endphp
                        <select name="business_type" class="form-select @error('business_type') is-invalid @enderror" id="shopBusinessTypeSelect" onchange="if(this.value==='Other'){document.getElementById('customBusinessTypeWrap').classList.remove('d-none'); document.getElementById('customBusinessTypeInput').required=true; document.getElementById('customBusinessTypeInput').focus();}else{document.getElementById('customBusinessTypeWrap').classList.add('d-none'); document.getElementById('customBusinessTypeInput').required=false;}">
                            @foreach($presets as $val => $lbl)
                                <option value="{{ $val }}" @selected($currentType === $val || ($val === 'Lube (Oil Changing)' && $shop->isLube()))>{{ $lbl }}</option>
                            @endforeach
                            <option value="Other" @selected($isOther)>Other (Custom)...</option>
                        </select>
                        <div id="customBusinessTypeWrap" class="{{ $isOther ? '' : 'd-none' }} mt-2">
                            <input type="text" id="customBusinessTypeInput" name="custom_business_type" class="form-control form-control-sm" placeholder="Please specify your custom business type" value="{{ old('custom_business_type', $isOther ? $currentType : '') }}">
                        </div>
                        <small class="text-muted d-block mt-1">Choosing <strong>Lube (Oil Changing)</strong> activates vehicle history, pit technicians, and auto lube counter POS.</small>
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
