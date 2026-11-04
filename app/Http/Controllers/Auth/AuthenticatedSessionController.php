<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'status' => session('status'),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string']
        ]);

        $throtlleKey = Str::transliterate(Str::lower($request->input('username'))) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throtlleKey, 5)) {
            $seconds = RateLimiter::availableIn($throtlleKey);
            throw ValidationException::withMessages([
                'username' => "Terlalu banyak percobaan login, Silakan coba lagi dalam {$seconds} detik.",
            ]);
        }

        $remember = $request->boolean('remember');

        if (!Auth::attempt($request->only('username', 'password'), $remember)) {
            RateLimiter::hit($throtlleKey, 60);

            throw ValidationException::withMessages([
                'username' => 'Username atau password yang Anda masukkan tidak sesuai.'
            ]);
        }

        RateLimiter::clear($throtlleKey);

        $request->session()->regenerate();

        $user = Auth::user();
        if ($user->role_user === 'cashier') {
            return redirect()->intended('/pos');
        }

        return redirect()->intended('/');
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
