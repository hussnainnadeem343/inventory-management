@extends('layouts.app')
@section('title', 'Monthly Payroll')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Monthly Payroll & Salaries</h4>
        <small class="text-secondary">Salary calculations, disbursements, and pay slips</small>
    </div>
    <a class="btn btn-primary" href="{{ route('hr.payroll.create') }}?month={{ $month }}&year={{ $year }}">
        <i class="bi bi-calculator me-1"></i> Generate Payroll
    </a>
</div>

<div class="card p-3 mb-3">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Month</label>
            <select class="form-select" name="month">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" @selected($month == $m)>{{ date('F', mktime(0, 0, 0, $m, 10)) }}</option>
                @endfor
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Year</label>
            <input type="number" class="form-control" name="year" value="{{ $year }}">
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-filter me-1"></i> Filter</button>
            <a class="btn btn-outline-secondary" href="{{ route('hr.payroll.index') }}">Current Month</a>
        </div>
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card p-3 shadow-sm border-0 bg-white">
            <small class="text-secondary fw-semibold">TOTAL BASIC SALARIES</small>
            <h3 class="fw-bold my-1 text-secondary">Rs. {{ number_format($totalBasic, 2) }}</h3>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-3 shadow-sm border-0 bg-white">
            <small class="text-secondary fw-semibold">TOTAL NET PAYABLE FOR {{ strtoupper(date('F', mktime(0, 0, 0, $month, 10))) }}</small>
            <h3 class="fw-bold my-1 text-primary">Rs. {{ number_format($totalNet, 2) }}</h3>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Slip #</th>
                    <th>Staff Name</th>
                    <th>Designation</th>
                    <th>Basic Pay</th>
                    <th>Allowances</th>
                    <th>Deductions</th>
                    <th>Net Salary</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payrolls as $pay)
                    <tr>
                        <td>
                            <a href="{{ route('hr.payroll.show', $pay) }}" class="fw-bold font-monospace text-decoration-none">
                                {{ $pay->payroll_number }}
                            </a>
                        </td>
                        <td>
                            <a href="{{ route('hr.employees.show', $pay->employee) }}" class="fw-semibold text-decoration-none">
                                {{ $pay->employee->full_name }}
                            </a>
                            <div><small class="text-muted">{{ $pay->employee->employee_code }}</small></div>
                        </td>
                        <td>{{ $pay->employee->designation ? $pay->employee->designation->title : 'Staff' }}</td>
                        <td>Rs. {{ number_format($pay->basic_salary, 2) }}</td>
                        <td class="text-success">+ Rs. {{ number_format($pay->allowances, 2) }}</td>
                        <td class="text-danger">- Rs. {{ number_format($pay->deductions, 2) }}</td>
                        <td class="fw-bold text-dark fs-6">Rs. {{ number_format($pay->net_salary, 2) }}</td>
                        <td>
                            @if($pay->status === 'paid')
                                <span class="badge bg-success-subtle text-success">Paid</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning">Generated</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a class="btn btn-outline-secondary" href="{{ route('hr.payroll.show', $pay) }}" title="View Payslip">
                                    <i class="bi bi-receipt"></i>
                                </a>
                                @if($pay->status === 'generated')
                                    <button class="btn btn-outline-success" type="button" data-bs-toggle="modal" data-bs-target="#payModal{{ $pay->id }}" title="Disburse Salary">
                                        <i class="bi bi-cash-stack me-1"></i> Pay
                                    </button>
                                @endif
                            </div>

                            <!-- Pay Modal -->
                            @if($pay->status === 'generated')
                                <div class="modal fade" id="payModal{{ $pay->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content text-start">
                                            <form method="post" action="{{ route('hr.payroll.pay', $pay) }}">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold">Disburse Salary - {{ $pay->employee->full_name }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Net Salary: <strong class="text-primary fs-5">Rs. {{ number_format($pay->net_salary, 2) }}</strong></p>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Disbursal Date <span class="text-danger">*</span></label>
                                                        <input type="date" class="form-control" name="payment_date" value="{{ date('Y-m-d') }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Payment Mode <span class="text-danger">*</span></label>
                                                        <select class="form-select" name="payment_method" required>
                                                            <option value="cash">Cash in Hand</option>
                                                            <option value="bank_transfer">Bank Transfer / Online</option>
                                                            <option value="cheque">Cheque</option>
                                                        </select>
                                                    </div>
                                                    <small class="text-muted">
                                                        <i class="bi bi-info-circle me-1"></i>
                                                        This will automatically post a Salary Expense into your Finance General Ledger.
                                                    </small>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-success">Confirm Disbursal</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            No payroll generated for this month. Click "Generate Payroll" to calculate.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($payrolls->hasPages())
        <div class="p-3 border-top">
            {{ $payrolls->links() }}
        </div>
    @endif
</div>
@endsection
