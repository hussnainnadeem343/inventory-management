@extends('layouts.app')
@section('title', 'Profile - ' . $employee->full_name)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h4 class="mb-0 fw-bold">{{ $employee->full_name }}</h4>
            <span class="badge bg-light text-dark border font-monospace">{{ $employee->employee_code }}</span>
            <span class="badge bg-primary-subtle text-primary">{{ $employee->designation ? $employee->designation->title : 'Staff' }}</span>
        </div>
        <small class="text-secondary">{{ $employee->department ? $employee->department->name : 'General Department' }}</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('hr.employees.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <a href="{{ route('hr.employees.edit', $employee) }}" class="btn btn-outline-primary">
            <i class="bi bi-pencil me-1"></i> Edit Profile
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card p-3 shadow-sm h-100">
            <small class="text-secondary fw-semibold">SALARY & COMPENSATION</small>
            <h3 class="fw-bold my-1 text-primary">Rs. {{ number_format($employee->basic_salary, 2) }}</h3>
            <small class="text-muted">Monthly Basic Pay</small>
            <div class="mt-3 border-top pt-2">
                <div><strong>Bank:</strong> {{ $employee->bank_name ?? 'Cash Payment' }}</div>
                <div><strong>A/C:</strong> {{ $employee->bank_account_no ?? '—' }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 shadow-sm h-100">
            <small class="text-secondary fw-semibold">PERSONAL & CONTACT INFO</small>
            <div class="mt-2">
                <div><i class="bi bi-card-text text-secondary me-2"></i>CNIC: {{ $employee->cnic ?? 'N/A' }}</div>
                <div><i class="bi bi-telephone text-secondary me-2"></i>Phone: {{ $employee->phone ?? 'N/A' }}</div>
                <div><i class="bi bi-envelope text-secondary me-2"></i>Email: {{ $employee->email ?? 'N/A' }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 shadow-sm h-100">
            <small class="text-secondary fw-semibold">EMPLOYMENT STATUS</small>
            <div class="mt-2">
                <div><strong>Joined:</strong> {{ $employee->joining_date->format('d M Y') }}</div>
                <div class="mt-1">
                    <strong>Status:</strong>
                    @if($employee->status === 'active')
                        <span class="badge bg-success-subtle text-success">Active</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($employee->status) }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Recent Attendance -->
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-calendar-check me-1 text-primary"></i> Recent Attendance Log</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->attendances as $att)
                            <tr>
                                <td>{{ $att->date->format('d M Y') }}</td>
                                <td>
                                    @php
                                        $badge = match($att->status) {
                                            'present' => 'bg-success-subtle text-success',
                                            'absent' => 'bg-danger-subtle text-danger',
                                            'late' => 'bg-warning-subtle text-warning',
                                            default => 'bg-info-subtle text-info',
                                        };
                                    @endphp
                                    <span class="badge {{ $badge }}">{{ ucfirst($att->status) }}</span>
                                </td>
                                <td>{{ $att->check_in ?? '—' }}</td>
                                <td>{{ $att->check_out ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">No attendance records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Payrolls -->
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-cash-stack me-1 text-success"></i> Recent Salary Slips</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>Slip #</th>
                            <th>Month / Year</th>
                            <th>Net Pay</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->payrolls as $pay)
                            <tr>
                                <td>
                                    <a href="{{ route('hr.payroll.show', $pay) }}" class="fw-semibold text-decoration-none">
                                        {{ $pay->payroll_number }}
                                    </a>
                                </td>
                                <td>{{ $pay->month_name }} {{ $pay->year }}</td>
                                <td class="fw-bold">Rs. {{ number_format($pay->net_salary, 2) }}</td>
                                <td>
                                    <span class="badge {{ $pay->status === 'paid' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">
                                        {{ ucfirst($pay->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">No payroll history found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
