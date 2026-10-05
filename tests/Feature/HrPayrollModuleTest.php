<?php

namespace Tests\Feature;

use App\Models\Finance\JournalEntry;
use App\Models\HR\Attendance;
use App\Models\HR\Department;
use App\Models\HR\Designation;
use App\Models\HR\Employee;
use App\Models\HR\Payroll;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrPayrollModuleTest extends TestCase
{
    use RefreshDatabase;

    private function shopAdmin(): User
    {
        $shop = Shop::create(['name' => 'Metro Retailers', 'code' => 'MR01', 'status' => 'active']);
        return User::factory()->create([
            'role' => 'shop_admin',
            'shop_id' => $shop->id,
            'status' => 'active',
        ]);
    }

    public function test_can_manage_departments_and_designations(): void
    {
        $admin = $this->shopAdmin();

        $respDept = $this->actingAs($admin)->post('/hr/departments', [
            'name' => 'Supply Chain',
            'description' => 'Inventory and procurement management team',
        ]);

        $respDept->assertSessionHas('success');
        $this->assertDatabaseHas('departments', [
            'name' => 'Supply Chain',
            'shop_id' => $admin->shop_id,
        ]);

        $respDesig = $this->actingAs($admin)->post('/hr/designations', [
            'title' => 'Inventory Specialist',
            'description' => 'Responsible for stock counts and warehouse audits',
        ]);

        $respDesig->assertSessionHas('success');
        $this->assertDatabaseHas('designations', [
            'title' => 'Inventory Specialist',
            'shop_id' => $admin->shop_id,
        ]);
    }

    public function test_can_register_and_update_employee(): void
    {
        $admin = $this->shopAdmin();
        $shopId = $admin->shop_id;

        $dept = Department::create(['shop_id' => $shopId, 'name' => 'Sales & POS']);
        $desig = Designation::create(['shop_id' => $shopId, 'title' => 'Cashier']);

        $createResponse = $this->actingAs($admin)->post('/hr/employees', [
            'employee_code' => 'EMP-101',
            'first_name' => 'Zeeshan',
            'last_name' => 'Ahmed',
            'department_id' => $dept->id,
            'designation_id' => $desig->id,
            'phone' => '03001234567',
            'email' => 'zeeshan@example.com',
            'joining_date' => '2026-01-15',
            'basic_salary' => 45000,
            'status' => 'active',
        ]);

        $createResponse->assertRedirect(route('hr.employees.index'));
        $this->assertDatabaseHas('employees', [
            'employee_code' => 'EMP-101',
            'first_name' => 'Zeeshan',
            'basic_salary' => 45000,
            'shop_id' => $shopId,
        ]);

        $emp = Employee::where('employee_code', 'EMP-101')->first();

        $updateResponse = $this->actingAs($admin)->put("/hr/employees/{$emp->id}", [
            'employee_code' => 'EMP-101',
            'first_name' => 'Zeeshan',
            'last_name' => 'Ahmed Khan',
            'department_id' => $dept->id,
            'designation_id' => $desig->id,
            'joining_date' => '2026-01-15',
            'basic_salary' => 50000,
            'status' => 'active',
        ]);

        $updateResponse->assertRedirect(route('hr.employees.index'));
        $this->assertDatabaseHas('employees', [
            'id' => $emp->id,
            'last_name' => 'Ahmed Khan',
            'basic_salary' => 50000,
        ]);
    }

    public function test_can_record_daily_attendance(): void
    {
        $admin = $this->shopAdmin();
        $shopId = $admin->shop_id;

        $emp = Employee::create([
            'shop_id' => $shopId,
            'employee_code' => 'EMP-201',
            'first_name' => 'Bilal',
            'joining_date' => '2026-02-01',
            'basic_salary' => 40000,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->post('/hr/attendance', [
            'date' => '2026-10-05',
            'attendance' => [
                $emp->id => [
                    'status' => 'present',
                    'check_in' => '09:00',
                    'check_out' => '18:00',
                    'notes' => 'On time',
                ],
            ],
        ]);

        $response->assertRedirect('/hr/attendance?date=2026-10-05');
        $this->assertDatabaseHas('attendances', [
            'shop_id' => $shopId,
            'employee_id' => $emp->id,
            'status' => 'present',
            'check_in' => '09:00',
        ]);
        $attendance = Attendance::where('employee_id', $emp->id)->first();
        $this->assertEquals('2026-10-05', $attendance->date->format('Y-m-d'));
    }

    public function test_can_generate_and_disburse_payroll_with_finance_posting(): void
    {
        $admin = $this->shopAdmin();
        $shopId = $admin->shop_id;

        $emp = Employee::create([
            'shop_id' => $shopId,
            'employee_code' => 'EMP-301',
            'first_name' => 'Tariq',
            'joining_date' => '2026-01-01',
            'basic_salary' => 60000,
            'status' => 'active',
        ]);

        $generateResp = $this->actingAs($admin)->post('/hr/payroll', [
            'month' => 10,
            'year' => 2026,
            'payroll' => [
                [
                    'employee_id' => $emp->id,
                    'basic_salary' => 60000,
                    'allowances' => 5000,
                    'deductions' => 2000,
                ],
            ],
        ]);

        $generateResp->assertRedirect('/hr/payroll?month=10&year=2026');

        $payroll = Payroll::where('employee_id', $emp->id)->where('month', 10)->first();
        $this->assertNotNull($payroll);
        $this->assertEquals(63000, $payroll->net_salary);
        $this->assertEquals('generated', $payroll->status);

        // Disburse salary and post to Finance
        $payResp = $this->actingAs($admin)->post("/hr/payroll/{$payroll->id}/pay", [
            'payment_date' => '2026-10-31',
            'payment_method' => 'bank_transfer',
        ]);

        $payResp->assertSessionHas('success');
        $payroll->refresh();
        $this->assertEquals('paid', $payroll->status);
        $this->assertEquals('bank_transfer', $payroll->payment_method);

        // Verify Journal Entry was created in Finance module
        $this->assertDatabaseHas('journal_entries', [
            'shop_id' => $shopId,
            'reference_type' => 'payroll',
            'reference_id' => $payroll->id,
            'total_debit' => 63000,
            'total_credit' => 63000,
        ]);
    }
}
