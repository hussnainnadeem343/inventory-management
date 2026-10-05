@extends('layouts.app')
@section('title', 'Departments & Designations')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Departments & Designations</h4>
        <small class="text-secondary">Organizational structure and employee job roles</small>
    </div>
</div>

<div class="row g-4">
    <!-- Departments -->
    <div class="col-lg-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-diagram-2 me-1 text-primary"></i> Add New Department</h6>
            </div>
            <div class="card-body p-3">
                <form method="post" action="{{ route('hr.departments.store') }}">
                    @csrf
                    <div class="input-group">
                        <input type="text" class="form-control" name="name" placeholder="Department name (e.g. Sales, Accounts, Warehouse)" required>
                        <button class="btn btn-primary" type="submit"><i class="bi bi-plus-lg me-1"></i> Add</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light py-3">
                <h6 class="mb-0 fw-bold">Departments Directory</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="small text-secondary">
                        <tr>
                            <th>Department</th>
                            <th class="text-end">Total Staff</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($departments as $dept)
                            <tr>
                                <td class="fw-semibold">{{ $dept->name }}</td>
                                <td class="text-end">
                                    <span class="badge bg-primary-subtle text-primary">{{ $dept->employees_count }} Employees</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted py-3">No departments created yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Designations -->
    <div class="col-lg-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-person-badge me-1 text-success"></i> Add New Designation / Job Title</h6>
            </div>
            <div class="card-body p-3">
                <form method="post" action="{{ route('hr.designations.store') }}">
                    @csrf
                    <div class="input-group">
                        <input type="text" class="form-control" name="title" placeholder="Job title (e.g. Cashier, Store Keeper, Manager)" required>
                        <button class="btn btn-success" type="submit"><i class="bi bi-plus-lg me-1"></i> Add</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light py-3">
                <h6 class="mb-0 fw-bold">Designations Directory</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="small text-secondary">
                        <tr>
                            <th>Designation / Title</th>
                            <th class="text-end">Total Staff</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($designations as $des)
                            <tr>
                                <td class="fw-semibold">{{ $des->title }}</td>
                                <td class="text-end">
                                    <span class="badge bg-success-subtle text-success">{{ $des->employees_count }} Employees</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted py-3">No designations created yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
