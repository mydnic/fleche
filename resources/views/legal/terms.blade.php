@extends('legal.layout', ['updated' => '2 October 2026'])

@section('title', 'Terms of service')

@section('summary')
    <ul>
        <li>Fleche is free. The lifetime plan is one payment of $35, no subscription, and you can get a full refund within 14 days, no questions asked.</li>
        <li>Your todos are yours. Packs you publish on the hub can be imported by anyone.</li>
        <li>Be nice: no illegal content, no abusing the service or other people.</li>
        <li>We do our best to keep Fleche running, but it's a todo app made by one person: keep your own copy of anything critical.</li>
        <li>Belgian law applies, and your rights as a consumer stay intact.</li>
    </ul>
@endsection

@section('content')
    <h2>1. Who we are</h2>
    <p>fleche.io ("the service") is operated by <strong>My Dynamic Production SRL</strong>, Rue du Curé 18a, 4280 Moxhe (Hannut), Belgium, company number (BCE/KBO) 0676680512, VAT BE0676680512. Contact: <a href="mailto:rigoclement@mydnic.be">rigoclement@mydnic.be</a>.</p>
    <p>By creating an account you accept these terms and our <a href="/privacy">privacy policy</a>. Fleche is also available as open-source software you can host yourself; these terms only cover fleche.io.</p>

    <h2>2. Your account</h2>
    <ul>
        <li>You need to be at least 16 years old, or have a parent's permission.</li>
        <li>Give a real email address: it's how you log in, reset your password and receive your daily list.</li>
        <li>Keep your password and API keys to yourself. You're responsible for what happens with them.</li>
        <li>You can delete your account at any time from Settings. It's immediate and permanent.</li>
    </ul>

    <h2>3. Plans and payment</h2>
    <h3>Free plan</h3>
    <p>Everything Fleche does, with one limit: todos older than 7 days are deleted automatically.</p>
    <h3>Lifetime plan</h3>
    <ul>
        <li>A one-time payment of <strong>$35</strong>, processed by Stripe. The amount shown at checkout is the amount you pay. No subscription, no renewal.</li>
        <li>It removes the 7-day limit: your history is kept for as long as your account and the service exist.</li>
        <li>"Lifetime" means the lifetime of the service. If we ever shut fleche.io down, we'll warn you at least 90 days in advance, and you'll be able to export your data or move to a self-hosted copy of Fleche.</li>
    </ul>
    <h3>Refunds and withdrawal</h3>
    <p>Changed your mind? Email us within <strong>14 days</strong> of your purchase and we'll refund you in full, no questions asked. This also covers your legal right of withdrawal as an EU consumer. After a refund, your account goes back to the free plan.</p>

    <h2>4. Your content</h2>
    <ul>
        <li>Your rules, todos, descriptions and pictures belong to you. You give us only the permission we need to store, process and show them to you so the service works.</li>
        <li>Done is done: checking a todo is final and can't be undone, in the app or the API. That's a feature.</li>
        <li>Don't upload anything illegal, or that you don't have the right to share.</li>
    </ul>

    <h2>5. The community hub</h2>
    <ul>
        <li>When you publish a pack, you let every Fleche user (on fleche.io and on self-hosted instances) import, use and modify its rules, for free.</li>
        <li>Packs are reviewed before going public. We can refuse or remove any pack, for example if it's spam, offensive or misleading.</li>
        <li>You earn points when someone imports your pack. Points, from the hub or from your todos, are just for fun: they have no monetary value, can't be bought, sold or exchanged, and only unlock reward rules in your own account.</li>
    </ul>

    <h2>6. Fair use</h2>
    <p>Please don't:</p>
    <ul>
        <li>break the law, or use Fleche to harass anyone,</li>
        <li>try to access other people's accounts or data, or test the service's security without our written permission,</li>
        <li>overload the service or its API (scripts hammering it, mass account creation),</li>
        <li>resell access to fleche.io.</li>
    </ul>
    <p>If you do, we may suspend or close your account. When reasonable, we'll warn you first and explain why.</p>

    <h2>7. Availability</h2>
    <p>We work hard to keep Fleche up and your data safe, but we can't promise the service will be available every minute or free of bugs. We may change or improve features over time. For anything important, keep your own copy (the API makes that easy).</p>

    <h2>8. Liability</h2>
    <p>Fleche reminds you of things; it doesn't do them for you. We're not responsible for the consequences of a todo that didn't show up, showed up late, or was skipped by the dice (yes, even the bins).</p>
    <p>To the extent the law allows, our total liability towards you is limited to the amount you paid us in the 12 months before the problem. Nothing in these terms limits liability for fraud, intentional misconduct or gross negligence, or the rights you have as a consumer under mandatory law.</p>

    <h2>9. Changes to these terms</h2>
    <p>If we change these terms in a way that matters, we'll email you at least 30 days before. If you don't agree, you can delete your account before the change applies (and, within the refund window, get your money back).</p>

    <h2>10. Law and disputes</h2>
    <p>These terms are governed by Belgian law. If something goes wrong, write to us first: most things are solved in a friendly email. Disputes go to the courts of Liège (Belgium). If you're a consumer, you keep the protection of the mandatory rules of your country of residence, and you can also bring a case before the courts there.</p>
@endsection
