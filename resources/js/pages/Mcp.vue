<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { Bot, Loader2, Play } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    callTool,
    discoverMcpServer,
    type McpServerInfo,
    type McpToolDescriptor,
} from '@/lib/mcp-api';
import { ApiError } from '@/lib/tasks-api';

const page = usePage();

const loading = ref(true);
const loadError = ref<string | null>(null);
const serverInfo = ref<McpServerInfo | null>(null);
const tools = ref<McpToolDescriptor[]>([]);
const selectedTool = ref('');

const args = ref<Record<string, string>>({});
const running = ref(false);
const runError = ref<string | null>(null);
const resultText = ref<string | null>(null);
const structuredJson = ref<string | null>(null);

const selected = computed(
    () => tools.value.find((tool) => tool.name === selectedTool.value) ?? null,
);

const schemaFields = computed(() =>
    Object.entries(selected.value?.inputSchema.properties ?? {}).map(
        ([key, schema]) => ({
            key,
            type: schema.type ?? 'string',
            description: schema.description ?? '',
            required: (selected.value?.inputSchema.required ?? []).includes(
                key,
            ),
        }),
    ),
);

const mcpJsonSnippet = computed(() =>
    JSON.stringify(
        {
            mcpServers: {
                tasks: {
                    command: 'php',
                    args: ['artisan', 'mcp:start', 'tasks'],
                    cwd: '/absolute/path/to/TskMngr',
                },
            },
        },
        null,
        4,
    ),
);

const httpSnippet = computed(
    () =>
        `POST ${window.location.origin}/mcp/tasks
Authorization: Bearer $TOKEN
Content-Type: application/json

${JSON.stringify(
    {
        jsonrpc: '2.0',
        id: 1,
        method: 'tools/call',
        params: {
            name: 'get-open-tasks-tool',
            arguments: { user_id: 1 },
        },
    },
    null,
    4,
)}`,
);

function friendlyError(error: unknown, fallback: string): string {
    if (error instanceof ApiError) {
        return error.message;
    }

    return error instanceof Error ? error.message : fallback;
}

function clearResult(): void {
    runError.value = null;
    resultText.value = null;
    structuredJson.value = null;
}

function resetArguments(): void {
    const defaults: Record<string, string> = {};

    for (const field of schemaFields.value) {
        defaults[field.key] =
            field.key === 'user_id' ? String(page.props.auth.user.id) : '';
    }

    args.value = defaults;
}

function selectTool(name: string): void {
    selectedTool.value = name;
    resetArguments();
    clearResult();
}

function buildArguments(): Record<string, unknown> | null {
    const built: Record<string, unknown> = {};

    for (const field of schemaFields.value) {
        const raw = (args.value[field.key] ?? '').trim();

        if (raw === '') {
            if (field.required) {
                runError.value = `Missing required argument [${field.key}].`;

                return null;
            }

            continue;
        }

        if (field.type === 'integer' || field.type === 'number') {
            const value = Number(raw);

            if (Number.isNaN(value)) {
                runError.value = `Argument [${field.key}] must be a number.`;

                return null;
            }

            built[field.key] = value;
        } else {
            built[field.key] = raw;
        }
    }

    return built;
}

async function loadServer(): Promise<void> {
    loading.value = true;
    loadError.value = null;

    try {
        const discovery = await discoverMcpServer();

        serverInfo.value = discovery.serverInfo;
        tools.value = discovery.tools;

        if (tools.value.length > 0) {
            selectTool(tools.value[0].name);
        }
    } catch (error) {
        loadError.value = friendlyError(
            error,
            'Could not reach the MCP endpoint.',
        );
    } finally {
        loading.value = false;
    }
}

async function runTool(): Promise<void> {
    if (selectedTool.value === '') {
        return;
    }

    running.value = true;
    clearResult();

    try {
        const input = buildArguments();

        if (input === null) {
            return;
        }

        const result = await callTool(selectedTool.value, input);

        structuredJson.value =
            result.structuredContent === undefined
                ? null
                : JSON.stringify(result.structuredContent, null, 2);

        const text = result.content
            .filter((part) => part.type === 'text')
            .map((part) => part.text ?? '')
            .join('\n')
            .trim();

        resultText.value = text === '' ? null : text;

        if (result.isError) {
            runError.value = resultText.value ?? 'The tool reported an error.';
        }
    } catch (error) {
        runError.value = friendlyError(error, 'The tool call failed.');
    } finally {
        running.value = false;
    }
}

onMounted(loadServer);
</script>

