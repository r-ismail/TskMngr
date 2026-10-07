<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ListTodo, Loader2, Plus } from '@lucide/vue';
import TaskRow from '@/components/TaskRow.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useTasksManager } from '@/composables/useTasksManager';

const manager = useTasksManager();
</script>

<template>
    <Head title="Tasks" />

    <div class="mx-auto w-full max-w-3xl px-4 py-10">
        <div class="mb-8 flex items-center gap-3">
            <span
                class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary text-primary-foreground"
            >
                <ListTodo class="h-6 w-6" />
            </span>
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Tasks</h1>
                <p class="text-sm text-muted-foreground">
                    Managed through the Sanctum-powered task API.
                </p>
            </div>
        </div>

        <div
            v-if="manager.bootstrapping.value"
            class="flex items-center justify-center gap-2 py-16 text-muted-foreground"
        >
            <Loader2 class="h-5 w-5 animate-spin" />
            <span>Loading…</span>
        </div>

        <div v-else class="space-y-6">
            <Card>
                <CardContent class="pt-6">
                    <form
                        class="space-y-3"
                        @submit.prevent="manager.submitNewTask()"
                    >
                        <div class="flex flex-col gap-3 sm:flex-row">
                            <Input
                                v-model="manager.newTitle.value"
                                placeholder="What needs to be done?"
                                aria-label="New task title"
                            />
                            <Button
                                type="submit"
                                :disabled="manager.saving.value"
                                class="shrink-0"
                            >
                                <Loader2
                                    v-if="manager.saving.value"
                                    class="mr-2 h-4 w-4 animate-spin"
                                />
                                <Plus v-else class="mr-2 h-4 w-4" />
                                Add task
                            </Button>
                        </div>
                        <Input
                            v-model="manager.newDescription.value"
                            placeholder="Description (optional)"
                            aria-label="New task description"
                        />
                        <p
                            v-if="
                                manager.fieldError(
                                    manager.taskErrors.value,
                                    'title',
                                )
                            "
                            class="text-sm text-destructive"
                        >
                            {{
                                manager.fieldError(
                                    manager.taskErrors.value,
                                    'title',
                                )
                            }}
                        </p>
                    </form>
                </CardContent>
            </Card>

            <div
                v-if="manager.tasksLoading.value"
                class="flex items-center justify-center gap-2 py-10 text-muted-foreground"
            >
                <Loader2 class="h-5 w-5 animate-spin" />
                <span>Loading tasks…</span>
            </div>

            <div
                v-else-if="manager.tasks.value.length === 0"
                class="rounded-xl border border-dashed p-10 text-center text-muted-foreground"
            >
                No tasks yet. Add your first one above.
            </div>

            <template v-else>
                <section
                    v-if="manager.openTasks.value.length > 0"
                    class="space-y-3"
                >
                    <h2
                        class="text-sm font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Open · {{ manager.openTasks.value.length }}
                    </h2>
                    <TaskRow
                        v-for="task in manager.openTasks.value"
                        :key="task.id"
                        :task="task"
                        :busy="manager.isMutating(task.id)"
                        :editing="manager.editingId.value === task.id"
                        :edit-title="manager.editTitle.value"
                        :edit-description="manager.editDescription.value"
                        @toggle="manager.toggleComplete(task)"
                        @edit="manager.startEditing(task)"
                        @cancel-edit="manager.cancelEditing()"
                        @save-edit="manager.saveEditing(task)"
                        @remove="manager.removeTask(task)"
                        @update:edit-title="manager.editTitle.value = $event"
                        @update:edit-description="
                            manager.editDescription.value = $event
                        "
                    />
                </section>

                <section
                    v-if="manager.completedTasks.value.length > 0"
                    class="space-y-3"
                >
                    <h2
                        class="text-sm font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Completed · {{ manager.completedTasks.value.length }}
                    </h2>
                    <TaskRow
                        v-for="task in manager.completedTasks.value"
                        :key="task.id"
                        :task="task"
                        :busy="manager.isMutating(task.id)"
                        :editing="manager.editingId.value === task.id"
                        :edit-title="manager.editTitle.value"
                        :edit-description="manager.editDescription.value"
                        @toggle="manager.toggleComplete(task)"
                        @edit="manager.startEditing(task)"
                        @cancel-edit="manager.cancelEditing()"
                        @save-edit="manager.saveEditing(task)"
                        @remove="manager.removeTask(task)"
                        @update:edit-title="manager.editTitle.value = $event"
                        @update:edit-description="
                            manager.editDescription.value = $event
                        "
                    />
                </section>
            </template>
        </div>
    </div>
</template>
