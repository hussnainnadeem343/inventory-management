<?php

namespace App\Http\Controllers;

use App\Http\Requests\BranchRequest;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BranchController extends Controller
{
    /**
     * Ensure only Super Admin or Business Owner can manage branches.
     */
    protected function authorizeBranchManagement(Request $request): void
    {
        $user = $request->user();
        if ($user->isSuperAdmin() || $user->isBusinessOwner()) {
            return;
        }

        abort(403, 'Unauthorized. Only super admins and business owners can manage branches.');
    }

    /**
     * Resolve the active root business for the user.
     */
    protected function resolveBusiness(Request $request): ?Shop
    {
        $user = $request->user();
        if ($user->isSuperAdmin()) {
            $businessId = $request->input('business_id', $request->query('business_id', $request->session()->get('dashboard_shop_id')));
            if ($businessId) {
                $shop = Shop::find($businessId);
                if ($shop) {
                    return $shop->getRootBusiness();
                }
            }
            return Shop::whereNull('parent_id')->first();
        }

        return $user->shop ? $user->shop->getRootBusiness() : null;
    }

    /**
     * Display a listing of branches belonging to the current business.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $this->authorizeBranchManagement($request);

        $business = $this->resolveBusiness($request);

        if (! $business) {
            if ($request->user()->isSuperAdmin()) {
                return redirect()->route('shops.create')->with('info', 'Please register a business first before managing branches.');
            }
            abort(403, 'No associated business found.');
        }

        $branches = Shop::where('parent_id', $business->id)
            ->withCount(['users', 'inventoryItems'])
            ->latest()
            ->get();

        $totalBranchesCount = $business->totalBranchesCount();
        $maxBranches = $business->max_branches;
        $totalUsersCount = $business->totalUsersCount();
        $maxUsers = $business->max_users;
        $canAddBranch = $business->canAddBranch();

        $allBusinesses = $request->user()->isSuperAdmin()
            ? Shop::whereNull('parent_id')->orderBy('name')->get()
            : collect();

        return view('branches.index', compact(
            'business',
            'branches',
            'totalBranchesCount',
            'maxBranches',
            'totalUsersCount',
            'maxUsers',
            'canAddBranch',
            'allBusinesses'
        ));
    }

    /**
     * Show the form for creating a new branch.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $this->authorizeBranchManagement($request);

        $business = $this->resolveBusiness($request);

        if (! $business) {
            abort(403, 'No associated business found.');
        }

        if (! $business->canAddBranch()) {
            return redirect()->route('branches.index')->with(
                'error',
                "Branch limit reached ({$business->totalBranchesCount()} / {$business->max_branches} allowed). Please contact support to upgrade your subscription."
            );
        }

        return view('branches.form', [
            'business' => $business,
            'branch' => new Shop,
            'canAddUser' => $business->canAddUser(),
        ]);
    }

    /**
     * Store a newly created branch in storage.
     */
    public function store(BranchRequest $request): RedirectResponse
    {
        $this->authorizeBranchManagement($request);

        $business = $this->resolveBusiness($request);

        if (! $business) {
            abort(403, 'No associated business found.');
        }

        if (! $business->canAddBranch()) {
            return back()->withInput()->with(
                'error',
                "Branch limit reached ({$business->max_branches} allowed). Please upgrade your subscription to open more branches."
            );
        }

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $request, $business): void {
            $branch = Shop::create([
                'parent_id' => $business->id,
                'name' => $validated['name'],
                'code' => strtoupper($validated['code']),
                'business_type' => $business->business_type,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'status' => $validated['status'],
                'max_branches' => 1,
                'max_users' => 5,
            ]);

            if ($request->filled('admin_name')) {
                if ($business->canAddUser()) {
                    User::create([
                        'shop_id' => $branch->id,
                        'name' => $validated['admin_name'],
                        'username' => $validated['admin_username'],
                        'email' => $validated['admin_email'] ?? null,
                        'password' => $validated['admin_password'],
                        'role' => 'shop_admin',
                        'status' => 'active',
                    ]);
                }
            }
        });

        $redirectParams = $request->user()->isSuperAdmin() ? ['business_id' => $business->id] : [];

        return redirect()->route('branches.index', $redirectParams)->with('success', 'Branch created successfully.');
    }

    /**
     * Show the form for editing the specified branch.
     */
    public function edit(Request $request, Shop $branch): View
    {
        $this->authorizeBranchManagement($request);

        $user = $request->user();
        $business = $branch->getRootBusiness();

        if (! $user->isSuperAdmin()) {
            $userBusiness = $user->shop ? $user->shop->getRootBusiness() : null;
            if (! $userBusiness || $business->id !== $userBusiness->id) {
                abort(403, 'Unauthorized access to this branch.');
            }
        }

        return view('branches.form', [
            'business' => $business,
            'branch' => $branch,
            'canAddUser' => $business->canAddUser(),
        ]);
    }

    /**
     * Update the specified branch in storage.
     */
    public function update(BranchRequest $request, Shop $branch): RedirectResponse
    {
        $this->authorizeBranchManagement($request);

        $user = $request->user();
        $business = $branch->getRootBusiness();

        if (! $user->isSuperAdmin()) {
            $userBusiness = $user->shop ? $user->shop->getRootBusiness() : null;
            if (! $userBusiness || $business->id !== $userBusiness->id) {
                abort(403, 'Unauthorized access to this branch.');
            }
        }

        $validated = $request->validated();
        $validated['code'] = strtoupper($validated['code']);

        $branch->update($validated);

        $redirectParams = $request->user()->isSuperAdmin() ? ['business_id' => $business->id] : [];

        return redirect()->route('branches.index', $redirectParams)->with('success', 'Branch updated successfully.');
    }

    /**
     * Remove or deactivate the specified branch.
     */
    public function destroy(Request $request, Shop $branch): RedirectResponse
    {
        $this->authorizeBranchManagement($request);

        $user = $request->user();
        $business = $branch->getRootBusiness();

        if (! $user->isSuperAdmin()) {
            $userBusiness = $user->shop ? $user->shop->getRootBusiness() : null;
            if (! $userBusiness || $branch->parent_id !== $userBusiness->id) {
                abort(403, 'Unauthorized access to this branch.');
            }
        }

        if ($branch->inventoryItems()->exists() || $branch->sales()->exists()) {
            $branch->update(['status' => 'inactive']);
            return back()->with('success', 'Branch marked as inactive because it contains existing sales or inventory records.');
        }

        $branch->delete();

        return back()->with('success', 'Branch removed successfully.');
    }
}
