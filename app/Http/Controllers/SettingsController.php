<?php

namespace App\Http\Controllers;

use App\Actions\DeleteAccount;
use App\Actions\LinkTelegramChats;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Cashier\Checkout;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SettingsController extends Controller
{
    public function show(Request $request, LinkTelegramChats $linkTelegramChats): Response
    {
        $user = $request->user();
        $bot = config('services.telegram.username');

        // Polled by the page while the user is off pressing Start in Telegram.
        if ($request->boolean('telegram') && $user->telegram_chat_id === null) {
            $linkTelegramChats->handle();
            $user->refresh();
        }

        // A standing one-time token, so "Connect Telegram" is a plain link.
        if ($bot && $user->telegram_chat_id === null && $user->telegram_link_token === null) {
            $user->forceFill(['telegram_link_token' => Str::random(32)])->save();
        }

        return Inertia::render('Settings', [
            'telegramLink' => $bot && $user->telegram_chat_id === null ? "https://t.me/{$bot}?start={$user->telegram_link_token}" : null,
            'tokens' => $user->tokens()->latest()->get(['id', 'name', 'last_used_at', 'created_at']),
            'newToken' => session('newToken'),
            'apiBase' => url('/api/v1'),
        ]);
    }

    public function notifications(Request $request): RedirectResponse
    {
        $request->user()->update($request->validate([
            'notify_mail' => ['required', 'boolean'],
            'notify_telegram' => ['required', 'boolean'],
            'day_start_hour' => ['required', 'integer', 'between:0,23'],
            'timezone' => ['required', 'timezone:all_with_bc'],
        ]));

        return back()->with('status', 'Saved.');
    }

    public function disconnectTelegram(Request $request): RedirectResponse
    {
        $request->user()->forceFill(['telegram_chat_id' => null, 'telegram_link_token' => null, 'notify_telegram' => false])->save();

        return back();
    }

    public function createToken(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);

        return back()->with('newToken', $request->user()->createToken($data['name'])->plainTextToken);
    }

    public function deleteToken(Request $request, int $token): RedirectResponse
    {
        $request->user()->tokens()->whereKey($token)->delete();

        return back();
    }

    public function destroyAccount(Request $request, DeleteAccount $deleteAccount): SymfonyResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $user = $request->user();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $deleteAccount->handle($user);

        return Inertia::location('/');
    }

    public function checkout(Request $request): SymfonyResponse
    {
        abort_unless(config('fleche.edition') === 'cloud' && $request->user()->paid_at === null, 404);

        $checkout = Checkout::guest()->create([[
            'price_data' => [
                'currency' => 'usd',
                'unit_amount' => config('fleche.cloud.lifetime_amount'),
                'product_data' => ['name' => 'Fleche Lifetime: unlimited history'],
            ],
        ]], [
            'client_reference_id' => (string) $request->user()->id,
            'customer_email' => $request->user()->email,
            'success_url' => route('settings').'?paid=1',
            'cancel_url' => route('settings'),
        ]);

        return Inertia::location($checkout->url);
    }
}
