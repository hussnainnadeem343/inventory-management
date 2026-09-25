<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
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

        $query = User::with('shop')
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($role, fn ($q, $role) => $q->where('role', $role))
            ->when($status, fn ($q, $status) => $q->where('status', $status));

        if ($currentUser->isSuperAdmin()) {
            if ($shopId) {
                $query->where('shop_id', $shopId);
            }
        } else {
            // Shop Admin sees users of their own shop, excluding any super admins
            $query->where('shop_id', $currentUser->shop_id)
                ->where('role', '!=', 'super_admin');
        }

        $users = $query->latest()->paginate($perPage)->withQueryString();

        return view('users.index', [
            'users' => $users,
            'perPage' => $perPage,
            'search' => $search,
            'role' => $role,
            'status' => $status,
            'shopId' => $shopId,
            'shops' => $currentUser->isSuperAdmin() ? Shop::orderBy('name')->get() : collect(),
        ]);
    }

    public function create(Request $request): View
    {
        $currentUser = $request->user();

        return view('users.form', [
            'user' => new User,
            'shops' => $currentUser->isSuperAdmin() ? Shop::where('status', 'active')->orderBy('name')->get() : collect(),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $currentUser = $request->user();
        $data = $request->validated();

        if ($currentUser->isShopAdmin()) {
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

        return view('users.form', [
            'user' => $user,
            'shops' => $currentUser->isSuperAdmin() ? Shop::orderBy('name')->get() : collect(),
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $currentUser = $request->user();
        $this->authorizeUserManagement($currentUser, $user);

        $data = $request->validated();
        if (empty($data['password'])) {
            unset($data['password']);
        }

        if ($currentUser->is($user) && ($data['status'] === 'inactive' || ($currentUser->isSuperAdmin() && $data['role'] !== 'super_admin'))) {
            return back()->with('error', 'You cannot deactivate or remove the Super Admin role from your own account.');
        }

        if ($currentUser->isShopAdmin()) {
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

        if ($currentUser->isShopAdmin() && $targetUser->shop_id === $currentUser->shop_id && ! $targetUser->isSuperAdmin()) {
            return;
        }

        abort(403, 'Unauthorized action. You can only manage staff within your own shop.');
    }
}
