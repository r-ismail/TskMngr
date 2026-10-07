<?php

use App\Models\Task;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('guests cannot access task endpoints', function () {
    $this->getJson('/api/tasks')->assertUnauthorized();
    $this->postJson('/api/tasks', ['title' => 'Test'])->assertUnauthorized();
    $this->getJson('/api/tasks/1')->assertUnauthorized();
    $this->putJson('/api/tasks/1', ['title' => 'Test'])->assertUnauthorized();
    $this->deleteJson('/api/tasks/1')->assertUnauthorized();
});

test('users can list their own tasks', function () {
    $user = User::factory()->create();
    Task::factory()->count(3)->for($user)->create();
    Task::factory()->count(2)->create(); // another user's tasks

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/tasks');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(3);
});

test('users can create a task', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/tasks', [
        'title' => 'Write documentation',
        'description' => 'Document the task API.',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.title', 'Write documentation')
        ->assertJsonPath('data.is_completed', false);

    $this->assertDatabaseHas('tasks', [
        'title' => 'Write documentation',
        'user_id' => $user->id,
    ]);
});

test('task creation validates input', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/tasks', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('title');
});

test('users can view one of their tasks', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();

    Sanctum::actingAs($user);

    $this->getJson("/api/tasks/{$task->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $task->id);
});

test('users cannot view another users task', function () {
    $task = Task::factory()->create();

    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/tasks/{$task->id}")->assertNotFound();
});

test('users can update a task', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create(['title' => 'Old title']);

    Sanctum::actingAs($user);

    $response = $this->putJson("/api/tasks/{$task->id}", [
        'title' => 'New title',
        'is_completed' => true,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.title', 'New title')
        ->assertJsonPath('data.is_completed', true);
});

test('task update validates input', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();

    Sanctum::actingAs($user);

    $this->putJson("/api/tasks/{$task->id}", ['title' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('title');
});

test('users cannot update another users task', function () {
    $task = Task::factory()->create(['title' => 'Untouched']);

    Sanctum::actingAs(User::factory()->create());

    $this->putJson("/api/tasks/{$task->id}", ['title' => 'Hacked'])
        ->assertNotFound();

    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Untouched']);
});

test('users can delete a task', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();

    Sanctum::actingAs($user);

    $this->deleteJson("/api/tasks/{$task->id}")->assertNoContent();

    $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
});

test('users cannot delete another users task', function () {
    $task = Task::factory()->create();

    Sanctum::actingAs(User::factory()->create());

    $this->deleteJson("/api/tasks/{$task->id}")->assertNotFound();

    $this->assertDatabaseHas('tasks', ['id' => $task->id]);
});
