@extends('layouts.app')
@section('title', 'Balance Sheet')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Statement of Financial Position (Balance Sheet)</h4>
        <small class="text-secondary">Summary of Assets, Liabilities, and Equity as of {{ \Carbon\Carbon::parse($asOfDate)->format('d M Y') }}</small>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-dark" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print Balance Sheet
        </button>
    </div>
</div>

<div class="card p-3 mb-4">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label">As of Date</label>
            <input type="date" class="form-control" name="as_of_date" value="{{ $asOfDate }}">
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-filter me-1"></i> Update</button>
            <a class="btn btn-outline-secondary" href="{{ route('finance.reports.balance-sheet') }}">Today</a>
        </div>
    </form>
</div>

<div class="row g-3">
    <!-- Assets Side -->
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-primary text-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-wallet2 me-1"></i> Assets (What We Own)</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <tbody>
                        @forelse($assetAccounts as $acc)
                            <tr>
                                <td style="width: 70%;" class="ps-3">
                                    <div class="fw-semibold">{{ $acc->name }}</div>
                                    <small class="text-muted">{{ $acc->code }}</small>
                                </td>
                                <td style="width: 30%;" class="text-end pe-3 fw-bold">Rs. {{ number_format($acc->current_balance, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="ps-3 text-muted py-3">No asset accounts recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td class="ps-3 fs-6">TOTAL ASSETS:</td>
                            <td class="text-end pe-3 text-primary fs-6">Rs. {{ number_format($totalAssets, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Liabilities & Equity Side -->
    <div class="col-lg-6">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-danger text-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-cash-stack me-1"></i> Liabilities (What We Owe)</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <tbody>
                        @forelse($liabilityAccounts as $acc)
                            <tr>
                                <td style="width: 70%;" class="ps-3">
                                    <div class="fw-semibold">{{ $acc->name }}</div>
                                    <small class="text-muted">{{ $acc->code }}</small>
                                </td>
                                <td style="width: 30%;" class="text-end pe-3 fw-bold">Rs. {{ number_format($acc->current_balance, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="ps-3 text-muted py-3">No liabilities recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td class="ps-3">TOTAL LIABILITIES:</td>
                            <td class="text-end pe-3 text-danger">Rs. {{ number_format($totalLiabilities, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-success text-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-bank me-1"></i> Equity (Capital)</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <tbody>
                        @forelse($equityAccounts as $acc)
                            <tr>
                                <td style="width: 70%;" class="ps-3">
                                    <div class="fw-semibold">{{ $acc->name }}</div>
                                    <small class="text-muted">{{ $acc->code }}</small>
                                </td>
                                <td style="width: 30%;" class="text-end pe-3 fw-bold">Rs. {{ number_format($acc->current_balance, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="ps-3 text-muted py-3">No equity accounts recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td class="ps-3">TOTAL EQUITY:</td>
                            <td class="text-end pe-3 text-success">Rs. {{ number_format($totalEquity, 2) }}</td>
                        </tr>
                        <tr class="table-active fw-bold border-top border-2">
                            <td class="ps-3 fs-6">TOTAL LIABILITIES & EQUITY:</td>
                            <td class="text-end pe-3 text-dark fs-6">Rs. {{ number_format($totalLiabilities + $totalEquity, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
