<?php

use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from the mcp page to login', function () {
    $this->get(route('mcp.index'))->assertRedirect(route('login'));
});

test('authenticated users can visit the mcp page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('mcp.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Mcp'));
});

test('the mcp web endpoint requires a bearer token', function () {
    $this->postJson('/mcp/tasks', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/list',
    ])->assertUnauthorized();
});

test('the mcp web endpoint lists the registered tools', function () {
    $user = User::factory()->create();

    $this->withToken($user->createToken('panel')->plainTextToken)
        ->postJson('/mcp/tasks', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ])
        ->assertOk()
        ->assertJsonPath('result.tools.0.name', 'get-open-tasks-tool');
});

test('the mcp web endpoint can call the open tasks tool', function () {
    $user = User::factory()->create();
    Task::factory()->for($user)->create(['title' => 'Via the MCP endpoint']);
    Task::factory()->for($user)->completed()->create();

    $this->withToken($user->createToken('panel')->plainTextToken)
        ->postJson('/mcp/tasks', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'get-open-tasks-tool',
                'arguments' => ['user_id' => $user->id],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('result.structuredContent.0.title', 'Via the MCP endpoint')
        ->assertJsonPath('result.isError', false);
});
