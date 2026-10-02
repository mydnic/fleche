<?php

use App\Models\HubPack;
use App\Models\Todo;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Database\Seeders\HubPackSeeder;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Laravel\Cashier\Events\WebhookReceived;
use NotificationChannels\Telegram\Telegram;

it('redirects / to the app when self-hosted', function () {
    $this->get('/')->assertRedirect('/app');
    $this->get('/api/hub/packs')->assertNotFound();
});

it('serves the landing page on cloud', function () {
    bootWithEnv(['APP_EDITION' => 'cloud']);

    $this->get('/')->assertOk()->assertSee('show up')->assertSee('Log in')->assertDontSee('Open app');

    $this->actingAs(User::factory()->create())->get('/')->assertSee('Open app')->assertSee('Upgrade for $35')->assertDontSee('Log in');
});

it('has no register route when registration is disabled', function () {
    bootWithEnv(['REGISTRATION_ENABLED' => 'false']);

    $this->get('/app/register')->assertNotFound();
    $this->post('/app/register')->assertNotFound();
});

it('lets the first visitor set up the instance, once', function () {
    $this->get('/app/login')->assertRedirect(route('setup'));

    $this->post(route('setup.store'), [
        'name' => 'Admin', 'email' => 'a@b.c', 'password' => 'password', 'password_confirmation' => 'password',
    ])->assertRedirect(route('today'));

    expect(User::sole()->is_admin)->toBeTrue();

    auth()->logout();
    $this->post(route('setup.store'), ['name' => 'X', 'email' => 'x@b.c', 'password' => 'password', 'password_confirmation' => 'password'])->assertForbidden();
});

it('prunes old todos of free cloud users only', function () {
    bootWithEnv(['APP_EDITION' => 'cloud']);

    $free = User::factory()->create();
    $paid = User::factory()->create(['paid_at' => now()]);
    Todo::factory()->for($free)->create(['date' => today()->subDays(8)]);
    Todo::factory()->for($free)->create(['date' => today()->subDays(3)]);
    Todo::factory()->for($paid)->create(['date' => today()->subDays(30)]);

    $this->artisan('fleche:prune')->assertSuccessful();

    expect($free->todos()->count())->toBe(1)->and($paid->todos()->count())->toBe(1);
});

it('keeps everything when self-hosted', function () {
    Todo::factory()->create(['date' => today()->subDays(30)]);

    $this->artisan('fleche:prune');

    expect(Todo::count())->toBe(1);
});

it('unlocks history when the Stripe checkout completes', function () {
    bootWithEnv(['APP_EDITION' => 'cloud']);
    $user = User::factory()->create();

    event(new WebhookReceived(['type' => 'checkout.session.completed', 'data' => ['object' => [
        'payment_status' => 'paid', 'client_reference_id' => (string) $user->id,
    ]]]));

    expect($user->refresh()->paid_at)->not->toBeNull();
});

it('imports a hub pack and credits its author', function () {
    bootWithEnv(['APP_EDITION' => 'cloud']);

    $author = User::factory()->create();
    $pack = HubPack::create(['user_id' => $author->id, 'name' => 'Stretching', 'rules' => [
        ['name' => 'Touch your toes', 'chance' => 0.33],
        ['name' => 'Neck rolls', 'chance' => 0.33, 'user_id' => 999],
    ], 'status' => 'approved']);
    $importer = User::factory()->create();

    $this->actingAs($importer)->post(route('hub.import', $pack->id))->assertRedirect(route('rules.index'));

    expect($importer->todoSettings()->count())->toBe(2)
        ->and($author->refresh()->points)->toBe(10)
        ->and($pack->refresh()->imports_count)->toBe(1);
});

it('hides unmoderated packs', function () {
    bootWithEnv(['APP_EDITION' => 'cloud']);

    HubPack::create(['user_id' => User::factory()->create()->id, 'name' => 'Spam', 'rules' => [['name' => 'x']]]);

    $this->getJson('/api/hub/packs')->assertOk()->assertJsonCount(0);
});

it('reads the hub from the cloud when self-hosted', function () {
    Http::fake(['*/api/hub/packs/7/imports' => Http::response(['id' => 7, 'name' => 'P', 'rules' => [['name' => 'Water plants']]])]);
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('hub.import', 7))->assertRedirect();

    expect($user->todoSettings()->sole()->name)->toBe('Water plants');
});

it('gives the user a bot link with a one-time token', function () {
    config(['services.telegram.token' => 'T', 'services.telegram.username' => 'FlecheBot']);
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('settings'))->assertInertia(fn ($page) => $page
        ->where('telegramLink', 'https://t.me/FlecheBot?start='.$user->refresh()->telegram_link_token));

    expect($user->telegram_link_token)->toHaveLength(32);
});

it('links the chat when the waiting Settings page polls', function () {
    config(['services.telegram.token' => 'T', 'services.telegram.username' => 'FlecheBot']);
    $user = User::factory()->create();
    $user->forceFill(['telegram_link_token' => 'abc'])->save();

    // The package talks to Telegram through its own Guzzle client.
    $sent = [];
    $stack = HandlerStack::create(new MockHandler([
        new GuzzleResponse(200, [], json_encode(['ok' => true, 'result' => [['update_id' => 5, 'message' => ['text' => '/start abc', 'chat' => ['id' => 42]]]]])),
        new GuzzleResponse(200, [], json_encode(['ok' => true, 'result' => []])),
    ]));
    $stack->push(Middleware::history($sent));
    app()->instance(Telegram::class, new Telegram('T', new GuzzleClient(['handler' => $stack])));

    // A plain visit never calls Telegram.
    $this->actingAs($user)->get(route('settings'));
    expect($sent)->toBeEmpty();

    $this->actingAs($user)->get(route('settings', ['telegram' => 1]))
        ->assertInertia(fn ($page) => $page->where('auth.user.telegram_chat_id', '42')->where('telegramLink', null));

    expect($user->refresh()->notify_telegram)->toBeTrue()
        ->and($user->telegram_link_token)->toBeNull()
        ->and(Cache::get('telegram-offset'))->toBe(6)
        ->and(collect($sent)->map(fn ($call) => basename($call['request']->getUri()->getPath()))->all())->toBe(['getUpdates', 'sendMessage']);
});

it('shows approved starter packs on the landing page, never pending ones', function () {
    bootWithEnv(['APP_EDITION' => 'cloud']);
    $this->seed(HubPackSeeder::class);
    HubPack::create(['user_id' => User::factory()->create()->id, 'name' => 'Not reviewed yet', 'rules' => [['name' => 'x']]]);

    $this->get('/')->assertOk()
        ->assertSee('Start with a pack')
        ->assertSee('Surprise Workout')
        ->assertSee('🎲 1 in 3')
        ->assertDontSee('Not reviewed yet');
});

it('keeps front-end routes relative when APP_URL is https, so the nav can match them', function () {
    // The production build: https APP_URL, providers booted, then Wayfinder.
    config(['app.url' => 'https://fleche.io']);
    (new AppServiceProvider($this->app))->boot();
    $path = sys_get_temp_dir().'/wayfinder-'.uniqid();

    $this->artisan('wayfinder:generate', ['--path' => $path, '--skip-actions' => true])->assertSuccessful();

    expect(file_get_contents("{$path}/routes/rules/index.ts"))
        ->toContain("url: '/app/rules'")
        ->not->toContain('https://fleche.io');

    File::deleteDirectory($path);
});
