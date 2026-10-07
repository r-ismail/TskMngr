<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('users can register and receive an api token', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Test User',
        'email' => 'api@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['user', 'token']);

    $this->assertDatabaseHas('users', ['email' => 'api@example.com']);
    $this->assertDatabaseCount('personal_access_tokens', 1);

    // The new user also gets a personal team, like the web flow.
    expect(User::where('email', 'api@example.com')->first()->currentTeam)->not->toBeNull();
});

test('registration validates input', function () {
    $this->postJson('/api/register', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

test('users can log in and receive an api token', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonPath('user.email', $user->email)
        ->assertJsonStructure(['user', 'token']);

    $this->assertDatabaseCount('personal_access_tokens', 1);
});

test('users cannot log in with invalid credentials', function () {
    $user = User::factory()->create();

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertUnprocessable();

    $this->assertDatabaseCount('personal_access_tokens', 0);
});

test('guests cannot access the current user endpoint', function () {
    $this->getJson('/api/user')->assertUnauthorized();
});

test('authenticated users can fetch their profile', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('data.email', $user->email);
});

test('authenticated users can log out and revoke their token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/logout');

    $response->assertOk();
    $this->assertDatabaseCount('personal_access_tokens', 0);
});

test('session authenticated users can mint an api token', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/token');

    $response->assertOk()->assertJsonStructure(['token']);
    $this->assertDatabaseCount('personal_access_tokens', 1);
});

test('guests cannot mint an api token from the web bridge', function () {
    $this->postJson('/api/token')->assertUnauthorized();
});
