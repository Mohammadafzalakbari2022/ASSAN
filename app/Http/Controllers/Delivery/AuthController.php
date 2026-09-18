<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(Request $request): View
    {
        $user = $request->user();

        if ($user !== null && $user->isDelivery()) {
            return redirect()->route('delivery.orders.index');
        }

        return view('delivery.auth.login', [
            'signedInAs' => $user !== null ? $user->name : null,
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        if ($request->user() !== null && $request->user()->isDelivery()) {
            return redirect()->route('delivery.orders.index');
        }
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Enter your email address.',
            'email.email' => 'Enter a valid email address.',
            'password.required' => 'Enter your password.',
        ]);

        if (!Auth::attempt($data, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'The email or password is not correct.',
            ]);
        }

        $user = Auth::user();

        if (!$user->isDelivery() || $user->active === false) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => !$user->isDelivery()
                    ? 'This sign-in page is only for delivery staff.'
                    : 'This delivery account has been disabled.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('delivery.orders.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('delivery.login');
    }
}
