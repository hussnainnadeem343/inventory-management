<?php

namespace App\Services;

use App\Models\Permission;
use Illuminate\Support\Collection;

class ModuleRegistry
{
    public const TYPE_RETAIL = 'retail';
    public const TYPE_LUBE = 'lube';

    /**
     * Canonical business types supported by DENFIN ERP.
     */
    public static function getBusinessTypes(): array
    {
        return [
            self::TYPE_RETAIL => [
                'name' => 'Retail & General Business',
                'description' => 'Complete enterprise retail management: Point of Sale, Product Catalog, Inventory, Procurement, Finance, HR & Analytics.',
                'badge' => 'primary',
            ],
            self::TYPE_LUBE => [
                'name' => 'Lube (Oil Changing) Station',
                'description' => 'Specialized auto lube & pit stop station: Includes all retail features plus Pit POS, Vehicle History, Oil Due Reminders, Technicians & Drum Decanting.',
                'badge' => 'warning',
            ],
        ];
    }

    /**
     * Normalize stored business type string to a canonical key.
     */
    public static function normalizeBusinessType(?string $type): string
    {
        if (empty($type)) {
            return self::TYPE_RETAIL;
        }

        $lower = strtolower(trim($type));
        if (str_contains($lower, 'lube') || str_contains($lower, 'oil')) {
            return self::TYPE_LUBE;
        }

        return self::TYPE_RETAIL;
    }

    /**
     * Get module identifiers enabled for a business type.
     */
    public static function getModulesForType(string $type): array
    {
        $normalized = self::normalizeBusinessType($type);

        $commonModules = [
            'pos',
            'products',
            'stock',
            'purchases',
            'accounts',
            'hr',
            'reports',
            'catalog',
            'users',
            'roles',
        ];

        if ($normalized === self::TYPE_LUBE) {
            $commonModules[] = 'lube';
        }

        return $commonModules;
    }

    /**
     * Resolve module identifier or alias to underlying DB module names.
     */
    public static function resolveModuleGroup(string $module): array
    {
        return match (strtolower(trim($module))) {
            'inventory' => ['stock', 'products', 'catalog'],
            'finance' => ['accounts'],
            'sales' => ['pos'],
            'management' => ['users', 'roles'],
            default => [strtolower(trim($module))],
        };
    }

    /**
     * Check if a module is available for a business type.
     */
    public static function isModuleAvailable(string $module, ?string $businessType): bool
    {
        $allowed = self::getModulesForType($businessType ?? self::TYPE_RETAIL);
        $resolved = self::resolveModuleGroup($module);

        foreach ($resolved as $m) {
            if (in_array($m, $allowed, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Metadata definitions for all modules.
     */
    public static function getModuleDefinitions(): array
    {
        return [
            'pos' => [
                'title' => 'Sales & POS',
                'icon' => 'cart3',
                'badge' => 'primary',
                'description' => 'Counter sales billing, item discounts, invoices & receipts',
            ],
            'inventory' => [
                'title' => 'Inventory & Products',
                'icon' => 'boxes',
                'badge' => 'success',
                'description' => 'Product catalog, stock levels, godowns, transfers, brands, categories & units',
            ],
            'purchases' => [
                'title' => 'Purchases & Procurement',
                'icon' => 'truck',
                'badge' => 'primary',
                'description' => 'Suppliers, Requisitions (PR), Orders (PO), Gate Pass, GRN & Invoices',
            ],
            'accounts' => [
                'title' => 'Finance & Accounts',
                'icon' => 'wallet2',
                'badge' => 'success',
                'description' => 'Chart of accounts, ledgers, vouchers, daily expenses & P&L statements',
            ],
            'hr' => [
                'title' => 'HR & Payroll',
                'icon' => 'person-workspace',
                'badge' => 'dark',
                'description' => 'Employee profiles, designations, daily attendance & monthly payroll',
            ],
            'reports' => [
                'title' => 'Reports & Analytics',
                'icon' => 'graph-up-arrow',
                'badge' => 'warning',
                'description' => 'Sales revenue, profit margins, stock movements & expiry tracking',
            ],
            'catalog' => [
                'title' => 'Catalog Management',
                'icon' => 'tags',
                'badge' => 'secondary',
                'description' => 'Brands, categories and units of measurement',
            ],
            'users' => [
                'title' => 'Staff Directory',
                'icon' => 'people',
                'badge' => 'info',
                'description' => 'Business staff accounts and credentials management',
            ],
            'roles' => [
                'title' => 'Roles & Security',
                'icon' => 'shield-lock',
                'badge' => 'danger',
                'description' => 'Custom role creation and granular screen rights configuration',
            ],
            'lube' => [
                'title' => 'Auto Lube Station',
                'icon' => 'fuel-pump',
                'badge' => 'warning',
                'description' => 'Pit counter POS, vehicle history, oil due reminders & technicians',
            ],
            'products' => [
                'title' => 'Product Catalog',
                'icon' => 'box-seam',
                'badge' => 'info',
                'description' => 'Product records, pricing, SKUs, barcodes & Excel imports',
            ],
            'stock' => [
                'title' => 'Stock & Inventory',
                'icon' => 'boxes',
                'badge' => 'success',
                'description' => 'Stock levels, batch restocks, quick sales, returns, damages & transfers',
            ],
        ];
    }

    /**
     * Get permissions grouped by module, filtered by business type availability.
     */
    public static function getPermissionsGroupedByModule(?string $businessType = null): Collection
    {
        $allowedModules = self::getModulesForType($businessType ?? self::TYPE_RETAIL);

        $moduleOrder = [
            'pos',
            'inventory',
            'purchases',
            'accounts',
            'hr',
            'reports',
            'users',
            'roles',
            'lube',
        ];

        $grouped = Permission::whereIn('module', $allowedModules)
            ->orderBy('id')
            ->get()
            ->groupBy(function ($perm) {
                if (in_array($perm->module, ['stock', 'products', 'catalog'], true)) {
                    return 'inventory';
                }

                return $perm->module;
            });

        return collect($moduleOrder)
            ->filter(fn ($m) => $grouped->has($m))
            ->mapWithKeys(fn ($m) => [$m => $grouped->get($m)]);
    }
}
