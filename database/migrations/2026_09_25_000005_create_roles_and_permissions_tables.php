<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->string('description', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['shop_id', 'slug']);
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->string('module', 50);
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->index('module');
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();

            $table->primary(['role_id', 'permission_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('shop_id')->constrained('roles')->nullOnDelete();
        });

        // Seed initial permissions
        $permissions = [
            // Products Module
            ['name' => 'View Products', 'slug' => 'products.view', 'module' => 'products', 'description' => 'Browse and view product catalog details'],
            ['name' => 'Create Product', 'slug' => 'products.create', 'module' => 'products', 'description' => 'Add new products to store catalog'],
            ['name' => 'Edit Product', 'slug' => 'products.edit', 'module' => 'products', 'description' => 'Update product information and prices'],
            ['name' => 'Delete Product', 'slug' => 'products.delete', 'module' => 'products', 'description' => 'Remove products without transaction history'],
            ['name' => 'Import Products', 'slug' => 'products.import', 'module' => 'products', 'description' => 'Bulk import products via Excel / CSV'],

            // Stock Module
            ['name' => 'View Stock', 'slug' => 'stock.view', 'module' => 'stock', 'description' => 'View store stock inventory list'],
            ['name' => 'Add Stock', 'slug' => 'stock.add', 'module' => 'stock', 'description' => 'Restock existing products with new batches'],
            ['name' => 'Quick Sell', 'slug' => 'stock.sell', 'module' => 'stock', 'description' => 'Process single item counter sales from stock table'],
            ['name' => 'Customer Return', 'slug' => 'stock.return', 'module' => 'stock', 'description' => 'Process customer returns and restock items'],
            ['name' => 'Damage & Wastage', 'slug' => 'stock.damage', 'module' => 'stock', 'description' => 'Record damaged, expired or audit loss write-offs'],
            ['name' => 'Exchange Item', 'slug' => 'stock.exchange', 'module' => 'stock', 'description' => 'Exchange one item for another at the counter'],
            ['name' => 'Stock Audit History', 'slug' => 'stock.history', 'module' => 'stock', 'description' => 'Inspect item transaction and movement audit log'],
            ['name' => 'Export Stock', 'slug' => 'stock.export', 'module' => 'stock', 'description' => 'Download complete stock excel report'],

            // POS & Billing Module
            ['name' => 'Access POS', 'slug' => 'pos.access', 'module' => 'pos', 'description' => 'Access fast POS counter billing screen'],
            ['name' => 'Item Discount', 'slug' => 'pos.discount_item', 'module' => 'pos', 'description' => 'Apply discounts to individual items in cart'],
            ['name' => 'Bill Discount', 'slug' => 'pos.discount_bill', 'module' => 'pos', 'description' => 'Apply overall discount or round-off to total invoice'],
            ['name' => 'View Invoices', 'slug' => 'sales.view_invoices', 'module' => 'pos', 'description' => 'Browse invoices history and re-print receipts'],

            // Financial & Analytical Reports
            ['name' => 'Sales & Profit Report', 'slug' => 'reports.sales', 'module' => 'reports', 'description' => 'View sales volume, net revenue and gross profit'],
            ['name' => 'Stock Movement Report', 'slug' => 'reports.stock_movement', 'module' => 'reports', 'description' => 'Analyze stock in, sales, returns and adjustments'],
            ['name' => 'Expiry Alerts Report', 'slug' => 'reports.expiry', 'module' => 'reports', 'description' => 'Track batches expiring in 15, 30 or 60 days'],

            // Catalog
            ['name' => 'Manage Brands & Categories', 'slug' => 'catalog.manage', 'module' => 'catalog', 'description' => 'Create, edit and delete brands and categories'],

            // Staff & Users
            ['name' => 'View Staff', 'slug' => 'users.view', 'module' => 'users', 'description' => 'View staff member directory for the shop'],
            ['name' => 'Manage Staff', 'slug' => 'users.manage', 'module' => 'users', 'description' => 'Create, edit and deactivate staff accounts'],

            // Roles & Permissions
            ['name' => 'Manage Roles', 'slug' => 'roles.manage', 'module' => 'roles', 'description' => 'Create custom roles and configure screen access rights'],
        ];

        $now = now();
        foreach ($permissions as &$p) {
            $p['created_at'] = $now;
            $p['updated_at'] = $now;
        }
        DB::table('permissions')->insert($permissions);

        // Pre-create standard default role templates for all existing active shops
        $allPermissions = DB::table('permissions')->get()->keyBy('slug');
        $shops = DB::table('shops')->get();

        foreach ($shops as $shop) {
            // 1. Cashier Role
            $cashierId = DB::table('roles')->insertGetId([
                'shop_id' => $shop->id,
                'name' => 'Counter Cashier',
                'slug' => 'counter-cashier',
                'description' => 'Front-desk POS billing, cash collection and sales invoices',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $cashierPerms = [
                'pos.access', 'pos.discount_item', 'pos.discount_bill', 'sales.view_invoices',
                'products.view', 'stock.view', 'stock.sell',
            ];
            foreach ($cashierPerms as $slug) {
                if (isset($allPermissions[$slug])) {
                    DB::table('permission_role')->insert([
                        'role_id' => $cashierId,
                        'permission_id' => $allPermissions[$slug]->id,
                    ]);
                }
            }

            // 2. Store Manager Role (all except roles.manage)
            $managerId = DB::table('roles')->insertGetId([
                'shop_id' => $shop->id,
                'name' => 'Store Manager',
                'slug' => 'store-manager',
                'description' => 'Full operational control over catalog, stock, sales and reports',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($allPermissions as $perm) {
                if ($perm->slug !== 'roles.manage') {
                    DB::table('permission_role')->insert([
                        'role_id' => $managerId,
                        'permission_id' => $perm->id,
                    ]);
                }
            }

            // 3. Stock Keeper Role
            $keeperId = DB::table('roles')->insertGetId([
                'shop_id' => $shop->id,
                'name' => 'Stock Keeper',
                'slug' => 'stock-keeper',
                'description' => 'Receiving shipments, restocks, damage write-offs and expiry checks',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $keeperPerms = [
                'products.view', 'stock.view', 'stock.add', 'stock.return', 'stock.damage',
                'stock.exchange', 'stock.history', 'stock.export', 'reports.stock_movement',
                'reports.expiry',
            ];
            foreach ($keeperPerms as $slug) {
                if (isset($allPermissions[$slug])) {
                    DB::table('permission_role')->insert([
                        'role_id' => $keeperId,
                        'permission_id' => $allPermissions[$slug]->id,
                    ]);
                }
            }

            // 4. Auditor / Accountant Role
            $auditorId = DB::table('roles')->insertGetId([
                'shop_id' => $shop->id,
                'name' => 'Auditor / Accountant',
                'slug' => 'auditor-accountant',
                'description' => 'Reviewing financial revenue, profit margins and stock movements',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $auditorPerms = [
                'reports.sales', 'reports.stock_movement', 'reports.expiry',
                'sales.view_invoices', 'stock.view', 'stock.history',
            ];
            foreach ($auditorPerms as $slug) {
                if (isset($allPermissions[$slug])) {
                    DB::table('permission_role')->insert([
                        'role_id' => $auditorId,
                        'permission_id' => $allPermissions[$slug]->id,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });

        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
