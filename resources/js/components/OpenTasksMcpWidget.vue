<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Bot, Loader2, RefreshCcw } from '@lucide/vue';
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
import { fetchOpenTasksViaMcp } from '@/lib/mcp-api';
import type { ApiTask } from '@/lib/tasks-api';

const page = usePage();

const loading = ref(true);
const loadError = ref<string | null>(null);
const tasks = ref<ApiTask[]>([]);

const openCount = computed(() => tasks.value.length);

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

async function load(): Promise<void> {
    loading.value = true;
    loadError.value = null;

    try {
        tasks.value = await fetchOpenTasksViaMcp(page.props.auth.user.id);
    } catch (error) {
        loadError.value =
            error instanceof Error
                ? error.message
                : 'Could not load open tasks via MCP.';
    } finally {
        loading.value = false;
    }
}

onMounted(load);
</script>

<template>
    <Card>
        <CardHeader>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <Bot class="h-4 w-4" />
                    <CardTitle class="text-base">Open tasks</CardTitle>
                    <Badge variant="secondary">via MCP</Badge>
                </div>
                <Link
                    href="/mcp"
                    class="text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
                >
                    MCP console →
                </Link>
            </div>
            <CardDescription>
                Fetched live through the get-open-tasks-tool MCP tool.
            </CardDescription>
        </CardHeader>
        <CardContent>
            <div
                v-if="loading"
                class="flex items-center gap-2 py-6 text-muted-foreground"
            >
                <Loader2 class="h-4 w-4 animate-spin" />
                <span>Calling the MCP endpoint…</span>
            </div>

            <div v-else-if="loadError" class="space-y-3">
                <p class="text-sm text-destructive">{{ loadError }}</p>
                <Button variant="outline" size="sm" @click="load()">
                    <RefreshCcw class="mr-2 h-3.5 w-3.5" />
                    Retry
                </Button>
            </div>

            <div
                v-else-if="openCount === 0"
                class="rounded-xl border border-dashed p-6 text-center text-sm text-muted-foreground"
            >
                No open tasks right now.
            </div>

            <ul v-else class="space-y-3">
                <li
                    v-for="task in tasks"
                    :key="task.id"
                    class="rounded-xl border p-3"
                >
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-sm font-medium">
                            {{ task.title }}
                        </p>
                        <span class="shrink-0 text-xs text-muted-foreground">
                            {{ formatDate(task.created_at) }}
                        </span>
                    </div>
                    <p
                        v-if="task.description"
                        class="mt-1 text-sm text-muted-foreground"
                    >
                        {{ task.description }}
                    </p>
                </li>
            </ul>
        </CardContent>
    </Card>
</template>
