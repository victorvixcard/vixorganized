<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('projects.index');
        }

        // Acesso rápido só em ambiente local: chips que preenchem o formulário.
        $demo = app()->environment('local') ? [
            ['name' => env('SEED_ADMIN_NAME', 'Vitão'), 'role' => 'Admin', 'email' => env('SEED_ADMIN_EMAIL', 'vitor@vixorganize.local')],
            ['name' => env('SEED_MANAGER_NAME', 'Felipe'), 'role' => 'Gerente', 'email' => env('SEED_MANAGER_EMAIL', 'felipe@vixorganize.local')],
        ] : [];

        return view('auth.login', [
            'demo' => $demo,
            'demoPassword' => app()->environment('local') ? env('SEED_PASSWORD', 'vixorganize') : null,
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'E-mail ou senha incorretos.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('projects.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
