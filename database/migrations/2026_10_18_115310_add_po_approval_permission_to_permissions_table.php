<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();
        $perm = DB::table('permissions')->where('slug', 'purchases.orders.approve')->first();
        
        if (!$perm) {
            $permId = DB::table('permissions')->insertGetId([
                'name' => 'Approve Purchase Orders',
                'slug' => 'purchases.orders.approve',
                'module' => 'purchases',
                'description' => 'Authorize and approve draft/pending purchase orders for fulfillment',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Auto-grant to store-manager roles
            $roles = DB::table('roles')->whereIn('slug', ['store-manager', 'admin'])->get();
            foreach ($roles as $role) {
                DB::table('permission_role')->insertOrIgnore([
                    'role_id' => $role->id,
                    'permission_id' => $permId,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $perm = DB::table('permissions')->where('slug', 'purchases.orders.approve')->first();
        if ($perm) {
            DB::table('permission_role')->where('permission_id', $perm->id)->delete();
            DB::table('permissions')->where('id', $perm->id)->delete();
        }
    }
};
