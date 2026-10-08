@extends('layouts.app')
@section('title', 'New Payment Voucher')
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Record Payment / Receipt Voucher</h5>
                    <a href="{{ route('finance.payments.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Back to Payments
                    </a>
                </div>
            </div>
            <div class="card-body p-4">
                <form method="post" action="{{ route('finance.payments.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Voucher Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('type') is-invalid @enderror" name="type" id="voucherType" required>
                                <option value="supplier_payment" @selected(old('type') === 'supplier_payment')>Supplier / Vendor Payment</option>
                                <option value="customer_receipt" @selected(old('type') === 'customer_receipt')>Customer Receipt</option>
                            </select>
                            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6" id="supplierBox">
                            <label class="form-label fw-semibold">Select Supplier / Vendor</label>
                            <select class="form-select searchable-select" name="supplier_id">
                                <option value="">Select Vendor</option>
                                @foreach($suppliers as $sup)
                                    <option value="{{ $sup->id }}" @selected(old('supplier_id') == $sup->id)>
                                        {{ $sup->name }} (Payable: Rs. {{ number_format($sup->current_balance, 2) }})
                                    </option>
                                @endforeach
                            </select>
                            @error('supplier_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-semibold mb-0">Paid Through Account <span class="text-danger">*</span></label>
                                <a href="{{ route('finance.accounts.create') }}" target="_blank" class="small text-decoration-none text-primary">
                                    <i class="bi bi-plus-circle me-1"></i>+ Add Account
                                </a>
                            </div>
                            <select class="form-select @error('payment_account_id') is-invalid @enderror" name="payment_account_id" required>
                                <option value="">Select Cash or Bank Account</option>
                                @foreach($paymentAccounts as $acc)
                                    <option value="{{ $acc->id }}" @selected(old('payment_account_id') == $acc->id)>
                                        {{ $acc->name }} (Bal: Rs. {{ number_format($acc->current_balance, 2) }})
                                    </option>
                                @endforeach
                            </select>
                            @error('payment_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('payment_date') is-invalid @enderror" name="payment_date" value="{{ old('payment_date', date('Y-m-d')) }}" required>
                            @error('payment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Amount (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" class="form-control @error('amount') is-invalid @enderror" name="amount" value="{{ old('amount') }}" placeholder="0.00" required>
                            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
                            <select class="form-select @error('payment_method') is-invalid @enderror" name="payment_method" required>
                                <option value="cash" @selected(old('payment_method') === 'cash')>Cash</option>
                                <option value="bank_transfer" @selected(old('payment_method') === 'bank_transfer')>Bank Transfer / Online</option>
                                <option value="cheque" @selected(old('payment_method') === 'cheque')>Cheque</option>
                                <option value="card" @selected(old('payment_method') === 'card')>Debit/Credit Card</option>
                            </select>
                            @error('payment_method')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Cheque / Transaction Ref #</label>
                            <input type="text" class="form-control" name="reference_no" value="{{ old('reference_no') }}" placeholder="e.g. Chq #009212 or Bank Ref ID">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Notes / Remarks</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="Settlement against invoices...">{{ old('notes') }}</textarea>
                        </div>

                        <div class="col-12 text-end mt-4">
                            <a href="{{ route('finance.payments.index') }}" class="btn btn-light me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check2-circle me-1"></i> Record & Post Payment
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('voucherType').addEventListener('change', function() {
        const box = document.getElementById('supplierBox');
        if (this.value === 'supplier_payment') {
            box.style.display = 'block';
        } else {
            box.style.display = 'none';
        }
    });
</script>
@endpush
@endsection
