<?php

namespace Tests\Concerns;

use Illuminate\Testing\TestResponse;

trait SendsMcpRequests
{
    protected const MCP_PROTOCOL_VERSION = '2026-07-28';

    protected function postMcp(string $method, array $params = []): TestResponse
    {
        $params['_meta'] = [
            'io.modelcontextprotocol/protocolVersion' => self::MCP_PROTOCOL_VERSION,
            'io.modelcontextprotocol/clientCapabilities' => [],
        ];

        $headers = [
            'MCP-Protocol-Version' => self::MCP_PROTOCOL_VERSION,
            'Mcp-Method' => $method,
        ];

        if (isset($params['name']) || isset($params['uri'])) {
            $headers['Mcp-Name'] = $params['name'] ?? $params['uri'];
        }

        return $this->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => $method,
            'params' => $params,
        ], $headers);
    }
}
