<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · Fleche</title>
    <meta name="theme-color" content="#f97316">
    <link rel="icon" href="/icon.svg" type="image/svg+xml">
    @fonts
    @include('partials.analytics')
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased text-stone-800">
    <header class="mx-auto flex max-w-3xl items-center justify-between px-4 py-5">
        <a href="/" class="inline-flex items-center gap-2 font-display text-2xl font-bold">
            <span class="grid size-9 rotate-[-8deg] place-items-center rounded-xl bg-orange-500 text-white shadow-[0_3px_0_0_#c2410c]">➚</span>
            Fleche
        </a>
        <a href="{{ auth()->check() ? '/app' : '/app/register' }}" class="rounded-xl bg-orange-500 px-4 py-2 text-sm font-bold text-white shadow-[0_3px_0_0_#c2410c] hover:bg-orange-600">{{ auth()->check() ? 'Open app' : 'Start free' }}</a>
    </header>

    <main class="mx-auto max-w-3xl px-4 pb-16">
        <article class="rounded-3xl border-2 border-orange-100 bg-white p-6 shadow-[0_4px_0_0_rgb(0_0_0/0.06)] sm:p-10 [&_a]:font-semibold [&_a]:text-orange-600 [&_a]:underline [&_h2]:mt-10 [&_h2]:text-2xl [&_h2]:font-bold [&_h3]:mt-6 [&_h3]:text-lg [&_h3]:font-bold [&_li]:mt-1 [&_p]:mt-3 [&_p]:leading-relaxed [&_table]:mt-4 [&_table]:w-full [&_table]:text-sm [&_td]:border-t [&_td]:border-orange-100 [&_td]:py-2 [&_td]:pr-3 [&_td]:align-top [&_th]:pb-2 [&_th]:pr-3 [&_th]:text-left [&_ul]:mt-3 [&_ul]:list-disc [&_ul]:pl-6">
            <h1 class="text-4xl font-bold">@yield('title')</h1>
            <p class="text-sm text-stone-400">Last updated: {{ $updated }}</p>

            <div class="mt-6 rounded-2xl bg-orange-50 p-5 text-stone-700">
                <p class="!mt-0 font-bold">🏹 The short version</p>
                @yield('summary')
            </div>

            @yield('content')
        </article>
    </main>

    <footer class="pb-10 text-center text-sm text-stone-400">
        <a href="/privacy" class="underline">Privacy</a> · <a href="/terms" class="underline">Terms</a> · Fleche is made by My Dynamic Production SRL, Belgium
    </footer>
</body>
</html>
