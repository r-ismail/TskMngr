<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\GetOpenTasksTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Task Server')]
#[Version('1.0.0')]
#[Instructions('This server exposes task management tools. Use the get-open-tasks tool to list the open (not completed) tasks of a user by their user ID.')]
class TaskServer extends Server
{
    /**
     * The tools registered with this MCP server.
     */
    protected array $tools = [
        GetOpenTasksTool::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
