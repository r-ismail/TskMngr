/**
 * Typed JSON-RPC client for the MCP web endpoint (POST /mcp/tasks).
 *
 * - Bootstraps a Sanctum token from the Fortify session when none is stored.
 * - Retries once on 401 after re-minting the token.
 * - Understands both plain JSON and SSE replies (the transport supports both).
 */
import {
    ApiError,
    exchangeSessionForToken,
    getToken,
    type ApiTask,
} from '@/lib/tasks-api';

export type McpToolSchema = {
    type?: string;
    properties?: Record<string, { type?: string; description?: string }>;
    required?: string[];
};

export type McpToolDescriptor = {
    name: string;
    title?: string;
    description?: string;
    inputSchema: McpToolSchema;
};

export type McpToolResult<T> = {
    content: { type: string; text?: string }[];
    structuredContent?: T;
    isError?: boolean;
};

export type McpServerInfo = {
    name: string;
    version: string;
};

type RpcSuccess<T> = {
    jsonrpc: '2.0';
    id: number | string;
    result: T;
};

type RpcFailure = {
    jsonrpc: '2.0';
    id: number | string | null;
    error: { code: number; message: string; data?: unknown };
};

const MCP_ENDPOINT = '/mcp/tasks';
const SERVER_INFO_KEY = 'io.modelcontextprotocol/serverInfo';

let requestCounter = 0;

async function postRpc(body: string, token: string): Promise<Response> {
    return fetch(MCP_ENDPOINT, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json, text/event-stream',
            Authorization: `Bearer ${token}`,
        },
        credentials: 'same-origin',
        body,
    });
}

function parseReply(
    response: Response,
    payload: string,
): RpcSuccess<unknown> | RpcFailure {
    const contentType = response.headers.get('content-type') ?? '';

    if (contentType.includes('text/event-stream')) {
        const frames = payload
            .split(/\r?\n/)
            .filter((line) => line.startsWith('data:'));

        const last = frames.at(-1);

        if (last === undefined) {
            throw new ApiError(
                response.status,
                'The MCP endpoint returned an empty event stream.',
            );
        }

        return JSON.parse(last.slice(5).trim());
    }

    return JSON.parse(payload);
}

async function rpcCall<T>(
    method: string,
    params: Record<string, unknown>,
): Promise<T> {
    const id = ++requestCounter;
    const body = JSON.stringify({ jsonrpc: '2.0', id, method, params });

    let token = getToken() ?? (await exchangeSessionForToken());
    let response = await postRpc(body, token);

    if (response.status === 401) {
        // The stored token may be stale — re-mint it once and retry.
        token = await exchangeSessionForToken();
        response = await postRpc(body, token);
    }

    if (response.status === 401) {
        throw new ApiError(401, 'Unauthenticated. Please log in again.');
    }

    const payload = await response.text();

    if (payload === '') {
        throw new ApiError(
            response.status,
            'The MCP endpoint returned an empty response.',
        );
    }

    const reply = parseReply(response, payload);

    if ('error' in reply) {
        throw new ApiError(
            response.status,
            reply.error.message || `MCP error ${reply.error.code}.`,
        );
    }

    if (!response.ok) {
        throw new ApiError(
            response.status,
            `MCP request failed with status ${response.status}.`,
        );
    }

    return reply.result as T;
}

/**
 * Discover the MCP server: identity metadata + registered tools via
 * the standard `tools/list` method.
 */
export async function discoverMcpServer(): Promise<{
    serverInfo: McpServerInfo | null;
    tools: McpToolDescriptor[];
}> {
    const result = await rpcCall<{
        tools?: McpToolDescriptor[];
        _meta?: Record<string, unknown>;
    }>('tools/list', {});

    const meta = result._meta?.[SERVER_INFO_KEY];

    return {
        serverInfo:
            meta && typeof meta === 'object' ? (meta as McpServerInfo) : null,
        tools: result.tools ?? [],
    };
}

export async function callTool<T>(
    name: string,
    args: Record<string, unknown>,
): Promise<McpToolResult<T>> {
    return rpcCall<McpToolResult<T>>('tools/call', {
        name,
        arguments: args,
    });
}

/**
 * Fetch a user's open tasks through the MCP `get-open-tasks-tool`.
 * Returns `[]` when the user has nothing open (the tool then replies
 * with a plain text message and no structured content).
 */
export async function fetchOpenTasksViaMcp(userId: number): Promise<ApiTask[]> {
    const result = await callTool<ApiTask[]>('get-open-tasks-tool', {
        user_id: userId,
    });

    if (result.isError) {
        const message = result.content.find(
            (part) => part.type === 'text',
        )?.text;

        throw new ApiError(500, message ?? 'The MCP tool returned an error.');
    }

    return result.structuredContent ?? [];
}
