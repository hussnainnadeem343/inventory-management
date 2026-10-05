@extends('layouts.app')
@section('title', 'Record Expense')
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Record Daily Expense Voucher</h5>
                    <a href="{{ route('finance.expenses.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Back to Expenses
                    </a>
                </div>
            </div>
            <div class="card-body p-4">
                <form method="post" action="{{ route('finance.expenses.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Expense Account <span class="text-danger">*</span></label>
                            <select class="form-select @error('expense_account_id') is-invalid @enderror" name="expense_account_id" required>
                                <option value="">Select Expense Head</option>
                                @foreach($expenseAccounts as $acc)
                                    <option value="{{ $acc->id }}" @selected(old('expense_account_id') == $acc->id)>
                                        {{ $acc->code }} - {{ $acc->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('expense_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Paid From (Payment Account) <span class="text-danger">*</span></label>
                            <select class="form-select @error('payment_account_id') is-invalid @enderror" name="payment_account_id" required>
                                <option value="">Select Cash / Bank Account</option>
                                @foreach($paymentAccounts as $acc)
                                    <option value="{{ $acc->id }}" @selected(old('payment_account_id') == $acc->id)>
                                        {{ $acc->name }} (Bal: Rs. {{ number_format($acc->current_balance, 2) }})
                                    </option>
                                @endforeach
                            </select>
                            @error('payment_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('date') is-invalid @enderror" name="date" value="{{ old('date', date('Y-m-d')) }}" required>
                            @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Amount (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" class="form-control @error('amount') is-invalid @enderror" name="amount" value="{{ old('amount') }}" placeholder="0.00" required>
                            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Category / Tag</label>
                            <input type="text" class="form-control" name="category" value="{{ old('category') }}" placeholder="e.g. Refreshment, Fuel, Stationery">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Receipt / Bill Ref #</label>
                            <input type="text" class="form-control" name="receipt_ref" value="{{ old('receipt_ref') }}" placeholder="e.g. Bill #1234">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Expense Description / Purpose <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="3" placeholder="Provide details of the expense..." required>{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 text-end mt-4">
                            <a href="{{ route('finance.expenses.index') }}" class="btn btn-light me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-save me-1"></i> Record & Post Expense
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
