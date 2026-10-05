@extends('layouts.app')
@section('title', 'Payments & Receipts')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Payments & Receipts</h4>
        <small class="text-secondary">Vendor payments and customer payment vouchers</small>
    </div>
    @if(auth()->user()->hasPermission('accounts.vouchers'))
        <a class="btn btn-primary" href="{{ route('finance.payments.create') }}">
            <i class="bi bi-plus-lg me-1"></i> New Payment Voucher
        </a>
    @endif
</div>

<div class="card p-3 mb-3">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-lg-5 col-md-5">
            <label class="form-label">Search</label>
            <input class="form-control" name="search" value="{{ $search }}" placeholder="Voucher #, supplier name, reference...">
        </div>
        <div class="col-sm-6 col-lg-3 col-md-3">
            <label class="form-label">Voucher Type</label>
            <select class="form-select" name="type">
                <option value="">All Types</option>
                <option value="supplier_payment" @selected($type === 'supplier_payment')>Supplier Payment</option>
                <option value="customer_receipt" @selected($type === 'customer_receipt')>Customer Receipt</option>
            </select>
        </div>
        <div class="col-lg-4 d-flex gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-filter me-1"></i> Filter</button>
            <a class="btn btn-outline-secondary" href="{{ route('finance.payments.index') }}">Reset</a>
        </div>
    </form>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Voucher #</th>
                    <th>Type</th>
                    <th>Party / Vendor</th>
                    <th>Date</th>
                    <th>Payment Account</th>
                    <th>Method / Ref</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $pay)
                    <tr>
                        <td><span class="fw-bold font-monospace">{{ $pay->voucher_no }}</span></td>
                        <td>
                            @if($pay->type === 'supplier_payment')
                                <span class="badge bg-warning-subtle text-warning">Supplier Payment</span>
                            @else
                                <span class="badge bg-success-subtle text-success">Customer Receipt</span>
                            @endif
                        </td>
                        <td>
                            @if($pay->supplier)
                                <a href="{{ route('purchases.suppliers.show', $pay->supplier) }}" class="fw-semibold text-decoration-none">
                                    {{ $pay->supplier->name }}
                                </a>
                            @else
                                <span class="text-muted">General</span>
                            @endif
                        </td>
                        <td>{{ $pay->payment_date->format('d M Y') }}</td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $pay->paymentAccount->name }}</span>
                        </td>
                        <td>
                            <div>{{ ucfirst($pay->payment_method) }}</div>
                            @if($pay->reference_no)
                                <small class="text-muted">Ref: {{ $pay->reference_no }}</small>
                            @endif
                        </td>
                        <td class="text-end fw-bold text-dark fs-6">
                            Rs. {{ number_format($pay->amount, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No payment vouchers found.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <td colspan="6" class="text-end fw-bold">Total Vouchers Value:</td>
                    <td class="text-end fw-bold text-primary fs-6">Rs. {{ number_format($totalAmount, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @if($payments->hasPages())
        <div class="p-3 border-top">
            {{ $payments->links() }}
        </div>
    @endif
</div>
@endsection
