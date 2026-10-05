<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            // Purchase & Procurement Module
            ['name' => 'View Purchases & Suppliers', 'slug' => 'purchases.view', 'module' => 'purchases', 'description' => 'Browse suppliers, purchase orders and goods received'],
            ['name' => 'Create Purchase Order', 'slug' => 'purchases.create', 'module' => 'purchases', 'description' => 'Create new purchase orders for suppliers'],
            ['name' => 'Edit Purchase Order', 'slug' => 'purchases.edit', 'module' => 'purchases', 'description' => 'Update draft purchase orders and pricing'],
            ['name' => 'Delete Purchase Order', 'slug' => 'purchases.delete', 'module' => 'purchases', 'description' => 'Cancel or remove draft purchase orders'],
            ['name' => 'Receive Goods (GRN)', 'slug' => 'purchases.grn', 'module' => 'purchases', 'description' => 'Create Goods Received Notes and receive stock batches'],
            ['name' => 'Purchase Return', 'slug' => 'purchases.return', 'module' => 'purchases', 'description' => 'Process purchase returns and debit notes to vendors'],

            // Finance & Accounts Module
            ['name' => 'View Accounts & Balances', 'slug' => 'accounts.view', 'module' => 'accounts', 'description' => 'View chart of accounts, cash and bank balances'],
            ['name' => 'Manage Accounts & Ledger', 'slug' => 'accounts.manage', 'module' => 'accounts', 'description' => 'Create accounts and manage account heads'],
            ['name' => 'Journal & Vouchers', 'slug' => 'accounts.vouchers', 'module' => 'accounts', 'description' => 'Create manual journal vouchers, expenses and payments'],
            ['name' => 'Financial Reports', 'slug' => 'accounts.reports', 'module' => 'accounts', 'description' => 'View Profit & Loss statement, Balance Sheet and Trial Balance'],

            // HR & Payroll Module
            ['name' => 'Manage Employees', 'slug' => 'hr.employees', 'module' => 'hr', 'description' => 'Manage employee profiles, designations and departments'],
            ['name' => 'Manage Attendance & Leaves', 'slug' => 'hr.attendance', 'module' => 'hr', 'description' => 'Record daily employee attendance and leave requests'],
            ['name' => 'Manage Payroll', 'slug' => 'hr.payroll', 'module' => 'hr', 'description' => 'Generate monthly payroll, salary slips and pay disbursals'],
        ];

        $now = now();
        $insertedSlugs = [];
        foreach ($permissions as $p) {
            $existing = DB::table('permissions')->where('slug', $p['slug'])->first();
            if (!$existing) {
                $id = DB::table('permissions')->insertGetId([
                    'name' => $p['name'],
                    'slug' => $p['slug'],
                    'module' => $p['module'],
                    'description' => $p['description'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $insertedSlugs[$p['slug']] = $id;
            }
        }

        // Attach new permissions to existing Store Manager roles
        $managerRoles = DB::table('roles')->where('slug', 'store-manager')->get();
        foreach ($managerRoles as $role) {
            foreach ($insertedSlugs as $slug => $permId) {
                $exists = DB::table('permission_role')
                    ->where('role_id', $role->id)
                    ->where('permission_id', $permId)
                    ->exists();

                if (!$exists) {
                    DB::table('permission_role')->insert([
                        'role_id' => $role->id,
                        'permission_id' => $permId,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $slugs = [
            'purchases.view', 'purchases.create', 'purchases.edit', 'purchases.delete', 'purchases.grn', 'purchases.return',
            'accounts.view', 'accounts.manage', 'accounts.vouchers', 'accounts.reports',
            'hr.employees', 'hr.attendance', 'hr.payroll',
        ];

        $permIds = DB::table('permissions')->whereIn('slug', $slugs)->pluck('id');
        DB::table('permission_role')->whereIn('permission_id', $permIds)->delete();
        DB::table('permissions')->whereIn('slug', $slugs)->delete();
    }
};
