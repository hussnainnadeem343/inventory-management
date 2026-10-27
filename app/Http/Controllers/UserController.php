<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Role;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $currentUser = $request->user();
        $shopId = $currentUser->isSuperAdmin() ? $request->query('shop_id') : $currentUser->shop_id;
        $search = trim((string) $request->query('search'));
        $role = $request->query('role');
        $status = $request->query('status');

        $perPage = in_array((int) $request->query('per_page', 10), [10, 20, 30, 50, 100], true) ? (int) $request->query('per_page', 10) : 10;

        $query = User::with(['shop', 'customRole'])
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status, fn ($q, $status) => $q->where('status', $status));

        if ($currentUser->isSuperAdmin()) {
            // Super Admin strictly views and manages Business Owners and Shop Admins
            if ($role && in_array($role, ['business_owner', 'shop_admin'], true)) {
                $query->where('role', $role);
            } else {
                $query->whereIn('role', ['business_owner', 'shop_admin']);
            }
            if ($shopId) {
                $query->where('shop_id', $shopId);
            }
        } elseif ($currentUser->isBusinessOwner()) {
            $branchIds = $currentUser->shop ? $currentUser->shop->getAllBranchIds() : [];
            if ($shopId && in_array((int) $shopId, $branchIds, true)) {
                $query->where('shop_id', $shopId);
            } else {
                $query->whereIn('shop_id', $branchIds);
            }
            if ($role) {
                $query->where('role', $role);
            }
            $query->where('role', '!=', 'super_admin');
        } else {
            // Shop Admin sees users of their own branch only, excluding super_admin AND business_owner
            if ($role) {
                $query->where('role', $role);
            }
            $query->where('shop_id', $currentUser->shop_id)
                ->whereNotIn('role', ['super_admin', 'business_owner']);
        }

        $users = $query->latest()->paginate($perPage)->withQueryString();

        $business = $currentUser->shop ? $currentUser->shop->getRootBusiness() : null;

        $shops = collect();
        if ($currentUser->isSuperAdmin()) {
            $shops = Shop::orderBy('name')->get();
        } elseif ($currentUser->isBusinessOwner() && $business) {
            $shops = Shop::whereIn('id', $business->getAllBranchIds())->orderBy('name')->get();
        }

        return view('users.index', [
            'users' => $users,
            'perPage' => $perPage,
            'search' => $search,
            'role' => $role,
            'status' => $status,
            'shopId' => $shopId,
            'shops' => $shops,
            'business' => $business,
            'canAddUser' => $business ? $business->canAddUser() : true,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $currentUser = $request->user();
        $business = $currentUser->shop ? $currentUser->shop->getRootBusiness() : null;

        if ($business && ! $currentUser->isSuperAdmin() && ! $business->canAddUser()) {
            return redirect()->route('users.index')->with(
                'error',
                "User creation limit reached ({$business->max_users} users allowed on your subscription). Please contact support to upgrade."
            );
        }

        $shopId = $currentUser->isSuperAdmin() ? session('dashboard_shop_id') : $currentUser->shop_id;
        $customRoles = Role::query()
            ->withCount('permissions')
            ->when($shopId, fn ($q) => $q->where(fn ($sub) => $sub->where('shop_id', $shopId)->orWhereNull('shop_id')))
            ->orderBy('name')
            ->get();

        $shops = collect();
        if ($currentUser->isSuperAdmin()) {
            $shops = Shop::where('status', 'active')->orderBy('name')->get();
        } elseif ($currentUser->isBusinessOwner() && $business) {
            $shops = Shop::whereIn('id', $business->getAllBranchIds())->where('status', 'active')->orderBy('name')->get();
        }

        return view('users.form', [
            'user' => new User,
            'shops' => $shops,
            'customRoles' => $customRoles,
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $currentUser = $request->user();
        $business = $currentUser->shop ? $currentUser->shop->getRootBusiness() : null;

        if ($business && ! $currentUser->isSuperAdmin() && ! $business->canAddUser()) {
            return back()->withInput()->with(
                'error',
                "User creation limit reached ({$business->max_users} users allowed on your subscription). Please contact support to upgrade."
            );
        }

        $data = $request->validated();
        unset($data['assigned_role']);
        $data['role_id'] = $request->input('role_id', null);

        if ($currentUser->isBusinessOwner() && $business) {
            $branchIds = $business->getAllBranchIds();
            if (empty($data['shop_id']) || ! in_array((int) $data['shop_id'], $branchIds, true)) {
                $data['shop_id'] = $currentUser->shop_id;
            }
            $data['role'] = in_array($data['role'] ?? '', ['shop_admin', 'staff', 'user']) ? $data['role'] : 'staff';
        } elseif ($currentUser->isShopAdmin()) {
            $data['shop_id'] = $currentUser->shop_id;
            $data['role'] = in_array($data['role'] ?? '', ['shop_admin', 'staff', 'user']) ? $data['role'] : 'staff';
        }

        User::create($data);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit(Request $request, User $user): View
    {
        $currentUser = $request->user();
        $this->authorizeUserManagement($currentUser, $user);

        $business = $currentUser->shop ? $currentUser->shop->getRootBusiness() : null;
        $shopId = $currentUser->isSuperAdmin() ? $user->shop_id : $currentUser->shop_id;
        $customRoles = Role::query()
            ->withCount('permissions')
            ->when($shopId, fn ($q) => $q->where(fn ($sub) => $sub->where('shop_id', $shopId)->orWhereNull('shop_id')))
            ->orderBy('name')
            ->get();

        $shops = collect();
        if ($currentUser->isSuperAdmin()) {
            $shops = Shop::orderBy('name')->get();
        } elseif ($currentUser->isBusinessOwner() && $business) {
            $shops = Shop::whereIn('id', $business->getAllBranchIds())->where('status', 'active')->orderBy('name')->get();
        }

        return view('users.form', [
            'user' => $user,
            'shops' => $shops,
            'customRoles' => $customRoles,
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $currentUser = $request->user();
        $this->authorizeUserManagement($currentUser, $user);

        $business = $currentUser->shop ? $currentUser->shop->getRootBusiness() : null;

        $data = $request->validated();
        unset($data['assigned_role']);
        $data['role_id'] = $request->input('role_id', null);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        if ($currentUser->is($user) && ($data['status'] === 'inactive' || ($currentUser->isSuperAdmin() && $data['role'] !== 'super_admin'))) {
            return back()->with('error', 'You cannot deactivate or remove the Super Admin role from your own account.');
        }

        if ($currentUser->isBusinessOwner() && $business) {
            $branchIds = $business->getAllBranchIds();
            if (! empty($data['shop_id']) && in_array((int) $data['shop_id'], $branchIds, true)) {
                // allowed to reassign branch within business
            } else {
                $data['shop_id'] = $user->shop_id;
            }
            if (! $currentUser->is($user)) {
                $data['role'] = in_array($data['role'] ?? '', ['shop_admin', 'staff', 'user']) ? $data['role'] : 'staff';
            }
        } elseif ($currentUser->isShopAdmin()) {
            $data['shop_id'] = $currentUser->shop_id;
            $data['role'] = in_array($data['role'] ?? '', ['shop_admin', 'staff', 'user']) ? $data['role'] : 'staff';
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $currentUser = $request->user();
        $this->authorizeUserManagement($currentUser, $user);

        if ($currentUser->is($user)) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Super Admin accounts cannot be deleted; set them inactive instead.');
        }

        if ($user->isBusinessOwner() && ! $currentUser->isSuperAdmin()) {
            return back()->with('error', 'Business Owner accounts can only be managed by Super Admin.');
        }

        if ($user->inventoryItems()->exists() || $user->brands()->exists() || $user->categories()->exists()) {
            $user->update(['status' => 'inactive']);

            return back()->with('success', 'User has linked business records and was set to inactive instead of deleted.');
        }

        $user->delete();

        return back()->with('success', 'User deleted successfully.');
    }

    private function authorizeUserManagement(User $currentUser, User $targetUser): void
    {
        if ($currentUser->isSuperAdmin()) {
            return;
        }

        if ($currentUser->isBusinessOwner() && $currentUser->shop) {
            $branchIds = $currentUser->shop->getAllBranchIds();
            if (in_array((int) $targetUser->shop_id, $branchIds, true) && ! $targetUser->isSuperAdmin()) {
                if ($targetUser->isBusinessOwner() && ! $currentUser->is($targetUser)) {
                    abort(403, 'Unauthorized action. Only super admins can manage other business owners.');
                }
                return;
            }
            abort(403, 'Unauthorized action. You can only manage staff within your own business branches.');
        }

        if ($currentUser->isShopAdmin()) {
            if ((int) $targetUser->shop_id === (int) $currentUser->shop_id && ! in_array($targetUser->role, ['super_admin', 'business_owner'], true)) {
                return;
            }
            abort(403, 'Unauthorized action. You can only manage staff within your own branch.');
        }

        abort(403, 'Unauthorized action. You do not have permission to manage staff.');
    }
}
