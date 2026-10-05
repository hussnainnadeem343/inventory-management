@extends('layouts.app')
@section('title', 'Journal Vouchers')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">General Journal Vouchers (JV)</h4>
        <small class="text-secondary">Complete audit log of double-entry financial transactions</small>
    </div>
    @if(auth()->user()->hasPermission('accounts.vouchers'))
        <a class="btn btn-primary" href="{{ route('finance.journal.create') }}">
            <i class="bi bi-plus-lg me-1"></i> New Journal Voucher
        </a>
    @endif
</div>

<div class="card p-3 mb-3">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-lg-5 col-md-5">
            <label class="form-label">Search</label>
            <input class="form-control" name="search" value="{{ $search }}" placeholder="JV number or description...">
        </div>
        <div class="col-sm-6 col-lg-3 col-md-3">
            <label class="form-label">Source / Type</label>
            <select class="form-select" name="reference_type">
                <option value="">All Sources</option>
                <option value="sale" @selected($refType === 'sale')>Sales & POS</option>
                <option value="purchase_grn" @selected($refType === 'purchase_grn')>Purchase (GRN)</option>
                <option value="payment" @selected($refType === 'payment')>Payment</option>
                <option value="expense" @selected($refType === 'expense')>Expense</option>
                <option value="manual" @selected($refType === 'manual')>Manual JV</option>
            </select>
        </div>
        <div class="col-lg-4 d-flex gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-filter me-1"></i> Filter</button>
            <a class="btn btn-outline-secondary" href="{{ route('finance.journal.index') }}">Reset</a>
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
                    <th>Source Type</th>
                    <th>Description</th>
                    <th class="text-end">Total Debit</th>
                    <th class="text-end">Total Credit</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $jv)
                    <tr>
                        <td>
                            <a href="{{ route('finance.journal.show', $jv) }}" class="fw-bold font-monospace text-decoration-none">
                                {{ $jv->entry_number }}
                            </a>
                        </td>
                        <td>{{ $jv->entry_date->format('d M Y') }}</td>
                        <td>
                            @php
                                $badgeClass = match($jv->reference_type) {
                                    'sale' => 'bg-info-subtle text-info',
                                    'purchase_grn' => 'bg-primary-subtle text-primary',
                                    'payment' => 'bg-warning-subtle text-warning',
                                    'expense' => 'bg-danger-subtle text-danger',
                                    default => 'bg-secondary-subtle text-secondary',
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }}">{{ ucfirst(str_replace('_', ' ', $jv->reference_type)) }}</span>
                        </td>
                        <td>{{ $jv->description }}</td>
                        <td class="text-end fw-semibold text-success">Rs. {{ number_format($jv->total_debit, 2) }}</td>
                        <td class="text-end fw-semibold text-danger">Rs. {{ number_format($jv->total_credit, 2) }}</td>
                        <td class="text-end">
                            <a class="btn btn-outline-secondary btn-sm" href="{{ route('finance.journal.show', $jv) }}">
                                <i class="bi bi-eye me-1"></i> View JV
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No journal entries found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($entries->hasPages())
        <div class="p-3 border-top">
            {{ $entries->links() }}
        </div>
    @endif
</div>
@endsection
