@extends('layouts.app')
@section('title', 'Register Employee')
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Register New Employee</h5>
                    <a href="{{ route('hr.employees.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Back to Directory
                    </a>
                </div>
            </div>
            <div class="card-body p-4">
                <form method="post" action="{{ route('hr.employees.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Employee Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('employee_code') is-invalid @enderror" name="employee_code" value="{{ old('employee_code') }}" placeholder="e.g. EMP-001" required>
                            @error('employee_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('first_name') is-invalid @enderror" name="first_name" value="{{ old('first_name') }}" placeholder="e.g. Muhammad" required>
                            @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Last Name</label>
                            <input type="text" class="form-control" name="last_name" value="{{ old('last_name') }}" placeholder="e.g. Ali">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Department</label>
                            <select class="form-select" name="department_id">
                                <option value="">Select Department</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" @selected(old('department_id') == $dept->id)>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Designation / Role</label>
                            <select class="form-select" name="designation_id">
                                <option value="">Select Designation</option>
                                @foreach($designations as $des)
                                    <option value="{{ $des->id }}" @selected(old('designation_id') == $des->id)>{{ $des->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">CNIC / ID Card Number</label>
                            <input type="text" class="form-control" name="cnic" value="{{ old('cnic') }}" placeholder="42101-1234567-1">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone / Mobile</label>
                            <input type="text" class="form-control" name="phone" value="{{ old('phone') }}" placeholder="0300-1234567">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email Address</label>
                            <input type="email" class="form-control" name="email" value="{{ old('email') }}" placeholder="employee@example.com">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Joining Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('joining_date') is-invalid @enderror" name="joining_date" value="{{ old('joining_date', date('Y-m-d')) }}" required>
                            @error('joining_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Basic Monthly Salary (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control @error('basic_salary') is-invalid @enderror" name="basic_salary" value="{{ old('basic_salary') }}" placeholder="35000" required>
                            @error('basic_salary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" required>
                                <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                                <option value="on_leave" @selected(old('status') === 'on_leave')>On Leave</option>
                                <option value="resigned" @selected(old('status') === 'resigned')>Resigned</option>
                                <option value="terminated" @selected(old('status') === 'terminated')>Terminated</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Bank Name (For Salary)</label>
                            <input type="text" class="form-control" name="bank_name" value="{{ old('bank_name') }}" placeholder="e.g. Meezan Bank">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Bank Account / IBAN</label>
                            <input type="text" class="form-control" name="bank_account_no" value="{{ old('bank_account_no') }}" placeholder="PK00MEZN000123456789">
                        </div>

                        <div class="col-12 text-end mt-4">
                            <a href="{{ route('hr.employees.index') }}" class="btn btn-light me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-save me-1"></i> Register Employee
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
