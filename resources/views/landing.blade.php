<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fleche · Todos that show up on their own</title>
    <meta name="description" content="Recurring todos with a twist: rules, chance, points and rewards. A community hub of rule packs. Self-host it or use the cloud.">
    <meta name="theme-color" content="#f97316">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/icon.svg" type="image/svg+xml">
    @fonts
    @include('partials.analytics')
    @vite(['resources/css/app.css', 'resources/js/landing.ts'])
</head>
<body class="cursor-crosshair font-sans antialiased text-stone-800">
    <header class="mx-auto flex max-w-5xl items-center justify-between px-4 py-5">
        <a href="/" class="inline-flex items-center gap-2 font-display text-2xl font-bold">
            <span class="grid size-9 rotate-[-8deg] place-items-center rounded-xl bg-orange-500 text-white shadow-[0_3px_0_0_#c2410c]">➚</span>
            Fleche
        </a>
        <nav class="flex items-center gap-2 text-sm font-bold">
            @auth
                <a href="/app" class="rounded-xl bg-orange-500 px-4 py-2 text-white shadow-[0_3px_0_0_#c2410c] hover:bg-orange-600">Open app 🏹</a>
            @else
                <a href="/app/login" class="rounded-xl px-3 py-2 hover:bg-orange-50">Log in</a>
                <a href="/app/register" class="rounded-xl bg-orange-500 px-4 py-2 text-white shadow-[0_3px_0_0_#c2410c] hover:bg-orange-600">Start free</a>
            @endauth
        </nav>
    </header>

    <main>
        <section class="mx-auto grid max-w-5xl items-center gap-10 px-4 py-14 md:grid-cols-2">
            <div>
                <p class="mb-3 inline-block rounded-full bg-amber-200 px-3 py-1 text-xs font-extrabold tracking-wide text-amber-900 uppercase">Your todo list, with dice</p>
                <h1 class="text-5xl leading-[1.05] font-bold sm:text-6xl">Todos that <span class="text-orange-500">show up</span> on their own.</h1>
                <p class="mt-5 text-lg text-stone-600">"Bins out every Tuesday." "Plank for a minute, 1 in 3 chance." "Pizza night on Friday, if you've earned 60 points." Write the rules once. Fleche fills your list every morning.</p>
                <div class="mt-8 grid max-w-md grid-cols-2 gap-3">
                    <a href="{{ auth()->check() ? '/app' : '/app/register' }}" class="rounded-2xl border-2 border-orange-500 bg-orange-500 px-4 py-3 text-center text-lg font-bold text-white shadow-[0_4px_0_0_#c2410c] hover:bg-orange-600">{{ auth()->check() ? 'Open my todos' : 'Start free' }} 🏹</a>
                    <a href="https://github.com/mydnic/fleche" class="rounded-2xl border-2 border-stone-200 bg-white px-4 py-3 text-center text-lg font-bold shadow-[0_4px_0_0_rgb(0_0_0/0.06)] hover:border-orange-300">Self-host it</a>
                </div>
            </div>

            {{-- Playable sample: resources/js/components/LandingDemo.vue. --}}
            <div id="demo" class="min-h-[22rem] cursor-crosshair pt-14 md:pt-0"></div>
        </section>

        <section class="mx-auto grid max-w-5xl gap-4 px-4 py-10 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['📅', 'Rules, not reminders', 'Specific weekdays, every N weeks, the last day of the month, once a quarter, not before a date…'],
                ['🎲', 'A pinch of chance', '"1 in 7" means about once a week, never on the same day. Habits that stay fresh.'],
                ['⭐', 'Points & rewards', 'Earn points for every hit. Spend them on reward rules that only appear when you can afford them. Optional.'],
                ['🏪', 'Community packs', 'Import rule packs from other people in one click. Share yours, earn points when others import.'],
            ] as [$emoji, $title, $body])
                <div class="rounded-3xl border-2 border-orange-100 bg-white p-5 shadow-[0_4px_0_0_rgb(0_0_0/0.06)]">
                    <div class="text-3xl">{{ $emoji }}</div>
                    <h3 class="mt-2 text-lg font-bold">{{ $title }}</h3>
                    <p class="mt-1 text-sm text-stone-600">{{ $body }}</p>
                </div>
            @endforeach
        </section>

        @if ($packs->isNotEmpty())
            <section class="mx-auto max-w-5xl px-4 py-10">
                <h2 class="text-center text-4xl font-bold">Start with a pack</h2>
                <p class="mt-2 text-center text-stone-600">Ready-made rules, one click to import, yours to tweak. 🎲 = the dice decide.</p>
                <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($packs as $pack)
                        <div class="rounded-3xl border-2 border-sky-100 bg-white p-5 shadow-[0_4px_0_0_rgb(0_0_0/0.06)]">
                            <h3 class="text-lg leading-tight font-bold">{{ $pack->name }}</h3>
                            <p class="mt-1 text-sm text-stone-600">{{ $pack->description }}</p>
                            <ul class="mt-3 space-y-1 text-sm">
                                @foreach ($pack->rules as $rule)
                                    <li class="flex items-center justify-between gap-2 rounded-lg bg-sky-50 px-2 py-1">
                                        <span>
                                            <span class="block leading-tight font-semibold">{{ $rule['name'] }}</span>
                                            <span class="block text-xs text-stone-500">{{ \App\Models\TodoSetting::describe($rule, withChance: false) }}</span>
                                        </span>
                                        @if (isset($rule['reward_cost']))
                                            <span class="shrink-0 text-xs font-bold text-sky-700">🎁 {{ $rule['reward_cost'] }} ★</span>
                                        @elseif (($rule['chance'] ?? 1) < 1)
                                            <span class="shrink-0 text-xs font-bold text-orange-600">🎲 {{ \App\Models\TodoSetting::describeChance($rule['chance']) }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
                <p class="mt-6 text-center"><a href="{{ auth()->check() ? '/app/hub' : '/app/register' }}" class="font-bold text-orange-600 underline">Browse them all in the hub →</a></p>
            </section>
        @endif

        <section class="mx-auto max-w-5xl px-4 py-10">
            <div class="grid gap-4 rounded-3xl bg-sky-100 p-8 sm:grid-cols-3">
                <div><h3 class="text-xl font-bold">☀️ Morning list</h3><p class="mt-1 text-sm text-stone-700">By email or Telegram, at the hour you pick.</p></div>
                <div><h3 class="text-xl font-bold">📱 Installable</h3><p class="mt-1 text-sm text-stone-700">Add it to your home screen. No app store needed.</p></div>
                <div><h3 class="text-xl font-bold">🔌 API</h3><p class="mt-1 text-sm text-stone-700">Your own key, documented endpoints, plug it into anything.</p></div>
            </div>
        </section>

        <section id="pricing" class="mx-auto max-w-5xl px-4 py-14">
            <h2 class="text-center text-4xl font-bold">Simple pricing</h2>
            <p class="mt-2 text-center text-stone-600">No subscription. Ever.</p>
            <div class="mt-8 grid gap-4 md:grid-cols-3">
                <div class="rounded-3xl border-2 border-orange-100 bg-white p-6">
                    <h3 class="text-xl font-bold">Free</h3>
                    <p class="mt-1 text-3xl font-bold">$0</p>
                    <ul class="mt-4 space-y-2 text-sm text-stone-600">
                        <li>✓ Unlimited rules & todos</li><li>✓ Points, rewards, hub</li><li>✓ Email & Telegram</li><li>⏳ History kept 7 days</li>
                    </ul>
                    <a href="{{ auth()->check() ? '/app' : '/app/register' }}" class="mt-6 block rounded-2xl border-2 border-orange-200 py-2.5 text-center font-bold hover:bg-orange-50">{{ auth()->check() ? 'Open app' : 'Start free' }}</a>
                </div>
                <div class="relative rounded-3xl border-[3px] border-orange-500 bg-white p-6 shadow-[0_6px_0_0_#fed7aa]">
                    <span class="absolute -top-3 right-5 rounded-full bg-orange-500 px-3 py-0.5 text-xs font-extrabold text-white uppercase">Pay once</span>
                    <h3 class="text-xl font-bold">Lifetime</h3>
                    <p class="mt-1 text-3xl font-bold">$35 <span class="text-base font-semibold text-stone-500">once, forever</span></p>
                    <ul class="mt-4 space-y-2 text-sm text-stone-600">
                        <li>✓ Everything in Free</li><li>✓ Your whole history, forever</li><li>✓ Supports the project 💛</li>
                    </ul>
                    @if (auth()->user()?->paid_at)
                        <p class="mt-6 rounded-2xl bg-green-50 py-2.5 text-center font-bold text-green-700">🏆 You're a lifetime member</p>
                    @else
                        {{-- Signed in: straight to the upgrade in Settings. --}}
                        <a href="{{ auth()->check() ? '/app/settings' : '/app/register' }}" class="mt-6 block rounded-2xl bg-orange-500 py-2.5 text-center font-bold text-white shadow-[0_3px_0_0_#c2410c] hover:bg-orange-600">{{ auth()->check() ? 'Upgrade for $35' : 'Get started' }}</a>
                    @endif
                </div>
                <div class="rounded-3xl border-2 border-sky-200 bg-white p-6">
                    <h3 class="text-xl font-bold">Self-hosted</h3>
                    <p class="mt-1 text-3xl font-bold">Free</p>
                    <ul class="mt-4 space-y-2 text-sm text-stone-600">
                        <li>✓ Everything, no limits</li><li>✓ One <code>docker compose up</code></li><li>✓ Still browse the community hub</li>
                    </ul>
                    <a href="https://github.com/mydnic/fleche" class="mt-6 block rounded-2xl border-2 border-sky-200 py-2.5 text-center font-bold hover:bg-sky-50">Read the docs</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="py-10 text-center text-sm text-stone-400">Fleche · made with 🏹 by <a href="https://mydnic.be" class="underline">mydnic</a> · <a href="/privacy" class="underline">Privacy</a> · <a href="/terms" class="underline">Terms</a></footer>
</body>
</html>
