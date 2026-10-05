@extends('layouts.app')
@section('title', 'Employees Directory')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Employee Directory</h4>
        <small class="text-secondary">Staff profiles, designations, and salary structures</small>
    </div>
    <a class="btn btn-primary" href="{{ route('hr.employees.create') }}">
        <i class="bi bi-person-plus me-1"></i> Register Employee
    </a>
</div>

<div class="card p-3 mb-3">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-lg-5 col-md-6">
            <label class="form-label">Search</label>
            <input class="form-control" name="search" value="{{ $search }}" placeholder="Name, code, phone...">
        </div>
        <div class="col-sm-6 col-lg-3 col-md-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
                <option value="">All Statuses</option>
                <option value="active" @selected($status === 'active')>Active</option>
                <option value="on_leave" @selected($status === 'on_leave')>On Leave</option>
                <option value="resigned" @selected($status === 'resigned')>Resigned</option>
                <option value="terminated" @selected($status === 'terminated')>Terminated</option>
            </select>
        </div>
        <div class="col-lg-4 d-flex gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i> Search</button>
            <a class="btn btn-outline-secondary" href="{{ route('hr.employees.index') }}">Reset</a>
        </div>
    </form>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Emp Code</th>
                    <th>Full Name</th>
                    <th>Department</th>
                    <th>Designation</th>
                    <th>Phone</th>
                    <th>Basic Salary</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $emp)
                    <tr>
                        <td><span class="badge bg-light text-dark border font-monospace">{{ $emp->employee_code }}</span></td>
                        <td>
                            <a href="{{ route('hr.employees.show', $emp) }}" class="fw-semibold text-decoration-none">
                                {{ $emp->full_name }}
                            </a>
                        </td>
                        <td>{{ $emp->department ? $emp->department->name : '—' }}</td>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary">
                                {{ $emp->designation ? $emp->designation->title : 'Staff' }}
                            </span>
                        </td>
                        <td>{{ $emp->phone ?? '—' }}</td>
                        <td class="fw-bold text-dark">Rs. {{ number_format($emp->basic_salary, 2) }}</td>
                        <td>
                            @php
                                $badgeClass = match($emp->status) {
                                    'active' => 'bg-success-subtle text-success',
                                    'on_leave' => 'bg-warning-subtle text-warning',
                                    'resigned', 'terminated' => 'bg-danger-subtle text-danger',
                                    default => 'bg-secondary-subtle text-secondary',
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }}">{{ ucfirst(str_replace('_', ' ', $emp->status)) }}</span>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a class="btn btn-outline-secondary" href="{{ route('hr.employees.show', $emp) }}" title="View Profile">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a class="btn btn-outline-primary" href="{{ route('hr.employees.edit', $emp) }}" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No employees registered yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($employees->hasPages())
        <div class="p-3 border-top">
            {{ $employees->links() }}
        </div>
    @endif
</div>
@endsection
