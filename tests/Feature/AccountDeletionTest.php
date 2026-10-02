<?php

use App\Enums\HubPackStatus;
use App\Models\HubPack;
use App\Models\Todo;
use App\Models\TodoSetting;
use App\Models\User;
use App\Support\Hub;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;

it('refuses to delete the account without the right password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->delete(route('settings.account.destroy'), ['password' => 'nope'])
        ->assertSessionHasErrors('password');

    expect($user->fresh())->not->toBeNull();
});

it('erases the account and everything it owns, keeping hub packs anonymous', function () {
    Storage::fake();
    Storage::put('images/rule.png', 'x');
    Storage::put('images/todo.png', 'x');

    $user = User::factory()->create();
    $rule = TodoSetting::factory()->for($user)->create(['image' => 'images/rule.png']);
    Todo::factory()->for($user)->create(['todo_setting_id' => $rule->id, 'image' => 'images/rule.png']);
    Todo::factory()->for($user)->create(['image' => 'images/todo.png']);
    $user->createToken('script');
    $pack = HubPack::create(['user_id' => $user->id, 'name' => 'Shared', 'rules' => [['name' => 'x']], 'status' => HubPackStatus::Approved]);
    $someoneElse = Todo::factory()->create();

    $this->actingAs($user)->delete(route('settings.account.destroy'), ['password' => 'password'])
        ->assertRedirect('/');

    $this->assertGuest();
    expect(User::find($user->id))->toBeNull()
        ->and(TodoSetting::count())->toBe(0)
        ->and(Todo::pluck('id')->all())->toBe([$someoneElse->id])
        ->and(PersonalAccessToken::count())->toBe(0)
        ->and($pack->refresh()->user_id)->toBeNull()
        ->and(app(Hub::class)->present($pack)['author'])->toBe('A former archer');
    Storage::assertMissing('images/rule.png');
    Storage::assertMissing('images/todo.png');
});
