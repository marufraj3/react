<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::guard('admin')->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('The provided credentials do not match our records.'),
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::guard('admin')->user();

        // Historical reseller accounts are preserved in the database but the module is retired.
        $isRetiredReseller = $user->hasRole('reseller')
            || (isset($user->role) && strtolower($user->role) === 'reseller')
            || $user->getRoleNames()->contains(fn ($role) => strtolower($role) === 'reseller');

        if ($isRetiredReseller) {
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'The reseller portal is no longer available.',
            ]);
        }

        // Otherwise, redirect to default dashboard
        return redirect()->intended('/admin/dashboard');
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
