<?php

use App\Enums\HubPackStatus;
use App\Models\HubPack;
use App\Models\Todo;
use App\Models\TodoSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookReceived;
use Laravel\Sanctum\Sanctum;

/*
 * Nobody touches somebody else's account: every route that takes an id is
 * tried against another user's record. Not found (404), never a leak.
 */

beforeEach(function () {
    $this->victim = User::factory()->create();
    $this->attacker = User::factory()->create();
    $this->rule = TodoSetting::factory()->for($this->victim)->create(['name' => 'Victim rule']);
    $this->todo = Todo::factory()->for($this->victim)->create(['name' => 'Victim todo', 'points' => 50]);
});

it('keeps web routes to the owner', function () {
    $this->actingAs($this->attacker);

    $this->post(route('todos.done', $this->todo))->assertNotFound();
    $this->get(route('rules.edit', $this->rule))->assertNotFound();
    $this->put(route('rules.update', $this->rule), ['name' => 'pwned'])->assertNotFound();
    $this->patch(route('rules.update', $this->rule), ['active' => false])->assertNotFound();
    $this->delete(route('rules.destroy', $this->rule))->assertNotFound();

    expect($this->todo->refresh()->done_at)->toBeNull()
        ->and($this->rule->refresh()->name)->toBe('Victim rule')
        ->and($this->rule->active)->toBeTrue()
        ->and($this->victim->refresh()->points)->toBe(0);
});

it('keeps API routes to the token owner', function () {
    Sanctum::actingAs($this->attacker);

    $this->getJson("/api/v1/todos/{$this->todo->id}")->assertNotFound();
    $this->postJson("/api/v1/todos/{$this->todo->id}/done")->assertNotFound();
    $this->getJson("/api/v1/todo-settings/{$this->rule->id}")->assertNotFound();
    $this->patchJson("/api/v1/todo-settings/{$this->rule->id}", ['name' => 'pwned'])->assertNotFound();
    $this->deleteJson("/api/v1/todo-settings/{$this->rule->id}")->assertNotFound();

    expect($this->getJson('/api/v1/todos')->json())->toBe([])
        ->and($this->getJson('/api/v1/todo-settings')->json())->toBe([])
        ->and($this->rule->fresh())->not->toBeNull();
});

it('never lets a request choose who owns what it creates', function () {
    Sanctum::actingAs($this->attacker);

    $todo = $this->postJson('/api/v1/todos', ['name' => 'x', 'user_id' => $this->victim->id])->assertCreated()->json();
    $rule = $this->postJson('/api/v1/todo-settings', ['name' => 'x', 'user_id' => $this->victim->id])->assertCreated()->json();

    expect(Todo::find($todo['id'])->user_id)->toBe($this->attacker->id)
        ->and(TodoSetting::find($rule['id'])->user_id)->toBe($this->attacker->id)
        ->and($this->victim->todoSettings()->count())->toBe(1);
});

it('cannot revoke another user\'s API key', function () {
    $token = $this->victim->createToken('victim')->accessToken;

    $this->actingAs($this->attacker)->delete(route('settings.tokens.destroy', $token->id));

    expect($this->victim->tokens()->count())->toBe(1);
});

it('cannot publish another user\'s rules on the hub', function () {
    bootWithEnv(['APP_EDITION' => 'cloud']);
    $victim = User::factory()->create();
    $attacker = User::factory()->create();
    $rule = TodoSetting::factory()->for($victim)->create();

    $this->actingAs($attacker)->post(route('hub.publish'), ['name' => 'Stolen', 'rule_ids' => [$rule->id]])->assertStatus(422);

    expect(HubPack::count())->toBe(0);
});

it('keeps moderation to the cloud admin', function () {
    bootWithEnv(['APP_EDITION' => 'cloud']);
    $pack = HubPack::create(['user_id' => User::factory()->create()->id, 'name' => 'P', 'rules' => [['name' => 'x']]]);

    $this->actingAs(User::factory()->create())->patch(route('hub.moderate', $pack), ['status' => 'approved'])->assertForbidden();

    expect($pack->refresh()->status)->toBe(HubPackStatus::Pending);
});

it('refuses picture types that could run script (SVG)', function () {
    $svg = UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

    $this->actingAs($this->attacker)->post(route('rules.store'), ['name' => 'x', 'image' => $svg])->assertSessionHasErrors('image');
});

it('ignores Stripe webhooks when no signing secret is configured', function () {
    bootWithEnv(['APP_EDITION' => 'cloud']);
    config(['cashier.webhook.secret' => null]);
    $user = User::factory()->create();

    event(new WebhookReceived(['type' => 'checkout.session.completed', 'data' => ['object' => [
        'payment_status' => 'paid', 'client_reference_id' => (string) $user->id,
    ]]]));

    expect($user->refresh()->paid_at)->toBeNull();
});

it('rejects unsigned Stripe webhooks once a secret is set', function () {
    // A self-hosted boot earlier in this process turned Cashier's routes off
    // (a static flag); production boots once, as cloud, and keeps them.
    Cashier::$registersRoutes = true;
    bootWithEnv(['APP_EDITION' => 'cloud']);
    config(['cashier.webhook.secret' => 'whsec_test']);
    $user = User::factory()->create();

    $this->postJson('/stripe/webhook', ['type' => 'checkout.session.completed', 'data' => ['object' => [
        'payment_status' => 'paid', 'client_reference_id' => (string) $user->id,
    ]]])->assertForbidden();

    expect($user->refresh()->paid_at)->toBeNull();
});

it('counts a hub import once per importer, ever, so nobody can farm a pack', function () {
    bootWithEnv(['APP_EDITION' => 'cloud']);
    $author = User::factory()->create();
    $pack = HubPack::create(['user_id' => $author->id, 'name' => 'P', 'rules' => [['name' => 'x']], 'status' => 'approved']);

    foreach (range(1, 5) as $i) {
        $this->postJson("/api/hub/packs/{$pack->id}/imports")->assertOk();
        Cache::flush(); // a deploy clears the cache; the dedupe lives in the database
    }
    $importer = User::factory()->create();
    $this->actingAs($importer)->post(route('hub.import', $pack->id));
    $this->actingAs($importer)->post(route('hub.import', $pack->id));

    // One anonymous IP + one signed-in user.
    expect(DB::table('hub_imports')->pluck('importer')->every(fn ($hash) => strlen($hash) === 64 && ! str_contains($hash, '127.0.0.1')))->toBeTrue()
        ->and($pack->refresh()->imports_count)->toBe(2)
        ->and($author->refresh()->points)->toBe(2 * config('fleche.hub_import_points'));
});
