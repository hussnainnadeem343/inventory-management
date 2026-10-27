<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['username' => ['required', 'string'], 'password' => ['required']]);
        if (Auth::attempt([...$credentials, 'status' => 'active'], $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();
            if (! $user->isSuperAdmin() && ! $user->isShopAdmin() && ! $user->isBusinessOwner()) {
                if ($user->hasPermission('pos.access') && ! $user->hasPermission('purchases.view') && ! $user->hasPermission('stock.view') && ! $user->hasPermission('reports.sales')) {
                    return redirect()->intended(route('pos'));
                }
            }

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors(['username' => 'The credentials are invalid or this account is inactive.'])->onlyInput('username');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
