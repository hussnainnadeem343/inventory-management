@extends('layouts.app')
@section('title', 'Daily Expenses')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Daily Shop Expenses</h4>
        <small class="text-secondary">Track operational, utility, and petty cash disbursements</small>
    </div>
    @if(auth()->user()->hasPermission('accounts.vouchers'))
        <a class="btn btn-primary" href="{{ route('finance.expenses.create') }}">
            <i class="bi bi-plus-lg me-1"></i> Record Expense
        </a>
    @endif
</div>

<div class="card p-3 mb-3">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-lg-4 col-md-4">
            <label class="form-label">Search</label>
            <input class="form-control" name="search" value="{{ $search }}" placeholder="Voucher #, description, category...">
        </div>
        <div class="col-sm-6 col-lg-3 col-md-3">
            <label class="form-label">From Date</label>
            <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
        </div>
        <div class="col-sm-6 col-lg-3 col-md-3">
            <label class="form-label">To Date</label>
            <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
        </div>
        <div class="col-lg-2 d-flex gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-filter me-1"></i> Filter</button>
            <a class="btn btn-outline-secondary" href="{{ route('finance.expenses.index') }}">Reset</a>
        </div>
    </form>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Voucher #</th>
                    <th>Date</th>
                    <th>Expense Head</th>
                    <th>Paid From</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $exp)
                    <tr>
                        <td><span class="fw-bold font-monospace">{{ $exp->voucher_no }}</span></td>
                        <td>{{ $exp->date->format('d M Y') }}</td>
                        <td>
                            <span class="badge bg-danger-subtle text-danger">{{ $exp->expenseAccount->name }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $exp->paymentAccount->name }}</span>
                        </td>
                        <td>{{ $exp->category ?? 'General' }}</td>
                        <td>{{ $exp->description }}</td>
                        <td class="text-end fw-bold text-danger">Rs. {{ number_format($exp->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No expense records found.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <td colspan="6" class="text-end fw-bold">Total Expenses:</td>
                    <td class="text-end fw-bold text-danger fs-6">Rs. {{ number_format($totalAmount, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @if($expenses->hasPages())
        <div class="p-3 border-top">
            {{ $expenses->links() }}
        </div>
    @endif
</div>
@endsection
