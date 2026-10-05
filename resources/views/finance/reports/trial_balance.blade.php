@extends('layouts.app')
@section('title', 'Trial Balance')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Trial Balance</h4>
        <small class="text-secondary">Audit summary verifying Total Debit Balances equal Total Credit Balances</small>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-dark" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print Trial Balance
        </button>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 15%;">Account Code</th>
                    <th style="width: 45%;">Account Title</th>
                    <th style="width: 20%;" class="text-end">Debit Balance</th>
                    <th style="width: 20%;" class="text-end">Credit Balance</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $sumDebit = 0;
                    $sumCredit = 0;
                @endphp
                @foreach($accounts as $acc)
                    @php
                        $bal = (float) $acc->current_balance;
                        $dr = 0;
                        $cr = 0;

                        if ($acc->nature === 'debit') {
                            if ($bal >= 0) {
                                $dr = $bal;
                            } else {
                                $cr = abs($bal);
                            }
                        } else {
                            if ($bal >= 0) {
                                $cr = $bal;
                            } else {
                                $dr = abs($bal);
                            }
                        }

                        $sumDebit += $dr;
                        $sumCredit += $cr;
                    @endphp
                    <tr>
                        <td><span class="font-monospace text-muted">{{ $acc->code }}</span></td>
                        <td>
                            <a href="{{ route('finance.accounts.ledger', $acc) }}" class="fw-semibold text-decoration-none">
                                {{ $acc->name }}
                            </a>
                            <small class="text-secondary ms-1">({{ $acc->head->name }})</small>
                        </td>
                        <td class="text-end text-success fw-semibold">
                            {{ $dr > 0 ? 'Rs. ' . number_format($dr, 2) : '—' }}
                        </td>
                        <td class="text-end text-danger fw-semibold">
                            {{ $cr > 0 ? 'Rs. ' . number_format($cr, 2) : '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light fw-bold fs-6">
                <tr>
                    <td colspan="2" class="text-end">TOTALS:</td>
                    <td class="text-end text-success">Rs. {{ number_format($sumDebit, 2) }}</td>
                    <td class="text-end text-danger">Rs. {{ number_format($sumCredit, 2) }}</td>
                </tr>
                <tr class="table-active">
                    <td colspan="4" class="text-center">
                        @if(abs($sumDebit - $sumCredit) < 0.01)
                            <span class="badge bg-success-subtle text-success fs-6">
                                <i class="bi bi-check-circle me-1"></i> Perfect Balance (Debits Match Credits)
                            </span>
                        @else
                            <span class="badge bg-danger-subtle text-danger fs-6">
                                <i class="bi bi-exclamation-triangle me-1"></i> Discrepancy: Difference of Rs. {{ number_format(abs($sumDebit - $sumCredit), 2) }}
                            </span>
                        @endif
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
