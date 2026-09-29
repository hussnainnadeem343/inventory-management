<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isSuperAdmin() || $user->isShopAdmin() || $user->hasPermission('roles.manage'), 403);

        $shopId = $user->isSuperAdmin() ? $request->query('shop_id') : $user->shop_id;

        $query = Role::with(['shop', 'creator'])
            ->withCount(['users', 'permissions']);

        if ($shopId) {
            $query->where(function ($q) use ($shopId) {
                $q->where('shop_id', $shopId)->orWhereNull('shop_id');
            });
        } elseif (! $user->isSuperAdmin()) {
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
        abort_unless($user->isSuperAdmin() || $user->isShopAdmin() || $user->hasPermission('roles.manage'), 403);

        $permissionsByModule = Permission::all()->groupBy('module');

        return view('roles.form', [
            'role' => new Role,
            'permissionsByModule' => $permissionsByModule,
            'rolePermissionIds' => [],
            'shops' => $user->isSuperAdmin() ? Shop::where('status', 'active')->orderBy('name')->get() : collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isSuperAdmin() || $user->isShopAdmin() || $user->hasPermission('roles.manage'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'shop_id' => ['nullable', 'exists:shops,id'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $shopId = $user->isSuperAdmin()
            ? (int) ($validated['shop_id'] ?? session('dashboard_shop_id') ?? Shop::first()->id)
            : $user->shop_id;

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

        if (! empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        return redirect()->route('roles.index')->with('success', "Role '{$role->name}' created successfully with configured permissions.");
    }

    public function edit(Request $request, Role $role): View
    {
        $user = $request->user();
        $this->authorizeRoleAccess($user, $role);

        $permissionsByModule = Permission::all()->groupBy('module');
        $rolePermissionIds = $role->permissions()->pluck('permissions.id')->toArray();

        return view('roles.form', [
            'role' => $role,
            'permissionsByModule' => $permissionsByModule,
            'rolePermissionIds' => $rolePermissionIds,
            'shops' => $user->isSuperAdmin() ? Shop::where('status', 'active')->orderBy('name')->get() : collect(),
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

        $role->permissions()->sync($validated['permissions'] ?? []);

        return redirect()->route('roles.index')->with('success', "Role '{$role->name}' updated successfully.");
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeRoleAccess($user, $role);

        $assignedUsersCount = $role->users()->count();
        if ($assignedUsersCount > 0) {
            return back()->with('error', "Cannot delete '{$role->name}' because it is assigned to {$assignedUsersCount} user(s). Reassign them first.");
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', "Role '{$role->name}' deleted successfully.");
    }

    private function authorizeRoleAccess($user, Role $role): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        if ($user->isShopAdmin() && $role->shop_id === $user->shop_id) {
            return;
        }

        abort(403, 'Unauthorized. You cannot manage roles from another store.');
    }
}
