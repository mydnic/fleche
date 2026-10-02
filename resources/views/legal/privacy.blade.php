@extends('legal.layout', ['updated' => '2 October 2026'])

@section('title', 'Privacy policy')

@section('summary')
    <ul>
        <li>We collect what Fleche needs to work: your account, your rules and todos, and the few settings you choose. Nothing else.</li>
        <li>No ads, no third-party trackers, no selling your data. Ever. We count visits with privacy-friendly analytics that run on our own server and use no cookies.</li>
        <li>Your data lives on servers in the European Union (Hetzner, Germany).</li>
        <li>You can delete your account and everything in it yourself, in one click, from Settings.</li>
        <li>Questions or requests: <a href="mailto:rigoclement@mydnic.be">rigoclement@mydnic.be</a>.</li>
    </ul>
@endsection

@section('content')
    <p>This policy explains how fleche.io (the hosted version of Fleche, "the service") handles your personal data, in line with the EU General Data Protection Regulation (GDPR). It does not cover self-hosted copies of Fleche: whoever runs those is responsible for them.</p>

    <h2>Who we are</h2>
    <p>The data controller is <strong>My Dynamic Production SRL</strong>, Rue du Curé 18a, 4280 Moxhe (Hannut), Belgium, company number (BCE/KBO) 0676680512, VAT BE0676680512.</p>
    <p>For anything about your data, write to <a href="mailto:rigoclement@mydnic.be">rigoclement@mydnic.be</a>. A real person (the developer) reads it.</p>

    <h2>What we collect, and why</h2>
    <table>
        <thead>
            <tr><th>Data</th><th>Why</th><th>Legal basis</th></tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Account</strong>: name, email address, password (stored hashed, we can never read it), timezone</td>
                <td>Create your account, log you in, reset your password, start your day at the right local time</td>
                <td>Contract (Art. 6.1.b)</td>
            </tr>
            <tr>
                <td><strong>Your content</strong>: rules, todos, descriptions, pictures you upload, points</td>
                <td>That's the product: generating and showing your todos</td>
                <td>Contract</td>
            </tr>
            <tr>
                <td><strong>Notification settings</strong>: the hour your day starts, email and/or Telegram choice, your Telegram chat ID if you connect Telegram</td>
                <td>Send you your daily list where you asked for it</td>
                <td>Contract</td>
            </tr>
            <tr>
                <td><strong>API keys</strong> you create (stored hashed) and when they were last used</td>
                <td>Let your own tools access your account</td>
                <td>Contract</td>
            </tr>
            <tr>
                <td><strong>Payment</strong>: the date you bought the lifetime plan. Card details are handled by Stripe and never reach us.</td>
                <td>Unlock unlimited history, refunds, accounting</td>
                <td>Contract, and legal obligation for accounting records (Art. 6.1.c)</td>
            </tr>
            <tr>
                <td><strong>Usage statistics</strong>: pages visited, referrer, browser, device type and approximate country</td>
                <td>Understand which features are used and improve Fleche</td>
                <td>Legitimate interest</td>
            </tr>
            <tr>
                <td><strong>Technical data</strong>: IP address, browser, pages requested, error reports</td>
                <td>Keep the service secure and working, investigate bugs and abuse</td>
                <td>Legitimate interest (Art. 6.1.f)</td>
            </tr>
        </tbody>
    </table>
    <p>We don't use your data for advertising, we don't build profiles, and no decision about you is made automatically. The "chance" in your rules is a dice roll about your todos, not about you.</p>

    <h2>The community hub</h2>
    <p>If you publish a rule pack on the hub, its name, description, rules and your display name become public, after a manual review. If you delete your account, your packs stay available but your name is removed from them.</p>

    <h2>Who else handles your data</h2>
    <p>We use a few providers ("processors") that only act on our instructions:</p>
    <table>
        <thead>
            <tr><th>Provider</th><th>What for</th><th>Where</th></tr>
        </thead>
        <tbody>
            <tr><td>Hetzner Online GmbH</td><td>Servers and database</td><td>Germany (EU)</td></tr>
            <tr><td>Cloudflare, Inc.</td><td>Sending emails, storing the pictures you upload</td><td>Global network; transfers outside the EU covered by the EU-US Data Privacy Framework and Standard Contractual Clauses</td></tr>
            <tr><td>Stripe Payments Europe, Ltd.</td><td>The lifetime payment (only if you buy it)</td><td>Ireland (EU); Stripe is a controller for its own fraud and legal obligations, see stripe.com/privacy</td></tr>
            <tr><td>Telegram</td><td>Delivering your daily list, only if you connect Telegram yourself</td><td>Outside the EU; Telegram's own privacy policy applies to your use of Telegram</td></tr>
        </tbody>
    </table>
    <p>Usage statistics are collected with <strong>Rybbit</strong>, an open-source analytics tool we run on our own server: the data never goes to an analytics company, no cookies are set, and visitors are counted without building a profile of you.</p>
    <p>We also receive technical error reports in a private Telegram chat so we can fix bugs fast. These may contain the page you visited and your user ID, never your password or your todos' content on purpose.</p>
    <p>We never sell or rent your data, and only hand it to authorities when the law requires it.</p>

    <h2>How long we keep it</h2>
    <ul>
        <li><strong>Your account and content</strong>: as long as you have an account.</li>
        <li><strong>Free plan</strong>: todos older than 7 days are deleted automatically. The lifetime plan keeps your history.</li>
        <li><strong>When you delete your account</strong>: your account, rules, todos, pictures and API keys are deleted immediately. Copies in backups disappear as backups rotate, within 30 days.</li>
        <li><strong>Server logs and error reports</strong>: up to 30 days.</li>
        <li><strong>Usage statistics</strong>: up to 2 years.</li>
        <li><strong>Payment and invoicing records</strong>: as long as Belgian accounting law requires (currently 10 years).</li>
    </ul>

    <h2>Cookies</h2>
    <p>Fleche only uses cookies that are strictly necessary: one to keep you logged in (session) and one to protect forms against forgery (XSRF-TOKEN). Our analytics don't use cookies, and there are no advertising or third-party cookies, so there's no cookie banner to click through. Fonts and icons are served from our own server.</p>

    <h2>Your rights</h2>
    <p>Under the GDPR you can at any time:</p>
    <ul>
        <li><strong>Access</strong> your data and get a copy of it,</li>
        <li><strong>Correct</strong> it (your name and email are editable in Settings),</li>
        <li><strong>Delete</strong> it: Settings → Delete my account, or ask us,</li>
        <li><strong>Take it with you</strong> (portability): the API exports your todos and rules, or ask us for a file,</li>
        <li><strong>Object</strong> to processing based on legitimate interest, or ask us to <strong>restrict</strong> it.</li>
    </ul>
    <p>Write to <a href="mailto:rigoclement@mydnic.be">rigoclement@mydnic.be</a>. We answer within one month. If you think we got something wrong, you can complain to the Belgian Data Protection Authority (Autorité de protection des données / Gegevensbeschermingsautoriteit), Rue de la Presse 35, 1000 Brussels, <a href="https://www.dataprotectionauthority.be">dataprotectionauthority.be</a>, or to the authority of your own EU country.</p>

    <h2>Security</h2>
    <p>Connections are encrypted (HTTPS), passwords and API keys are stored hashed, and access to the servers is limited to the developer. No system is perfect: if a breach ever affects your data, we'll tell you and the authority as the GDPR requires.</p>

    <h2>Children</h2>
    <p>Fleche is not meant for children under 16. If you're younger, please ask a parent before signing up.</p>

    <h2>Changes</h2>
    <p>If we change this policy in a way that matters, we'll email you before it applies. The date at the top always shows the latest version.</p>
@endsection
