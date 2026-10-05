@extends('layouts.app')
@section('title', 'Ledger - ' . $account->name)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
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

<div class="card p-3 mb-3">
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
@endsection
