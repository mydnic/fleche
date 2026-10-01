<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * First run of an instance with no ADMIN_EMAIL/ADMIN_PASSWORD: whoever gets
 * here first creates the admin. Closed for good once any user exists.
 */
class SetupController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        return User::query()->exists() ? to_route('login') : Inertia::render('auth/Setup');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_if(User::query()->exists(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'timezone' => ['nullable', 'timezone:all_with_bc'],
        ]);

        $user = User::create([...$data, 'timezone' => $data['timezone'] ?? config('app.timezone')]);
        $user->forceFill(['is_admin' => true, 'email_verified_at' => now()])->save();

        Auth::login($user);
        $request->session()->regenerate();

        return to_route('today');
    }
}
