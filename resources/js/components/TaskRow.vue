<script setup lang="ts">
import { Loader2, Pencil, Trash2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import type { ApiTask } from '@/lib/tasks-api';

defineProps<{
    task: ApiTask;
    busy: boolean;
    editing: boolean;
    editTitle: string;
    editDescription: string;
}>();

defineEmits<{
    toggle: [];
    edit: [];
    cancelEdit: [];
    saveEdit: [];
    remove: [];
    'update:editTitle': [value: string];
    'update:editDescription': [value: string];
}>();
</script>

<template>
    <div class="rounded-xl border bg-card p-4 shadow-xs">
        <div v-if="!editing" class="flex items-start gap-3">
            <Checkbox
                :model-value="task.is_completed"
                :disabled="busy"
                aria-label="Toggle task completion"
                class="mt-1"
                @update:model-value="$emit('toggle')"
            />
            <div class="min-w-0 flex-1">
                <p
                    :class="[
                        'font-medium break-words',
                        task.is_completed &&
                            'text-muted-foreground line-through',
                    ]"
                >
                    {{ task.title }}
                </p>
                <p
                    v-if="task.description"
                    class="mt-1 text-sm break-words text-muted-foreground"
                >
                    {{ task.description }}
                </p>
            </div>
            <div class="flex shrink-0 items-center gap-1">
                <Button
                    variant="ghost"
                    size="icon-sm"
                    :disabled="busy"
                    aria-label="Edit task"
                    @click="$emit('edit')"
                >
                    <Loader2 v-if="busy" class="h-4 w-4 animate-spin" />
                    <Pencil v-else class="h-4 w-4" />
                </Button>
                <Button
                    variant="ghost"
                    size="icon-sm"
                    :disabled="busy"
                    aria-label="Delete task"
                    @click="$emit('remove')"
                >
                    <Trash2 class="h-4 w-4 text-destructive" />
                </Button>
            </div>
        </div>
        <div v-else class="space-y-3">
            <Input
                :model-value="editTitle"
                placeholder="Task title"
                aria-label="Edit task title"
                @update:model-value="$emit('update:editTitle', String($event))"
            />
            <Input
                :model-value="editDescription"
                placeholder="Description (optional)"
                aria-label="Edit task description"
                @update:model-value="
                    $emit('update:editDescription', String($event))
                "
            />
            <div class="flex justify-end gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="busy"
                    @click="$emit('cancelEdit')"
                    >Cancel</Button
                >
                <Button size="sm" :disabled="busy" @click="$emit('saveEdit')"
                    >Save</Button
                >
            </div>
        </div>
    </div>
</template>
