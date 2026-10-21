@extends('layouts.app')
@section('title', 'Add Supplier')
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card shadow-sm border-0 bg-white">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold">Add New Supplier</h5>
                        <!-- <small class="text-secondary">Register procurement partner, statutory tax numbers & liability ledger</small> -->
                    </div>
                    <a href="{{ route('purchases.suppliers.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Back to List
                    </a>
                </div>
            </div>
            <div class="card-body p-4">
                <form method="post" action="{{ route('purchases.suppliers.store') }}">
                    @csrf
                    <div class="row g-3">
                        {{-- Basic Information --}}
                        <div class="col-12">
                            <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-person-lines-fill me-1"></i> Contact & Business Profile
                            </h6>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Supplier / Contact Person <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" placeholder="e.g. Muhammad Ahmad" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Company / Business Name</label>
                            <input type="text" class="form-control @error('company_name') is-invalid @enderror" name="company_name" value="{{ old('company_name') }}" placeholder="e.g. Al-Madina Trading Co.">
                            @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone / WhatsApp</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone') }}" placeholder="0300-1234567">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email Address</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" placeholder="supplier@example.com">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Tax & Statutory Compliance --}}
                        <div class="col-12 mt-4">
                            <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-file-earmark-check me-1"></i> Tax &amp; Regulatory Numbers
                            </h6>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">National Tax Number (NTN)</label>
                            <input type="text" class="form-control @error('ntn') is-invalid @enderror" name="ntn" value="{{ old('ntn') }}" placeholder="e.g. 1234567-8">
                            @error('ntn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Sales Tax Reg. (STRN)</label>
                            <input type="text" class="form-control @error('strn') is-invalid @enderror" name="strn" value="{{ old('strn') }}" placeholder="e.g. 9876543-2">
                            @error('strn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Licence / Registration Number</label>
                            <input type="text" class="form-control @error('license_number') is-invalid @enderror" name="license_number" value="{{ old('license_number') }}" placeholder="e.g. DRAP-1029 / LIC-998">
                            @error('license_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Financial Accounts & Balances --}}
                        <div class="col-12 mt-4">
                            <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-journal-bookmark me-1"></i> Chart of Accounts (Liability Link)
                            </h6>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Liability Account (Accounts Payable)</label>
                            <select class="form-select @error('liability_account_id') is-invalid @enderror" name="liability_account_id">
                                <option value="">Select Liability Account (Default: Accounts Payable)</option>
                                @foreach($liabilityAccounts as $acc)
                                    <option value="{{ $acc->id }}" @selected(old('liability_account_id') == $acc->id)>
                                        {{ $acc->code }} - {{ $acc->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Links this vendor's credit bills & payments to your balance sheet liabilities.</small>
                            @error('liability_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Opening Balance (Rs.)</label>
                            <input type="number" step="0.01" class="form-control @error('opening_balance') is-invalid @enderror" name="opening_balance" value="{{ old('opening_balance', '0.00') }}" placeholder="0.00">
                            <small class="text-muted">Previous payable balance</small>
                            @error('opening_balance')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                                <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                                <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Address / Location</label>
                            <textarea class="form-control @error('address') is-invalid @enderror" name="address" rows="2" placeholder="Full office/warehouse address">{{ old('address') }}</textarea>
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 text-end mt-4">
                            <a href="{{ route('purchases.suppliers.index') }}" class="btn btn-light me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                <i class="bi bi-save me-1"></i> Save Supplier
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
