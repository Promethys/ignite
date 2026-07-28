<?php

use App\Mcp\Servers\IgniteServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp', IgniteServer::class)
    ->middleware(['auth:sanctum', 'throttle:mcp']);

Mcp::local('/ignite', IgniteServer::class);
