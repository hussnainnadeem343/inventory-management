@extends('layouts.app')
@section('title', 'JV ' . $journal->entry_number)
@section('content')
{{-- Printable Official Journal Voucher Header (Print Only) --}}
<div class="d-none d-print-block mb-4 pb-3 border-bottom">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h2 class="h4 fw-bold mb-1 text-dark">{{ auth()->user()->shop ? auth()->user()->shop->name : 'DenFin ERP' }}</h2>
            <div class="text-secondary small">
                @if(auth()->user()->shop && auth()->user()->shop->address)
                    <div><i class="bi bi-geo-alt me-1"></i>{{ auth()->user()->shop->address }}</div>
                @endif
                @if(auth()->user()->shop && auth()->user()->shop->phone)
                    <div><i class="bi bi-telephone me-1"></i>Phone: {{ auth()->user()->shop->phone }}</div>
                @endif
                <div>Branch Code: <strong>{{ auth()->user()->shop ? auth()->user()->shop->code : 'MAIN' }}</strong></div>
            </div>
        </div>
        <div class="text-end">
            <span class="badge border border-dark px-3 py-1 fs-6 text-uppercase fw-bold mb-1 d-inline-block">JOURNAL VOUCHER</span>
            <div class="font-monospace fw-bold fs-5 text-dark mt-1">{{ $journal->entry_number }}</div>
            <div class="small text-secondary">Entry Date: <strong>{{ $journal->entry_date->format('d M, Y') }}</strong></div>
            <div class="small text-secondary">Type: <strong class="text-uppercase">{{ str_replace('_', ' ', $journal->reference_type) }}</strong></div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
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

{{-- Printable Official Authorization Signatures Footer (Print Only) --}}
<div class="d-none d-print-block print-signature-block">
    <div class="row text-center">
        <div class="col-4">
            <div class="border-top border-dark pt-2 mx-3">
                <strong class="d-block text-dark small text-uppercase">Prepared By / Accountant</strong>
                <span class="text-secondary" style="font-size: 11px;">{{ $journal->creator?->name ?? auth()->user()->name }}</span>
            </div>
        </div>
        <div class="col-4">
            <div class="border-top border-dark pt-2 mx-3">
                <strong class="d-block text-dark small text-uppercase">Audited & Checked</strong>
                <span class="text-secondary" style="font-size: 11px;">Internal Audit Verification</span>
            </div>
        </div>
        <div class="col-4">
            <div class="border-top border-dark pt-2 mx-3">
                <strong class="d-block text-dark small text-uppercase">Finance Manager / Approved</strong>
                <span class="text-secondary" style="font-size: 11px;">Authorized Financial Approval</span>
            </div>
        </div>
    </div>
    <div class="text-center text-secondary mt-4" style="font-size: 10px;">
        Generated on {{ now()->format('d M, Y h:i A') }} &bull; DenFin ERP Enterprise Document &bull; Double-Entry Journal Voucher
    </div>
</div>
@endsection
