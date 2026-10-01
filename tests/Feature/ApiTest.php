<?php

use App\Models\Todo;
use App\Models\TodoSetting;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('requires a key', function () {
    $this->getJson('/api/v1/todos')->assertUnauthorized();
});

it('lists todos with filters', function () {
    $user = User::factory()->create();
    Todo::factory()->for($user)->create(['date' => '2026-10-01', 'name' => 'old']);
    Todo::factory()->for($user)->create(['date' => '2026-10-05', 'name' => 'open']);
    Todo::factory()->for($user)->done()->create(['date' => '2026-10-05', 'name' => 'done']);
    Todo::factory()->create(['date' => '2026-10-05', 'name' => 'not mine']);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/todos?from=2026-10-02&active=1')->assertOk()->assertJsonCount(1)->assertJsonPath('0.name', 'open');
    $this->getJson('/api/v1/todos?active=0')->assertJsonCount(1)->assertJsonPath('0.name', 'done');
    $this->getJson('/api/v1/todos?to=2026-10-01')->assertJsonCount(1)->assertJsonPath('0.name', 'old');
});

it('marks done, with no way back', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create();
    Sanctum::actingAs($user);

    $this->postJson("/api/v1/todos/{$todo->id}/done")->assertOk()->assertJsonPath('id', $todo->id);
    expect($todo->refresh()->done_at)->not->toBeNull();

    $this->deleteJson("/api/v1/todos/{$todo->id}")->assertMethodNotAllowed();
});

it('manages todo settings', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $id = $this->postJson('/api/v1/todo-settings', ['name' => 'Stretch', 'chance' => 0.33])->assertCreated()->json('id');
    $this->patchJson("/api/v1/todo-settings/{$id}", ['active' => false])->assertOk()->assertJsonPath('active', false);

    $other = TodoSetting::factory()->create();
    $this->getJson("/api/v1/todo-settings/{$other->id}")->assertNotFound();
});
