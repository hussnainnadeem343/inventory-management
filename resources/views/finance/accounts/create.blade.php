@extends('layouts.app')
@section('title', 'Add Ledger Account')
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Add Ledger Account</h5>
                    <a href="{{ route('finance.accounts.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Back to Accounts
                    </a>
                </div>
            </div>
            <div class="card-body p-4">
                <form method="post" action="{{ route('finance.accounts.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Account Category / Head <span class="text-danger">*</span></label>
                        <select class="form-select @error('account_head_id') is-invalid @enderror" name="account_head_id" required>
                            <option value="">Select Category</option>
                            @foreach($heads as $head)
                                <option value="{{ $head->id }}" @selected(old('account_head_id') == $head->id)>
                                    {{ $head->code }} - {{ $head->name }} ({{ ucfirst($head->nature) }})
                                </option>
                            @endforeach
                        </select>
                        @error('account_head_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Account Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('code') is-invalid @enderror" name="code" value="{{ old('code') }}" placeholder="e.g. 1003 or 5040" required>
                        <small class="text-muted">Unique numeric identifier for the account</small>
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Account Title / Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" placeholder="e.g. Marketing & Advertising Expense" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Opening Balance (Rs.)</label>
                        <input type="number" step="0.01" class="form-control @error('opening_balance') is-invalid @enderror" name="opening_balance" value="{{ old('opening_balance', '0.00') }}">
                        @error('opening_balance')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="text-end mt-4">
                        <a href="{{ route('finance.accounts.index') }}" class="btn btn-light me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-save me-1"></i> Save Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
