<?php

use App\Models\Todo;
use App\Models\TodoSetting;
use App\Models\User;
use Carbon\CarbonImmutable;

it('awards points once when a todo is done', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create(['points' => 5]);

    $this->actingAs($user)->post(route('todos.done', $todo))->assertRedirect();
    $this->actingAs($user)->post(route('todos.done', $todo))->assertRedirect();

    expect($user->refresh()->points)->toBe(5)
        ->and($todo->refresh()->done_at)->not->toBeNull();
});

it('cannot touch somebody else\'s todo', function () {
    $todo = Todo::factory()->create();

    $this->actingAs(User::factory()->create())->post(route('todos.done', $todo))->assertNotFound();
});

it('puts a manual todo on tomorrow by default', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('todos.store'), ['name' => 'Call mum'])->assertRedirect();

    expect($user->todos()->sole()->date->toDateString())->toBe(today()->addDay()->toDateString());
});

it('cannot delete a todo, open or done', function () {
    $user = User::factory()->create();
    $open = Todo::factory()->for($user)->create();

    $this->actingAs($user)->delete("/app/todos/{$open->id}")->assertNotFound();
    $this->actingAs($user)->deleteJson("/api/v1/todos/{$open->id}")->assertMethodNotAllowed();

    expect(Todo::count())->toBe(1);
});

it('renders the main pages', function () {
    $user = User::factory()->create();

    foreach (['today', 'rules.index', 'rules.create', 'settings'] as $route) {
        $this->actingAs($user)->get(route($route))->assertOk();
    }
});

it('creates a rule from the form', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('rules.store'), [
        'name' => 'Trash',
        'days' => ['wednesday'],
        'chance' => 1,
        'points' => 2,
        'every_value' => null,
        'every_unit' => null,
    ])->assertRedirect(route('rules.index'));

    expect($user->todoSettings()->sole()->days)->toBe(['wednesday']);
});

it('duplicates a rule, todos excluded, onto the copy\'s own form', function () {
    $user = User::factory()->create();
    $rule = TodoSetting::factory()->for($user)->create([
        'name' => 'Trash',
        'group' => 'Home',
        'image' => 'rules/bin.jpg',
        'days' => ['wednesday'],
        'chance' => 0.5,
        'points' => 2,
        'active' => false,
    ]);
    Todo::factory()->for($user)->create(['todo_setting_id' => $rule->id]);
    $copied = ['group', 'image', 'days', 'chance', 'points', 'active'];

    $response = $this->actingAs($user)->post(route('rules.duplicate', $rule));
    $copy = $user->todoSettings()->whereKeyNot($rule->id)->sole();

    $response->assertRedirect(route('rules.edit', $copy));
    expect($copy->name)->toBe('Trash (copy)')
        ->and($copy->only($copied))->toBe($rule->only($copied))
        ->and($copy->todos()->count())->toBe(0);

    // The name is already taken, so the next copy counts up.
    $this->actingAs($user)->post(route('rules.duplicate', $rule));
    expect($user->todoSettings()->pluck('name')->all())->toContain('Trash (copy 2)');
});

it('keeps a duplicated name inside the column', function () {
    $user = User::factory()->create();
    $rule = TodoSetting::factory()->for($user)->create(['name' => str_repeat('a', 255)]);

    $this->actingAs($user)->post(route('rules.duplicate', $rule));

    expect($user->todoSettings()->whereKeyNot($rule->id)->sole()->name)
        ->toBe(str_repeat('a', 248).' (copy)');
});

it('moves, renames and dissolves rule groups', function () {
    $user = User::factory()->create();
    $rule = TodoSetting::factory()->for($user)->create();
    $other = TodoSetting::factory()->create(['group' => 'Home']);

    $this->actingAs($user)->patch(route('rules.update', $rule), ['group' => 'Home'])->assertSessionHasNoErrors();
    expect($rule->refresh()->group)->toBe('Home');

    $this->actingAs($user)->put(route('rules.groups'), ['from' => 'Home', 'to' => 'House']);
    expect($rule->refresh()->group)->toBe('House')
        ->and($other->refresh()->group)->toBe('Home');

    $this->actingAs($user)->put(route('rules.groups'), ['from' => 'House', 'to' => null]);
    expect($rule->refresh()->group)->toBeNull();
});

it('separates today from what is still open from earlier days', function () {
    $user = User::factory()->create();
    Todo::factory()->for($user)->create(['name' => 'today']);
    Todo::factory()->for($user)->create(['name' => 'yesterday', 'date' => today()->subDay()]);
    Todo::factory()->for($user)->create(['name' => 'last week', 'date' => today()->subWeek()]);
    Todo::factory()->for($user)->done()->create(['name' => 'done before', 'date' => today()->subDay()]);

    $this->actingAs($user)->get(route('today'))->assertInertia(fn ($page) => $page
        ->where('open.0.name', 'today')
        ->has('open', 1)
        ->where('overdue.0.name', 'yesterday')
        ->where('overdue.1.name', 'last week')
        ->has('overdue', 2));
});

it('lists what was done on a picked day, in the user\'s timezone', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 12:00', 'UTC'));
    $user = User::factory()->create(['timezone' => 'Asia/Tokyo']); // Oct 1st, 21:00 there
    Todo::factory()->for($user)->done()->create(['name' => 'today', 'done_at' => now()]);
    // Sep 30th, 23:30 in Tokyo.
    Todo::factory()->for($user)->done()->create(['name' => 'yesterday late', 'done_at' => CarbonImmutable::parse('2026-09-30 14:30', 'UTC')]);

    $this->actingAs($user)->get(route('today'))->assertInertia(fn ($page) => $page
        ->where('doneOn', '2026-10-01')->has('done', 1)->where('done.0.name', 'today')->where('doneTodayCount', 1));

    $this->actingAs($user)->get(route('today', ['done_on' => '2026-09-30']))->assertInertia(fn ($page) => $page
        ->has('done', 1)->where('done.0.name', 'yesterday late')->where('doneTodayCount', 1));

    $this->actingAs($user)->get(route('today', ['done_on' => '2026-10-05']))->assertSessionHasErrors('done_on');
});
