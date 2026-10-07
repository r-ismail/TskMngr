<?php

namespace App\Mcp\Tools;

use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Get all open (not completed) tasks for a given user.')]
class GetOpenTasksTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        ['user_id' => $userId] = $request->validate([
            'user_id' => ['required', 'integer', 'min:1'],
        ]);

        $tasks = Task::query()
            ->where('user_id', $userId)
            ->where('is_completed', false)
            ->orderByDesc('created_at')
            ->get();

        if ($tasks->isEmpty()) {
            return Response::text('No open tasks found for this user.');
        }

        return Response::structured($tasks->toArray());
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'user_id' => $schema->integer()
                ->description('The ID of the user whose open tasks should be returned.')
                ->required(),
        ];
    }
}
