@extends('layouts.app')
@section('title', 'Payslip - ' . $payroll->payroll_number)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h4 class="mb-0 fw-bold">{{ $payroll->payroll_number }}</h4>
            <span class="badge {{ $payroll->status === 'paid' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }} fs-6">
                {{ ucfirst($payroll->status) }}
            </span>
        </div>
        <small class="text-secondary">Salary slip for {{ $payroll->month_name }} {{ $payroll->year }}</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('hr.payroll.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <button class="btn btn-outline-dark" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print Payslip
        </button>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm p-4 border">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
                <div>
                    <h5 class="fw-bold mb-0">{{ $payroll->employee->shop ? $payroll->employee->shop->name : 'Inventory Management' }}</h5>
                    <small class="text-secondary">Official Monthly Salary Slip</small>
                </div>
                <div class="text-end">
                    <span class="badge bg-light text-dark border font-monospace fs-6">{{ $payroll->payroll_number }}</span>
                    <div><small class="text-muted">{{ $payroll->month_name }} {{ $payroll->year }}</small></div>
                </div>
            </div>

            <!-- Employee Info Grid -->
            <div class="row g-3 mb-4">
                <div class="col-6">
                    <small class="text-secondary d-block">EMPLOYEE NAME</small>
                    <strong class="fs-6">{{ $payroll->employee->full_name }}</strong>
                </div>
                <div class="col-6">
                    <small class="text-secondary d-block">EMPLOYEE CODE</small>
                    <strong>{{ $payroll->employee->employee_code }}</strong>
                </div>
                <div class="col-6">
                    <small class="text-secondary d-block">DEPARTMENT</small>
                    <div>{{ $payroll->employee->department ? $payroll->employee->department->name : 'General' }}</div>
                </div>
                <div class="col-6">
                    <small class="text-secondary d-block">DESIGNATION</small>
                    <div>{{ $payroll->employee->designation ? $payroll->employee->designation->title : 'Staff' }}</div>
                </div>
                <div class="col-6">
                    <small class="text-secondary d-block">PAYMENT METHOD</small>
                    <div>{{ ucfirst($payroll->payment_method) }}</div>
                </div>
                <div class="col-6">
                    <small class="text-secondary d-block">DISBURSED DATE</small>
                    <div>{{ $payroll->payment_date ? $payroll->payment_date->format('d M Y') : 'Pending' }}</div>
                </div>
            </div>

            <!-- Salary Details Table -->
            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Earnings / Components</th>
                            <th class="text-end">Amount (Rs.)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Basic Salary</td>
                            <td class="text-end fw-semibold">Rs. {{ number_format($payroll->basic_salary, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Allowances / Bonus / Overtime</td>
                            <td class="text-end text-success fw-semibold">+ Rs. {{ number_format($payroll->allowances, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Deductions / Advances / Unpaid Leaves</td>
                            <td class="text-end text-danger fw-semibold">- Rs. {{ number_format($payroll->deductions, 2) }}</td>
                        </tr>
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fs-5 fw-bold">
                            <td>NET SALARY PAYABLE:</td>
                            <td class="text-end text-primary">Rs. {{ number_format($payroll->net_salary, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Signatures -->
            <div class="row pt-4 mt-4 border-top text-center">
                <div class="col-6">
                    <div style="border-top: 1px dashed #aaa; width: 60%; margin: 40px auto 5px;"></div>
                    <small class="text-secondary">Employee Signature</small>
                </div>
                <div class="col-6">
                    <div style="border-top: 1px dashed #aaa; width: 60%; margin: 40px auto 5px;"></div>
                    <small class="text-secondary">Authorized Signature / Stamp</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
