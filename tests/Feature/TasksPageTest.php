<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from the tasks page to login', function () {
    $this->get(route('tasks.index'))->assertRedirect(route('login'));
});

test('authenticated users can visit the tasks page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('tasks.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Tasks'));
});
