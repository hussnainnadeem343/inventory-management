@extends('layouts.app')
@section('title', 'Mark Staff Attendance')
@section('content')
<form method="post" action="{{ route('hr.attendance.store') }}">
    @csrf
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 fw-bold">Mark Staff Attendance</h4>
            <small class="text-secondary">Record attendance for all active employees</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('hr.attendance.index', ['date' => $date]) }}" class="btn btn-outline-secondary">
                <i class="bi bi-x-lg me-1"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check2-circle me-1"></i> Save Attendance Sheet
            </button>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Attendance Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="date" value="{{ $date }}" onchange="window.location.href='{{ route('hr.attendance.create') }}?date=' + this.value" required>
                </div>
                <div class="col-md-8 text-md-end mt-3 mt-md-0">
                    <button type="button" class="btn btn-sm btn-outline-success me-1" onclick="setAll('present')">Mark All Present</button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="setAll('absent')">Mark All Absent</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 25%;">Employee</th>
                        <th style="width: 35%;">Attendance Status</th>
                        <th style="width: 15%;">Check In</th>
                        <th style="width: 15%;">Check Out</th>
                        <th style="width: 10%;">Note</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $emp)
                        @php
                            $rec = $existing->get($emp->id);
                            $currentStatus = $rec ? $rec->status : 'present';
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $emp->full_name }}</div>
                                <small class="text-muted">{{ $emp->employee_code }} • {{ $emp->designation ? $emp->designation->title : 'Staff' }}</small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <input type="radio" class="btn-check status-radio" name="attendance[{{ $emp->id }}][status]" id="p_{{ $emp->id }}" value="present" @checked($currentStatus === 'present')>
                                    <label class="btn btn-outline-success" for="p_{{ $emp->id }}">Present</label>

                                    <input type="radio" class="btn-check status-radio" name="attendance[{{ $emp->id }}][status]" id="l_{{ $emp->id }}" value="late" @checked($currentStatus === 'late')>
                                    <label class="btn btn-outline-warning" for="l_{{ $emp->id }}">Late</label>

                                    <input type="radio" class="btn-check status-radio" name="attendance[{{ $emp->id }}][status]" id="h_{{ $emp->id }}" value="half_day" @checked($currentStatus === 'half_day')>
                                    <label class="btn btn-outline-info" for="h_{{ $emp->id }}">Half Day</label>

                                    <input type="radio" class="btn-check status-radio" name="attendance[{{ $emp->id }}][status]" id="a_{{ $emp->id }}" value="absent" @checked($currentStatus === 'absent')>
                                    <label class="btn btn-outline-danger" for="a_{{ $emp->id }}">Absent</label>

                                    <input type="radio" class="btn-check status-radio" name="attendance[{{ $emp->id }}][status]" id="lv_{{ $emp->id }}" value="leave" @checked($currentStatus === 'leave')>
                                    <label class="btn btn-outline-secondary" for="lv_{{ $emp->id }}">Leave</label>
                                </div>
                            </td>
                            <td>
                                <input type="time" class="form-control form-control-sm" name="attendance[{{ $emp->id }}][check_in]" value="{{ $rec?->check_in }}">
                            </td>
                            <td>
                                <input type="time" class="form-control form-control-sm" name="attendance[{{ $emp->id }}][check_out]" value="{{ $rec?->check_out }}">
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm" name="attendance[{{ $emp->id }}][notes]" value="{{ $rec?->notes }}" placeholder="Optional">
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No active staff registered.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</form>

@push('scripts')
<script>
    function setAll(val) {
        document.querySelectorAll(`input.status-radio[value="${val}"]`).forEach(r => r.checked = true);
    }
</script>
@endpush
@endsection
