@extends('layouts.app')
@section('title', 'Daily Attendance')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Daily Staff Attendance</h4>
        <small class="text-secondary">Attendance records for {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</small>
    </div>
    <a class="btn btn-primary" href="{{ route('hr.attendance.create') }}?date={{ $date }}">
        <i class="bi bi-pencil-square me-1"></i> Mark / Edit Attendance
    </a>
</div>

<div class="card p-3 mb-3">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Select Date</label>
            <input type="date" class="form-control" name="date" value="{{ $date }}">
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-filter me-1"></i> View Date</button>
            <a class="btn btn-outline-secondary" href="{{ route('hr.attendance.index') }}">Today</a>
        </div>
    </form>
</div>

<div class="row g-3 mb-4">
    @php
        $presentCount = $attendances->where('status', 'present')->count();
        $absentCount = $attendances->where('status', 'absent')->count();
        $lateCount = $attendances->where('status', 'late')->count();
    @endphp
    <div class="col-md-3">
        <div class="card p-3 shadow-sm border-0 bg-white">
            <small class="text-secondary fw-semibold">TOTAL ACTIVE STAFF</small>
            <h3 class="fw-bold my-1 text-primary">{{ $activeEmployeesCount }}</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 shadow-sm border-0 bg-white">
            <small class="text-secondary fw-semibold">PRESENT TODAY</small>
            <h3 class="fw-bold my-1 text-success">{{ $presentCount }}</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 shadow-sm border-0 bg-white">
            <small class="text-secondary fw-semibold">ABSENT</small>
            <h3 class="fw-bold my-1 text-danger">{{ $absentCount }}</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 shadow-sm border-0 bg-white">
            <small class="text-secondary fw-semibold">LATE ARRIVALS</small>
            <h3 class="fw-bold my-1 text-warning">{{ $lateCount }}</h3>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Emp Code</th>
                    <th>Staff Name</th>
                    <th>Designation</th>
                    <th>Status</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendances as $att)
                    <tr>
                        <td><span class="badge bg-light text-dark border font-monospace">{{ $att->employee->employee_code }}</span></td>
                        <td>
                            <a href="{{ route('hr.employees.show', $att->employee) }}" class="fw-semibold text-decoration-none">
                                {{ $att->employee->full_name }}
                            </a>
                        </td>
                        <td>{{ $att->employee->designation ? $att->employee->designation->title : 'Staff' }}</td>
                        <td>
                            @php
                                $badge = match($att->status) {
                                    'present' => 'bg-success-subtle text-success',
                                    'absent' => 'bg-danger-subtle text-danger',
                                    'late' => 'bg-warning-subtle text-warning',
                                    default => 'bg-info-subtle text-info',
                                };
                            @endphp
                            <span class="badge {{ $badge }} fs-6">{{ ucfirst($att->status) }}</span>
                        </td>
                        <td>{{ $att->check_in ?? '—' }}</td>
                        <td>{{ $att->check_out ?? '—' }}</td>
                        <td><small class="text-muted">{{ $att->notes ?? '—' }}</small></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            No attendance recorded for this date. Click "Mark / Edit Attendance" to record.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
