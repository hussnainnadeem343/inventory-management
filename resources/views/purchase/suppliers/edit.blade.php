@extends('layouts.app')
@section('title', 'Edit Supplier')
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card shadow-sm border-0 bg-white">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold">Edit Supplier: {{ $supplier->name }}</h5>
                        <!-- <small class="text-secondary">Update vendor details, tax registrations, and balance sheet liability linkage</small> -->
                    </div>
                    <a href="{{ route('purchases.suppliers.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Back to List
                    </a>
                </div>
            </div>
            <div class="card-body p-4">
                <form method="post" action="{{ route('purchases.suppliers.update', $supplier) }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        {{-- Basic Information --}}
                        <div class="col-12">
                            <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-person-lines-fill me-1"></i> Contact & Business Profile
                            </h6>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Supplier / Contact Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $supplier->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Company / Business Name</label>
                            <input type="text" class="form-control @error('company_name') is-invalid @enderror" name="company_name" value="{{ old('company_name', $supplier->company_name) }}">
                            @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone / WhatsApp</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $supplier->phone) }}">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email Address</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $supplier->email) }}">
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
                            <input type="text" class="form-control @error('ntn') is-invalid @enderror" name="ntn" value="{{ old('ntn', $supplier->ntn) }}" placeholder="e.g. 1234567-8">
                            @error('ntn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Sales Tax Reg. (STRN)</label>
                            <input type="text" class="form-control @error('strn') is-invalid @enderror" name="strn" value="{{ old('strn', $supplier->strn) }}" placeholder="e.g. 9876543-2">
                            @error('strn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Licence / Registration Number</label>
                            <input type="text" class="form-control @error('license_number') is-invalid @enderror" name="license_number" value="{{ old('license_number', $supplier->license_number) }}" placeholder="e.g. DRAP-1029 / LIC-998">
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
                                    <option value="{{ $acc->id }}" @selected(old('liability_account_id', $supplier->liability_account_id) == $acc->id)>
                                        {{ $acc->code }} - {{ $acc->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Links this supplier's credit bills & payments to your balance sheet liabilities.</small>
                            @error('liability_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Current Balance (Rs.)</label>
                            <input type="text" class="form-control bg-light fw-bold" value="Rs. {{ number_format($supplier->current_balance, 2) }}" readonly disabled>
                            <!-- <small class="text-muted">Auto-computed via invoices & payments</small> -->
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                                <option value="active" @selected(old('status', $supplier->status) === 'active')>Active</option>
                                <option value="inactive" @selected(old('status', $supplier->status) === 'inactive')>Inactive</option>
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Address / Location</label>
                            <textarea class="form-control @error('address') is-invalid @enderror" name="address" rows="2">{{ old('address', $supplier->address) }}</textarea>
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 text-end mt-4">
                            <a href="{{ route('purchases.suppliers.index') }}" class="btn btn-light me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                <i class="bi bi-save me-1"></i> Update Supplier
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
