<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Shop;
use App\Services\ModuleRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isSuperAdmin() || $user->isBusinessOwner() || $user->isShopAdmin() || $user->hasPermission('roles.manage'), 403);

        $shopId = $user->isSuperAdmin() ? $request->query('shop_id') : $user->shop_id;

        $query = Role::with(['shop', 'creator'])
            ->withCount(['users', 'permissions']);

        if ($user->isSuperAdmin()) {
            if ($shopId) {
                $query->where('shop_id', $shopId);
            }
        } elseif ($user->isBusinessOwner()) {
            $branchIds = $user->shop ? $user->shop->getAllBranchIds() : [];
            $query->whereIn('shop_id', $branchIds);
        } else {
            $query->where('shop_id', $user->shop_id);
        }

        $roles = $query->orderBy('name')->get();

        return view('roles.index', [
            'roles' => $roles,
            'shopId' => $shopId,
            'shops' => $user->isSuperAdmin() ? Shop::orderBy('name')->get() : collect(),
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isSuperAdmin() || $user->isBusinessOwner() || $user->isShopAdmin() || $user->hasPermission('roles.manage'), 403);

        $targetShop = $user->isSuperAdmin()
            ? (Shop::find($request->query('shop_id')) ?? Shop::first())
            : $user->shop;

        $permissionsByModule = ModuleRegistry::getPermissionsGroupedByModule($targetShop?->business_type);
        $moduleDefinitions = ModuleRegistry::getModuleDefinitions();

        return view('roles.form', [
            'role' => new Role,
            'permissionsByModule' => $permissionsByModule,
            'moduleDefinitions' => $moduleDefinitions,
            'rolePermissionIds' => [],
            'shops' => $user->isSuperAdmin() ? Shop::where('status', 'active')->orderBy('name')->get() : collect(),
            'targetShop' => $targetShop,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isSuperAdmin() || $user->isBusinessOwner() || $user->isShopAdmin() || $user->hasPermission('roles.manage'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'shop_id' => ['nullable', 'exists:shops,id'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $shopId = $user->isSuperAdmin()
            ? (int) ($validated['shop_id'] ?? session('dashboard_shop_id') ?? Shop::first()?->id)
            : $user->shop_id;

        $targetShop = Shop::find($shopId);

        $baseSlug = Str::slug($validated['name']);
        $slug = $baseSlug;
        $counter = 1;
        while (Role::where('shop_id', $shopId)->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $role = Role::create([
            'shop_id' => $shopId,
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'created_by' => $user->id,
        ]);

        // Filter permissions to only those enabled for this business type
        if (! empty($validated['permissions'])) {
            $allowedModules = ModuleRegistry::getModulesForType($targetShop?->business_type ?? '');
            $validPermissionIds = Permission::whereIn('module', $allowedModules)
                ->whereIn('id', $validated['permissions'])
                ->pluck('id')
                ->all();

            $role->permissions()->sync($validPermissionIds);
        }

        return redirect()->route('roles.index')->with('success', "Role '{$role->name}' created successfully with configured rights.");
    }

    public function edit(Request $request, Role $role): View
    {
        $user = $request->user();
        $this->authorizeRoleAccess($user, $role);

        $targetShop = $role->shop ?? $user->shop;
        $permissionsByModule = ModuleRegistry::getPermissionsGroupedByModule($targetShop?->business_type);
        $moduleDefinitions = ModuleRegistry::getModuleDefinitions();
        $rolePermissionIds = $role->permissions()->pluck('permissions.id')->toArray();

        return view('roles.form', [
            'role' => $role,
            'permissionsByModule' => $permissionsByModule,
            'moduleDefinitions' => $moduleDefinitions,
            'rolePermissionIds' => $rolePermissionIds,
            'shops' => $user->isSuperAdmin() ? Shop::where('status', 'active')->orderBy('name')->get() : collect(),
            'targetShop' => $targetShop,
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeRoleAccess($user, $role);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        $targetShop = $role->shop ?? $user->shop;
        $allowedModules = ModuleRegistry::getModulesForType($targetShop?->business_type ?? '');
        $validPermissionIds = Permission::whereIn('module', $allowedModules)
            ->whereIn('id', $validated['permissions'] ?? [])
            ->pluck('id')
            ->all();

        $role->permissions()->sync($validPermissionIds);

        return redirect()->route('roles.index')->with('success', "Role '{$role->name}' updated successfully.");
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeRoleAccess($user, $role);

        $assignedUsersCount = $role->users()->count();
        if ($assignedUsersCount > 0) {
            return back()->with('error', "Cannot delete role '{$role->name}' because it is assigned to {$assignedUsersCount} staff account(s). Please reassign them first.");
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', "Role '{$role->name}' deleted successfully.");
    }

    private function authorizeRoleAccess($user, Role $role): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        if ($user->shop) {
            $allowedShopIds = $user->shop->getAllBranchIds();
            if (in_array($role->shop_id, $allowedShopIds, true)) {
                return;
            }
        }

        abort(403, 'Unauthorized. You cannot manage roles from another business.');
    }
}
