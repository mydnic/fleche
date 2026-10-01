<?php

use App\Actions\GenerateTodos;
use App\Models\HubPack;
use App\Models\TodoSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(fn () => Storage::fake());

it('attaches a picture to a rule and passes it to its todos', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('rules.store'), [
        'name' => 'Squats', 'image' => UploadedFile::fake()->image('squat.png'),
    ])->assertRedirect();

    $rule = $user->todoSettings()->sole();
    Storage::assertExists($rule->image);

    app(GenerateTodos::class)->handle($user->today());

    expect($user->todos()->sole()->image_url)->toBe($rule->image_url)->not->toBeNull();
});

it('replaces and removes a rule picture with a spoofed PUT', function () {
    $user = User::factory()->create();
    $rule = TodoSetting::factory()->for($user)->create(['image' => 'images/old.png']);

    $this->actingAs($user)->post(route('rules.update', $rule), ['_method' => 'put', 'name' => 'x', 'image' => UploadedFile::fake()->image('new.png')]);
    expect($rule->refresh()->image)->not->toBe('images/old.png');

    $this->actingAs($user)->post(route('rules.update', $rule), ['_method' => 'put', 'name' => 'x', 'remove_image' => true]);
    expect($rule->refresh()->image)->toBeNull();
});

it('refuses what is not an image', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('todos.store'), ['name' => 'x', 'image' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])
        ->assertSessionHasErrors('image');
});

it('accepts pictures through the API', function () {
    Sanctum::actingAs($user = User::factory()->create());

    $this->post('/api/v1/todos', ['name' => 'x', 'image' => UploadedFile::fake()->image('x.jpg')], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('image_url', fn ($url) => str_contains($url, '/storage/images/'))
        ->assertJsonMissingPath('image');
});

it('copies a pack picture onto the importing instance', function () {
    bootWithEnv(['APP_EDITION' => 'cloud']);
    Storage::fake();
    Http::fake(['https://cdn.example/*' => Http::response('PNGDATA', 200, ['Content-Type' => 'image/png'])]);

    $pack = HubPack::create(['user_id' => User::factory()->create()->id, 'name' => 'P', 'status' => 'approved', 'rules' => [
        ['name' => 'With picture', 'image_url' => 'https://cdn.example/a.png'],
        ['name' => 'Bad picture', 'image_url' => 'file:///etc/passwd'],
    ]]);
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('hub.import', $pack->id));

    [$good, $bad] = $user->todoSettings()->orderBy('id')->get();
    expect($good->image)->not->toBeNull()->and($bad->image)->toBeNull();
    Storage::assertExists($good->image);
});
