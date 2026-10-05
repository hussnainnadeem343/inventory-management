<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Finance\Account;
use App\Models\HR\Employee;
use App\Models\HR\Payroll;
use App\Services\Finance\JournalEntryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id') : $user->shop_id;
        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);

        $query = Payroll::with(['employee.designation', 'employee.department'])
            ->forShop($shopId)
            ->where('month', $month)
            ->where('year', $year);

        $totalBasic = (clone $query)->sum('basic_salary');
        $totalNet = (clone $query)->sum('net_salary');
        $payrolls = $query->latest('id')->paginate(15)->withQueryString();

        return view('hr.payroll.index', compact('payrolls', 'month', 'year', 'totalBasic', 'totalNet'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id', $user->shop_id) : $user->shop_id;
        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);

        $employees = Employee::with(['designation', 'department'])
            ->forShop($shopId)
            ->active()
            ->orderBy('first_name')
            ->get();

        $existing = Payroll::forShop($shopId)
            ->where('month', $month)
            ->where('year', $year)
            ->get()
            ->keyBy('employee_id');

        return view('hr.payroll.create', compact('employees', 'month', 'year', 'existing'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->input('shop_id', $user->shop_id) : $user->shop_id;

        $validated = $request->validate([
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'min:2020'],
            'payroll' => ['required', 'array'],
            'payroll.*.employee_id' => ['required', 'exists:employees,id'],
            'payroll.*.basic_salary' => ['required', 'numeric', 'min:0'],
            'payroll.*.allowances' => ['nullable', 'numeric', 'min:0'],
            'payroll.*.deductions' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($user, $shopId, $validated) {
            foreach ($validated['payroll'] as $item) {
                $basic = (float) $item['basic_salary'];
                $allowances = (float) ($item['allowances'] ?? 0);
                $deductions = (float) ($item['deductions'] ?? 0);
                $net = max(0, $basic + $allowances - $deductions);

                $prefix = "PAY-{$validated['year']}" . str_pad((string) $validated['month'], 2, '0', STR_PAD_LEFT) . '-';
                $count = Payroll::where('payroll_number', 'like', "{$prefix}%")->count() + 1;
                $payNo = $prefix . str_pad((string) $count, 4, '0', STR_PAD_LEFT);

                Payroll::updateOrCreate(
                    [
                        'shop_id' => $shopId,
                        'employee_id' => $item['employee_id'],
                        'month' => $validated['month'],
                        'year' => $validated['year'],
                    ],
                    [
                        'payroll_number' => $payNo,
                        'basic_salary' => $basic,
                        'allowances' => $allowances,
                        'deductions' => $deductions,
                        'net_salary' => $net,
                        'status' => Payroll::STATUS_GENERATED,
                        'created_by' => $user->id,
                    ]
                );
            }
        });

        return redirect()->route('hr.payroll.index', ['month' => $validated['month'], 'year' => $validated['year']])
            ->with('success', 'Monthly payroll calculated and generated.');
    }

    public function pay(Request $request, Payroll $payroll, JournalEntryService $journalService): RedirectResponse
    {
        $user = $request->user();
        $shopId = $payroll->shop_id;

        if ($payroll->status === Payroll::STATUS_PAID) {
            return back()->with('error', 'Payroll has already been marked as paid.');
        }

        $validated = $request->validate([
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'string'],
        ]);

        DB::transaction(function () use ($user, $shopId, $payroll, $validated, $journalService) {
            $payroll->update([
                'status' => Payroll::STATUS_PAID,
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
            ]);

            // Auto-post Salary Expense in Finance Module
            $salaryExpAcc = Account::where('shop_id', $shopId)->where('code', '5010')->first()
                ?? Account::whereNull('shop_id')->where('code', '5010')->first()
                ?? Account::where('code', '5010')->first();

            $cashAcc = Account::where('shop_id', $shopId)->where('code', '1001')->first()
                ?? Account::whereNull('shop_id')->where('code', '1001')->first()
                ?? Account::where('code', '1001')->first();

            if ($salaryExpAcc && $cashAcc && $payroll->net_salary > 0) {
                try {
                    $journalService->recordEntry(
                        $shopId,
                        $validated['payment_date'],
                        'payroll',
                        $payroll->id,
                        "Salary payment to {$payroll->employee->full_name} for {$payroll->month_name} {$payroll->year}",
                        [
                            ['account_id' => $salaryExpAcc->id, 'debit' => $payroll->net_salary, 'credit' => 0, 'narration' => 'Salary Expense'],
                            ['account_id' => $cashAcc->id, 'debit' => 0, 'credit' => $payroll->net_salary, 'narration' => 'Disbursed via ' . ucfirst($payroll->payment_method)],
                        ],
                        $user->id
                    );
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Payroll journal entry failed: ' . $e->getMessage(), [
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        });

        return back()->with('success', "Payroll #{$payroll->payroll_number} disbursed and posted to financial accounts.");
    }

    public function show(Payroll $payroll): View
    {
        $payroll->load(['employee.designation', 'employee.department', 'employee.shop', 'creator']);

        return view('hr.payroll.show', compact('payroll'));
    }
}
