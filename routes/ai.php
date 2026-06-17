<?php

use App\Mcp\Servers\ApiRaupulusServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/api-raupulus', ApiRaupulusServer::class);
Mcp::local('api-raupulus', ApiRaupulusServer::class);