<template>
    <Head title="MCP" />

    <div class="mx-auto w-full max-w-4xl px-4 py-10">
        <div class="mb-8 flex items-center gap-3">
            <span
                class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary text-primary-foreground"
            >
                <Bot class="h-6 w-6" />
            </span>
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    MCP Server
                </h1>
                <p class="text-sm text-muted-foreground">
                    Model Context Protocol tools this app exposes to AI clients.
                </p>
            </div>
        </div>

        <div
            v-if="loading"
            class="flex items-center justify-center gap-2 py-16 text-muted-foreground"
        >
            <Loader2 class="h-5 w-5 animate-spin" />
            <span>Discovering the MCP server…</span>
        </div>

        <div v-else-if="loadError" class="space-y-4">
            <div
                class="rounded-xl border border-destructive/50 bg-destructive/5 p-6 text-sm text-destructive"
            >
                {{ loadError }}
            </div>
            <Button variant="outline" @click="loadServer()">Retry</Button>
        </div>

        <div v-else class="space-y-6">
            <div class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardDescription>Server</CardDescription>
                        <CardTitle class="text-base">
                            {{ serverInfo?.name ?? 'Task Server' }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent
                        class="flex flex-col items-start gap-2 text-sm text-muted-foreground"
                    >
                        <p>Version {{ serverInfo?.version ?? '1.0.0' }}</p>
                        <p>
                            {{ tools.length }}
                            {{ tools.length === 1 ? 'tool' : 'tools' }}
                            registered
                        </p>
                        <Badge variant="outline">read-only</Badge>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardDescription>HTTP transport</CardDescription>
                        <CardTitle class="font-mono text-base">
                            POST /mcp/tasks
                        </CardTitle>
                    </CardHeader>
                    <CardContent
                        class="flex flex-col items-start gap-2 text-sm text-muted-foreground"
                    >
                        <p>For remote clients over the network.</p>
                        <Badge variant="secondary">auth:sanctum</Badge>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardDescription>Local stdio</CardDescription>
                        <CardTitle class="font-mono text-base">
                            mcp:start tasks
                        </CardTitle>
                    </CardHeader>
                    <CardContent
                        class="flex flex-col items-start gap-2 text-sm text-muted-foreground"
                    >
                        <p>For Claude Desktop / Cursor on this machine.</p>
                        <Badge variant="secondary">no token</Badge>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <div
                        class="flex flex-wrap items-center justify-between gap-2"
                    >
                        <div>
                            <CardTitle class="text-base">
                                Tool console
                            </CardTitle>
                            <CardDescription>
                                Every call runs live against the real MCP
                                endpoint.
                            </CardDescription>
                        </div>
                        <Badge variant="secondary">tools/call</Badge>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-2">
                        <Label for="mcp-tool">Tool</Label>
                        <Select
                            :model-value="selectedTool"
                            name="tool"
                            @update:model-value="selectTool(String($event))"
                        >
                            <SelectTrigger
                                id="mcp-tool"
                                class="w-full"
                                data-test="mcp-tool-select"
                            >
                                <SelectValue placeholder="Select a tool" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="tool in tools"
                                    :key="tool.name"
                                    :value="tool.name"
                                >
                                    {{ tool.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p
                            v-if="selected?.description"
                            class="text-sm text-muted-foreground"
                        >
                            {{ selected.description }}
                        </p>
                    </div>

                    <div
                        v-if="schemaFields.length > 0"
                        class="grid gap-4 sm:grid-cols-2"
                    >
                        <div
                            v-for="field in schemaFields"
                            :key="field.key"
                            class="grid gap-2"
                        >
                            <Label :for="`mcp-arg-${field.key}`">
                                {{ field.key }}
                                <span
                                    v-if="field.required"
                                    class="text-destructive"
                                    >*</span
                                >
                            </Label>
                            <Input
                                :id="`mcp-arg-${field.key}`"
                                v-model="args[field.key]"
                                :type="
                                    field.type === 'integer' ||
                                    field.type === 'number'
                                        ? 'number'
                                        : 'text'
                                "
                                :data-test="`mcp-arg-${field.key}`"
                            />
                            <p
                                v-if="field.description"
                                class="text-xs text-muted-foreground"
                            >
                                {{ field.description }}
                            </p>
                        </div>
                    </div>

                    <p v-else class="text-sm text-muted-foreground">
                        This tool takes no arguments.
                    </p>

                    <Button
                        :disabled="running || selectedTool === ''"
                        @click="runTool()"
                    >
                        <Loader2
                            v-if="running"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        <Play v-else class="mr-2 h-4 w-4" />
                        Run tool
                    </Button>

                    <div
                        v-if="runError"
                        class="rounded-xl border border-destructive/50 bg-destructive/5 p-4 text-sm text-destructive"
                    >
                        {{ runError }}
                    </div>

                    <div
                        v-if="resultText && !runError"
                        class="rounded-xl border p-4 text-sm"
                    >
                        {{ resultText }}
                    </div>

                    <div v-if="structuredJson" class="space-y-2">
                        <p class="text-sm font-medium">structuredContent</p>
                        <pre
                            class="max-h-80 overflow-auto rounded-xl border bg-muted/50 p-4 text-xs"
                            >{{ structuredJson }}</pre>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base"
                        >Connect an AI client</CardTitle
                    >
                    <CardDescription>
                        Add this block to the client's mcp.json (Claude Desktop,
                        Cursor, …) for local stdio access.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <pre
                        class="overflow-auto rounded-xl border bg-muted/50 p-4 text-xs"
                        >{{ mcpJsonSnippet }}</pre>
                    <p class="text-sm text-muted-foreground">
                        Or call the authenticated HTTP transport directly:
                    </p>
                    <pre
                        class="overflow-auto rounded-xl border bg-muted/50 p-4 text-xs"
                        >{{ httpSnippet }}</pre>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
