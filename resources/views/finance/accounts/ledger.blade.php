@extends('layouts.app')
@section('title', 'Ledger - ' . $account->name)
@section('content')
{{-- Printable Official Statement of Account Header (Print Only) --}}
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
            <span class="badge border border-dark px-3 py-1 fs-6 text-uppercase fw-bold mb-1 d-inline-block">STATEMENT OF ACCOUNT / LEDGER</span>
            <div class="font-monospace fw-bold fs-5 text-dark mt-1">{{ $account->name }} ({{ $account->code }})</div>
            <div class="small text-secondary">Account Head: <strong>{{ $account->head->name }}</strong></div>
            <div class="small text-secondary">Period: <strong>{{ \Carbon\Carbon::parse($startDate)->format('d M, Y') }} &mdash; {{ \Carbon\Carbon::parse($endDate)->format('d M, Y') }}</strong></div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h4 class="mb-0 fw-bold">{{ $account->name }}</h4>
            <span class="badge bg-light text-dark border font-monospace">{{ $account->code }}</span>
            <span class="badge bg-primary-subtle text-primary">{{ $account->head->name }}</span>
        </div>
        <small class="text-secondary">Statement of Account transactions and running balance</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('finance.accounts.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <button class="btn btn-outline-dark" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print Statement
        </button>
    </div>
</div>

<div class="card p-3 mb-3 d-print-none">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label">From Date</label>
            <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">To Date</label>
            <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-filter me-1"></i> Filter Ledger</button>
            <a class="btn btn-outline-secondary" href="{{ route('finance.accounts.ledger', $account) }}">Reset</a>
        </div>
    </form>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Voucher #</th>
                    <th>Description / Narration</th>
                    <th class="text-end">Debit</th>
                    <th class="text-end">Credit</th>
                    <th class="text-end">Running Balance</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $runningBalance = (float) $account->opening_balance;
                    $totalDebit = 0;
                    $totalCredit = 0;
                @endphp
                <tr class="table-light">
                    <td>—</td>
                    <td>—</td>
                    <td><em>Opening Balance</em></td>
                    <td class="text-end">—</td>
                    <td class="text-end">—</td>
                    <td class="text-end fw-bold">Rs. {{ number_format($runningBalance, 2) }}</td>
                </tr>

                @forelse($items as $line)
                    @php
                        $dr = (float) $line->debit;
                        $cr = (float) $line->credit;
                        $totalDebit += $dr;
                        $totalCredit += $cr;

                        if ($account->nature === 'debit') {
                            $runningBalance += ($dr - $cr);
                        } else {
                            $runningBalance += ($cr - $dr);
                        }
                    @endphp
                    <tr>
                        <td>{{ $line->journalEntry->entry_date->format('d M Y') }}</td>
                        <td>
                            <a href="{{ route('finance.journal.show', $line->journalEntry) }}" class="fw-semibold text-decoration-none">
                                {{ $line->journalEntry->entry_number }}
                            </a>
                        </td>
                        <td>
                            <div>{{ $line->journalEntry->description }}</div>
                            @if($line->narration)
                                <small class="text-muted">{{ $line->narration }}</small>
                            @endif
                        </td>
                        <td class="text-end text-success fw-semibold">
                            {{ $dr > 0 ? 'Rs. ' . number_format($dr, 2) : '—' }}
                        </td>
                        <td class="text-end text-danger fw-semibold">
                            {{ $cr > 0 ? 'Rs. ' . number_format($cr, 2) : '—' }}
                        </td>
                        <td class="text-end fw-bold">
                            Rs. {{ number_format($runningBalance, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No transactions found for the selected date range.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="3" class="text-end">Period Totals:</td>
                    <td class="text-end text-success">Rs. {{ number_format($totalDebit, 2) }}</td>
                    <td class="text-end text-danger">Rs. {{ number_format($totalCredit, 2) }}</td>
                    <td class="text-end text-primary">Rs. {{ number_format($runningBalance, 2) }}</td>
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
                <strong class="d-block text-dark small text-uppercase">Generated By</strong>
                <span class="text-secondary" style="font-size: 11px;">{{ auth()->user()->name }}</span>
            </div>
        </div>
        <div class="col-4">
            <div class="border-top border-dark pt-2 mx-3">
                <strong class="d-block text-dark small text-uppercase">Accounts Incharge</strong>
                <span class="text-secondary" style="font-size: 11px;">Audit & Verification</span>
            </div>
        </div>
        <div class="col-4">
            <div class="border-top border-dark pt-2 mx-3">
                <strong class="d-block text-dark small text-uppercase">Finance Director</strong>
                <span class="text-secondary" style="font-size: 11px;">Official Sign & Seal</span>
            </div>
        </div>
    </div>
    <div class="text-center text-secondary mt-4" style="font-size: 10px;">
        Generated on {{ now()->format('d M, Y h:i A') }} &bull; DenFin ERP Enterprise Document &bull; Official Account Ledger Statement
    </div>
</div>
@endsection
