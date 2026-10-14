@extends('layouts.app')
@section('title', 'Profit & Loss Statement')
@section('content')
{{-- Printable Official P&L Header (Print Only) --}}
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
            <span class="badge border border-dark px-3 py-1 fs-6 text-uppercase fw-bold mb-1 d-inline-block">INCOME STATEMENT (P&L)</span>
            <div class="small text-secondary mt-1">Period: <strong>{{ \Carbon\Carbon::parse($startDate)->format('d M, Y') }} &mdash; {{ \Carbon\Carbon::parse($endDate)->format('d M, Y') }}</strong></div>
            <div class="small text-secondary">Statement of Profit & Loss</div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
    <div>
        <h4 class="mb-0 fw-bold">Income Statement (Profit & Loss)</h4>
        <small class="text-secondary">Summary of revenues, cost of goods sold, and net income</small>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-dark" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print P&L
        </button>
    </div>
</div>

<div class="card p-3 mb-4 d-print-none">
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
            <button class="btn btn-primary" type="submit"><i class="bi bi-filter me-1"></i> Generate Report</button>
            <a class="btn btn-outline-secondary" href="{{ route('finance.reports.profit-loss') }}">Current Month</a>
        </div>
    </form>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <!-- 1. Revenue -->
        <h6 class="fw-bold text-uppercase text-primary border-bottom pb-2 mb-3">1. Revenue / Sales Income</h6>
        <div class="table-responsive mb-4">
            <table class="table table-sm align-middle mb-0">
                <tbody>
                    @foreach($revenueAccounts as $rev)
                        <tr>
                            <td style="width: 70%;" class="ps-3">{{ $rev->name }} ({{ $rev->code }})</td>
                            <td style="width: 30%;" class="text-end fw-semibold">Rs. {{ number_format($rev->period_total, 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="table-light fw-bold">
                        <td class="ps-3">Total Revenue:</td>
                        <td class="text-end text-success fs-6">Rs. {{ number_format($totalRevenue, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- 2. Cost of Goods Sold -->
        <h6 class="fw-bold text-uppercase text-secondary border-bottom pb-2 mb-3">2. Cost of Goods Sold (COGS)</h6>
        <div class="table-responsive mb-4">
            <table class="table table-sm align-middle mb-0">
                <tbody>
                    <tr>
                        <td style="width: 70%;" class="ps-3">Cost of Inventory Sold</td>
                        <td style="width: 30%;" class="text-end fw-semibold text-danger">Rs. {{ number_format($cogsTotal, 2) }}</td>
                    </tr>
                    <tr class="table-light fw-bold">
                        <td class="ps-3">Gross Profit (Revenue - COGS):</td>
                        <td class="text-end {{ $grossProfit >= 0 ? 'text-primary' : 'text-danger' }} fs-6">
                            Rs. {{ number_format($grossProfit, 2) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- 3. Operating Expenses -->
        <h6 class="fw-bold text-uppercase text-danger border-bottom pb-2 mb-3">3. Operating Expenses</h6>
        <div class="table-responsive mb-4">
            <table class="table table-sm align-middle mb-0">
                <tbody>
                    @forelse($operatingExpenses as $exp)
                        <tr>
                            <td style="width: 70%;" class="ps-3">{{ $exp->name }} ({{ $exp->code }})</td>
                            <td style="width: 30%;" class="text-end">Rs. {{ number_format($exp->period_total, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="ps-3 text-muted">No operating expenses recorded in period.</td>
                        </tr>
                    @endforelse
                    <tr class="table-light fw-bold">
                        <td class="ps-3">Total Operating Expenses:</td>
                        <td class="text-end text-danger fs-6">Rs. {{ number_format($totalOperatingExpense, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Net Profit -->
        <div class="p-3 rounded {{ $netProfit >= 0 ? 'bg-success-subtle border border-success' : 'bg-danger-subtle border border-danger' }} d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}">
                NET {{ $netProfit >= 0 ? 'PROFIT' : 'LOSS' }} FOR THE PERIOD:
            </h5>
            <h4 class="mb-0 fw-bold {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}">
                Rs. {{ number_format($netProfit, 2) }}
            </h4>
        </div>
    </div>
</div>
@endsection
