<?php

use App\Mcp\Servers\TaskServer;
use App\Mcp\Tools\GetOpenTasksTool;
use App\Models\Task;
use App\Models\User;

test('the tool returns only open tasks of the given user', function () {
    $user = User::factory()->create();
    $openOne = Task::factory()->for($user)->create(['title' => 'Open one']);
    $openTwo = Task::factory()->for($user)->create(['title' => 'Open two']);
    $completed = Task::factory()->for($user)->completed()->create(['title' => 'Done']);
    $otherUserTask = Task::factory()->create(['title' => 'Someone elses']);

    TaskServer::tool(GetOpenTasksTool::class, ['user_id' => $user->id])
        ->assertSee([$openOne->title, $openTwo->title])
        ->assertDontSee([$completed->title, $otherUserTask->title])
        ->assertStructuredContent(function ($json) use ($openOne, $openTwo) {
            $json->count(2);
            $json->first(fn ($task) => $task
                ->where('id', $openOne->id)
                ->where('title', $openOne->title)
                ->where('is_completed', false)
                ->etc());
            $json->where('1.title', $openTwo->title);
        });
});

test('the tool reports when a user has no open tasks', function () {
    $user = User::factory()->create();
    Task::factory()->for($user)->completed()->create();

    TaskServer::tool(GetOpenTasksTool::class, ['user_id' => $user->id])
        ->assertSee('No open tasks found for this user.');
});

test('the tool requires a user id', function () {
    TaskServer::tool(GetOpenTasksTool::class, [])
        ->assertSee('user id');
});

test('the tool is registered on the task server', function () {
    TaskServer::tools()->assertRegistered(GetOpenTasksTool::class);
});
