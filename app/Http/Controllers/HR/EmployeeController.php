<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Department;
use App\Models\HR\Designation;
use App\Models\HR\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id') : $user->shop_id;
        $search = trim((string) $request->query('search'));
        $status = $request->query('status');

        $query = Employee::with(['department', 'designation', 'shop'])
            ->forShop($shopId);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $employees = $query->latest('id')->paginate(15)->withQueryString();

        return view('hr.employees.index', compact('employees', 'search', 'status'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id', $user->shop_id) : $user->shop_id;

        $departments = Department::forShop($shopId)->orderBy('name')->get();
        $designations = Designation::forShop($shopId)->orderBy('title')->get();

        return view('hr.employees.create', compact('departments', 'designations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->input('shop_id', $user->shop_id) : $user->shop_id;

        $validated = $request->validate([
            'employee_code' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'designation_id' => ['nullable', 'exists:designations,id'],
            'cnic' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'joining_date' => ['required', 'date'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_no' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:active,on_leave,resigned,terminated'],
        ]);

        Employee::create(array_merge($validated, [
            'shop_id' => $shopId,
            'created_by' => $user->id,
        ]));

        return redirect()->route('hr.employees.index')->with('success', 'Employee registered successfully.');
    }

    public function edit(Employee $employee): View
    {
        $shopId = $employee->shop_id;
        $departments = Department::forShop($shopId)->orderBy('name')->get();
        $designations = Designation::forShop($shopId)->orderBy('title')->get();

        return view('hr.employees.edit', compact('employee', 'departments', 'designations'));
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'employee_code' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'designation_id' => ['nullable', 'exists:designations,id'],
            'cnic' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'joining_date' => ['required', 'date'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_no' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:active,on_leave,resigned,terminated'],
        ]);

        $employee->update($validated);

        return redirect()->route('hr.employees.index')->with('success', 'Employee updated successfully.');
    }

    public function show(Employee $employee): View
    {
        $employee->load([
            'department',
            'designation',
            'attendances' => fn($q) => $q->latest('date')->take(10),
            'payrolls' => fn($q) => $q->latest('year')->latest('month')->take(6),
        ]);

        return view('hr.employees.show', compact('employee'));
    }
}
