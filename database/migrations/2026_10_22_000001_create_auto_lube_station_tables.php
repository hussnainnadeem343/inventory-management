<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Vehicles table
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('plate_number')->index();
            $table->string('vehicle_type')->default('sedan');
            $table->string('make');
            $table->string('model');
            $table->string('year')->nullable();
            $table->string('customer_name');
            $table->string('customer_phone')->nullable()->index();
            $table->text('notes')->nullable();
            $table->unsignedInteger('last_odometer')->nullable();
            $table->date('last_service_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['shop_id', 'plate_number']);
        });

        // 2. Pit Technicians table
        Schema::create('pit_technicians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('bay_number')->nullable();
            $table->string('commission_type')->default('fixed'); // fixed, percentage, none
            $table->decimal('commission_rate', 10, 2)->default(0);
            $table->string('status')->default('active'); // active, inactive
            $table->timestamps();
            $table->softDeletes();

            $table->index(['shop_id', 'status']);
        });

        // 3. Lube Service Records table
        Schema::create('lube_service_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('pit_technician_id')->nullable()->constrained('pit_technicians')->nullOnDelete();
            $table->unsignedInteger('current_odometer')->nullable();
            $table->unsignedInteger('interval_km')->nullable()->default(5000);
            $table->unsignedInteger('next_due_odometer')->nullable();
            $table->date('next_due_date')->nullable();
            $table->string('oil_grade')->nullable()->default('5W-30');
            $table->json('inspection_checklist')->nullable();
            $table->decimal('labor_amount', 12, 2)->default(0);
            $table->decimal('technician_commission', 12, 2)->default(0);
            $table->string('commission_status')->default('pending'); // pending, paid
            $table->timestamp('commission_paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['shop_id', 'commission_status']);
            $table->index(['next_due_date', 'next_due_odometer']);
        });

        // 4. Lube Service Packages table
        Schema::create('lube_service_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('vehicle_type')->nullable();
            $table->decimal('package_price', 12, 2)->default(0);
            $table->decimal('labor_amount', 12, 2)->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['shop_id', 'is_active']);
        });

        // 5. Lube Service Package Items table
        Schema::create('lube_service_package_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('lube_service_packages')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->timestamps();
        });

        // 6. Drum Decantings table
        Schema::create('drum_decantings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('drum_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('loose_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->decimal('drums_used', 8, 2)->default(1.00);
            $table->decimal('liters_produced', 10, 2)->default(208.00);
            $table->decimal('shrinkage_liters', 8, 2)->default(0.00);
            $table->decimal('cost_per_liter', 12, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['shop_id', 'created_at']);
        });

        // 7. Seed Lube Station Permissions
        $now = now();
        $permissions = [
            ['name' => 'Lube Analytics', 'slug' => 'lube.analytics', 'module' => 'lube', 'description' => 'View Lube Station analytics and metrics'],
            ['name' => 'Lube Counter POS', 'slug' => 'lube.pos', 'module' => 'lube', 'description' => 'Access Auto Lube Service Counter and bill services'],
            ['name' => 'Lube Service Packages', 'slug' => 'lube.packages', 'module' => 'lube', 'description' => 'Manage lube service packages and bundles'],
            ['name' => 'Lube Vehicle History', 'slug' => 'lube.vehicles', 'module' => 'lube', 'description' => 'View and manage vehicle maintenance history'],
            ['name' => 'Lube Oil Due Reminders', 'slug' => 'lube.reminders', 'module' => 'lube', 'description' => 'Track and contact vehicles due for oil change'],
            ['name' => 'Lube Pit Technicians', 'slug' => 'lube.technicians', 'module' => 'lube', 'description' => 'Manage pit crew mechanics and rates'],
            ['name' => 'Lube Mechanic Commissions', 'slug' => 'lube.commissions', 'module' => 'lube', 'description' => 'View and settle mechanic commissions'],
            ['name' => 'Lube Drum Decanting', 'slug' => 'lube.decanting', 'module' => 'lube', 'description' => 'Perform drum decanting to loose liters'],
        ];

        foreach ($permissions as $perm) {
            $existing = DB::table('permissions')->where('slug', $perm['slug'])->first();
            if (!$existing) {
                $permId = DB::table('permissions')->insertGetId(array_merge($perm, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));

                // Auto-grant to admin and store-manager roles
                $roles = DB::table('roles')->whereIn('slug', ['admin', 'store-manager'])->get();
                foreach ($roles as $role) {
                    DB::table('permission_role')->insertOrIgnore([
                        'role_id' => $role->id,
                        'permission_id' => $permId,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drum_decantings');
        Schema::dropIfExists('lube_service_package_items');
        Schema::dropIfExists('lube_service_packages');
        Schema::dropIfExists('lube_service_records');
        Schema::dropIfExists('pit_technicians');
        Schema::dropIfExists('vehicles');

        $lubeSlugs = [
            'lube.analytics', 'lube.pos', 'lube.packages', 'lube.vehicles',
            'lube.reminders', 'lube.technicians', 'lube.commissions', 'lube.decanting'
        ];
        $permIds = DB::table('permissions')->whereIn('slug', $lubeSlugs)->pluck('id');
        DB::table('permission_role')->whereIn('permission_id', $permIds)->delete();
        DB::table('permissions')->whereIn('id', $permIds)->delete();
    }
};
