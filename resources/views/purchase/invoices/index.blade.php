@extends('layouts.app')
@section('title', 'Purchase Invoices')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Purchase Invoices (Vendor Bills)</h4>
        <small class="text-secondary">Stage 5: Official commercial bills, landed costs & AP ledger liability</small>
    </div>
    @if(auth()->user()->hasPermission('purchases.invoices') || auth()->user()->isShopAdmin() || auth()->user()->isSuperAdmin())
        <a class="btn btn-primary" href="{{ route('purchases.invoices.create') }}">
            <i class="bi bi-plus-lg me-1"></i> New Purchase Invoice
        </a>
    @endif
</div>

<div class="card shadow-sm border-0 mb-3 bg-white">
    <div class="card-body py-2 px-3">
        <form method="get" action="{{ route('purchases.invoices.index') }}" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0 text-secondary"><i class="bi bi-search"></i></span>
                    <input type="search" name="search" class="form-control border-start-0" placeholder="Search Invoice #, manual #, vendor, supplier bill #..." value="{{ $search }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="payment_status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Payment Statuses</option>
                    <option value="unpaid" @selected($paymentStatus === 'unpaid')>Unpaid</option>
                    <option value="partially_paid" @selected($paymentStatus === 'partially_paid')>Partially Paid</option>
                    <option value="paid" @selected($paymentStatus === 'paid')>Paid</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
                @if($search || $paymentStatus)
                    <a href="{{ route('purchases.invoices.index') }}" class="btn btn-sm btn-link text-decoration-none text-muted">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="small text-secondary bg-light">
                <tr>
                    <th class="ps-3">Invoice Number</th>
                    <th>Date</th>
                    <th>Supplier / Vendor</th>
                    <th>Supplier Bill #</th>
                    <th>Type</th>
                    <th class="text-end">Grand Total</th>
                    <th>Status</th>
                    <th class="text-end pe-3">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $inv)
                    <tr>
                        <td class="ps-3">
                            <a href="{{ route('purchases.invoices.show', $inv) }}" class="fw-bold text-decoration-none">
                                {{ $inv->invoice_number }}
                            </a>
                            @if($inv->manual_invoice_number)
                                <div class="small text-muted">M-Inv: {{ $inv->manual_invoice_number }}</div>
                            @endif
                        </td>
                        <td>{{ $inv->invoice_date->format('d M, Y') }}</td>
                        <td class="fw-semibold">
                            {{ $inv->supplier->name }}
                            @if($inv->goodsReceivedNote)
                                <div class="small text-muted">GRN: {{ $inv->goodsReceivedNote->grn_number }}</div>
                            @endif
                        </td>
                        <td>{{ $inv->supplier_invoice_no ?? '-' }}</td>
                        <td>
                            <span class="badge bg-light text-dark border text-uppercase">{{ $inv->invoice_type }}</span>
                        </td>
                        <td class="text-end fw-bold text-primary">
                            Rs. {{ number_format($inv->grand_total, 2) }}
                        </td>
                        <td>
                            @php
                                $statusBadges = [
                                    'unpaid' => ['danger', 'Unpaid'],
                                    'partially_paid' => ['warning', 'Partially Paid'],
                                    'paid' => ['success', 'Paid'],
                                ];
                                $sb = $statusBadges[$inv->payment_status] ?? ['secondary', ucfirst($inv->payment_status)];
                            @endphp
                            <span class="badge bg-{{ $sb[0] }}-subtle text-{{ $sb[0] }}">
                                {{ $sb[1] }}
                            </span>
                        </td>
                        <td class="text-end pe-3">
                            <div class="btn-group btn-group-sm">
                                <a class="btn btn-outline-secondary" href="{{ route('purchases.invoices.show', $inv) }}">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if($inv->payment_status !== 'paid')
                                    <a class="btn btn-outline-success" href="{{ route('finance.payments.create', ['supplier_id' => $inv->supplier_id]) }}" title="Pay Vendor Voucher">
                                        <i class="bi bi-credit-card"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-receipt fs-3 d-block mb-2"></i>
                            No Purchase Invoices recorded yet. Click <strong>New Purchase Invoice</strong> to create a vendor bill.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($invoices->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $invoices->links() }}
        </div>
    @endif
</div>
@endsection
