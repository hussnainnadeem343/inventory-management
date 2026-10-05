@extends('layouts.app')
@section('title', 'JV ' . $journal->entry_number)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h4 class="mb-0 fw-bold">{{ $journal->entry_number }}</h4>
            <span class="badge bg-secondary-subtle text-secondary fs-6">{{ ucfirst(str_replace('_', ' ', $journal->reference_type)) }}</span>
        </div>
        <small class="text-secondary">Posted on {{ $journal->entry_date->format('d M Y') }}</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('finance.journal.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <button class="btn btn-outline-dark" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print Voucher
        </button>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <strong>Voucher Number:</strong> {{ $journal->entry_number }}
            </div>
            <div class="col-md-4">
                <strong>Entry Date:</strong> {{ $journal->entry_date->format('d M Y') }}
            </div>
            <div class="col-md-4">
                <strong>Created By:</strong> {{ $journal->creator ? $journal->creator->name : 'System' }}
            </div>
            <div class="col-12 border-top pt-2">
                <strong>Description:</strong> {{ $journal->description }}
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold">Journal Voucher Line Items</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Account Code & Title</th>
                    <th>Head / Category</th>
                    <th>Narration</th>
                    <th class="text-end">Debit</th>
                    <th class="text-end">Credit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($journal->items as $item)
                    <tr>
                        <td>
                            <a href="{{ route('finance.accounts.ledger', $item->account) }}" class="fw-semibold text-decoration-none">
                                {{ $item->account->code }} - {{ $item->account->name }}
                            </a>
                        </td>
                        <td><small class="text-secondary">{{ $item->account->head->name }}</small></td>
                        <td>{{ $item->narration ?? '—' }}</td>
                        <td class="text-end text-success fw-semibold">
                            {{ $item->debit > 0 ? 'Rs. ' . number_format($item->debit, 2) : '—' }}
                        </td>
                        <td class="text-end text-danger fw-semibold">
                            {{ $item->credit > 0 ? 'Rs. ' . number_format($item->credit, 2) : '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="3" class="text-end">Voucher Total:</td>
                    <td class="text-end text-success fs-6">Rs. {{ number_format($journal->total_debit, 2) }}</td>
                    <td class="text-end text-danger fs-6">Rs. {{ number_format($journal->total_credit, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
