@extends('layouts.app')
@section('title', 'Generate Monthly Payroll')
@section('content')
<form method="post" action="{{ route('hr.payroll.store') }}" id="payrollForm">
    @csrf
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 fw-bold">Generate Monthly Payroll</h4>
            <small class="text-secondary">Calculate monthly employee salaries, allowances, and deductions</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('hr.payroll.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-x-lg me-1"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-calculator me-1"></i> Save & Generate Payroll
            </button>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="mb-0">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Payroll Month <span class="text-danger">*</span></label>
                    <select class="form-select" name="month" onchange="window.location.href='{{ route('hr.payroll.create') }}?month=' + this.value + '&year={{ $year }}'">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected($month == $m)>{{ date('F', mktime(0, 0, 0, $m, 10)) }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Year <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="year" value="{{ $year }}" required>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0" id="payrollTable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 25%;">Employee</th>
                        <th style="width: 20%;">Basic Pay (Rs.)</th>
                        <th style="width: 18%;">Allowances (Bonus/OT)</th>
                        <th style="width: 18%;">Deductions (Advance)</th>
                        <th style="width: 19%;" class="text-end">Net Salary (Rs.)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $index => $emp)
                        @php
                            $rec = $existing->get($emp->id);
                            $basic = $rec ? $rec->basic_salary : $emp->basic_salary;
                            $allowances = $rec ? $rec->allowances : 0;
                            $deductions = $rec ? $rec->deductions : 0;
                            $net = max(0, $basic + $allowances - $deductions);
                        @endphp
                        <tr class="payroll-row">
                            <td>
                                <input type="hidden" name="payroll[{{ $index }}][employee_id]" value="{{ $emp->id }}">
                                <div class="fw-semibold">{{ $emp->full_name }}</div>
                                <small class="text-muted">{{ $emp->employee_code }} • {{ $emp->designation ? $emp->designation->title : 'Staff' }}</small>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" class="form-control basic-input" name="payroll[{{ $index }}][basic_salary]" value="{{ $basic }}" required>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" class="form-control allowance-input text-success" name="payroll[{{ $index }}][allowances]" value="{{ $allowances }}" placeholder="0.00">
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" class="form-control deduction-input text-danger" name="payroll[{{ $index }}][deductions]" value="{{ $deductions }}" placeholder="0.00">
                            </td>
                            <td class="text-end fw-bold fs-6 net-display">
                                Rs. {{ number_format($net, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No active employees found to generate payroll.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</form>

@push('scripts')
<script>
    function recalcRow(row) {
        const basic = parseFloat(row.querySelector('.basic-input').value) || 0;
        const allowances = parseFloat(row.querySelector('.allowance-input').value) || 0;
        const deductions = parseFloat(row.querySelector('.deduction-input').value) || 0;
        const net = Math.max(0, basic + allowances - deductions);
        row.querySelector('.net-display').textContent = 'Rs. ' + net.toFixed(2);
    }

    document.querySelectorAll('.payroll-row').forEach(row => {
        row.querySelector('.basic-input').addEventListener('input', () => recalcRow(row));
        row.querySelector('.allowance-input').addEventListener('input', () => recalcRow(row));
        row.querySelector('.deduction-input').addEventListener('input', () => recalcRow(row));
    });
</script>
@endpush
@endsection
