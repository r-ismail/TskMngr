<?php

use App\Mcp\Servers\TaskServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/tasks', TaskServer::class)->middleware(['auth:sanctum']);
Mcp::local('tasks', TaskServer::class);
