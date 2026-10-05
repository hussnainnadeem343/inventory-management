<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Attendance;
use App\Models\HR\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id') : $user->shop_id;
        $date = $request->query('date', now()->format('Y-m-d'));

        $attendances = Attendance::with(['employee.designation'])
            ->forShop($shopId)
            ->where('date', $date)
            ->get();

        $activeEmployeesCount = Employee::forShop($shopId)->active()->count();

        return view('hr.attendance.index', compact('attendances', 'date', 'activeEmployeesCount'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id', $user->shop_id) : $user->shop_id;
        $date = $request->query('date', now()->format('Y-m-d'));

        $employees = Employee::with(['designation', 'department'])
            ->forShop($shopId)
            ->active()
            ->orderBy('first_name')
            ->get();

        $existing = Attendance::forShop($shopId)->where('date', $date)->get()->keyBy('employee_id');

        return view('hr.attendance.create', compact('employees', 'date', 'existing'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->input('shop_id', $user->shop_id) : $user->shop_id;

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'attendance' => ['required', 'array'],
            'attendance.*.status' => ['required', 'in:present,absent,late,half_day,leave'],
            'attendance.*.check_in' => ['nullable'],
            'attendance.*.check_out' => ['nullable'],
            'attendance.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($shopId, $validated) {
            foreach ($validated['attendance'] as $empId => $data) {
                Attendance::updateOrCreate(
                    [
                        'shop_id' => $shopId,
                        'employee_id' => $empId,
                        'date' => $validated['date'],
                    ],
                    [
                        'status' => $data['status'],
                        'check_in' => $data['check_in'] ?? null,
                        'check_out' => $data['check_out'] ?? null,
                        'notes' => $data['notes'] ?? null,
                    ]
                );
            }
        });

        return redirect()->route('hr.attendance.index', ['date' => $validated['date']])->with('success', 'Attendance marked successfully.');
    }
}
