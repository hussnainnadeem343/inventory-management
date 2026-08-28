<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = in_array((int) $request->query('per_page', 10), [10, 20, 30, 50, 100], true) ? (int) $request->query('per_page', 10) : 10;

        return view('users.index', ['users' => User::latest()->paginate($perPage)->withQueryString(), 'perPage' => $perPage]);
    }

    public function create(): View
    {
        return view('users.form', ['user' => new User]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        User::create($request->validated());

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        return view('users.form', compact('user'));
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        if (empty($data['password'])) {
            unset($data['password']);
        }if ($request->user()->is($user) && ($data['status'] === 'inactive' || $data['role'] !== 'super_admin')) {
            return back()->with('error', 'You cannot deactivate or remove the Super Admin role from your own account.');
        }$user->update($data);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if (request()->user()->is($user)) {
            return back()->with('error', 'You cannot delete your own account.');
        }if ($user->isSuperAdmin()) {
            return back()->with('error', 'Super Admin accounts cannot be deleted; set them inactive instead.');
        }if ($user->inventoryItems()->exists() || $user->brands()->exists() || $user->categories()->exists()) {
            return back()->with('error', 'This user created business records and cannot be deleted; set the user inactive instead.');
        }$user->delete();

        return back()->with('success', 'User deleted successfully.');
    }
}
