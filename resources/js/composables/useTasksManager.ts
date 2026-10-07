import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';
import {
    ApiError,
    createTask,
    deleteTask,
    exchangeSessionForToken,
    fetchTasks,
    fetchUser,
    getToken,
    login,
    logout,
    register,
    updateTask,
    type ApiTask,
    type ApiUser,
} from '@/lib/tasks-api';

export type AuthMode = 'login' | 'register';

/**
 * Reactive state + actions for the API-driven tasks page. All data comes
 * from the Sanctum token API; nothing is passed as Inertia props.
 */
export function useTasksManager() {
    const page = usePage();
    const sessionUser = computed(
        () =>
            (page.props.auth as { user?: ApiUser | null } | undefined)?.user ??
            null,
    );

    const bootstrapping = ref(true);
    const authMode = ref<AuthMode>('login');
    const authBusy = ref(false);
    const authForm = ref({ name: '', email: '', password: '' });
    const authErrors = ref<Record<string, string[]>>({});
    const user = ref<ApiUser | null>(null);

    const tasks = ref<ApiTask[]>([]);
    const tasksLoading = ref(false);
    const saving = ref(false);
    const mutatingIds = ref<Set<number>>(new Set());

    const newTitle = ref('');
    const newDescription = ref('');
    const taskErrors = ref<Record<string, string[]>>({});

    const editingId = ref<number | null>(null);
    const editTitle = ref('');
    const editDescription = ref('');

    const openTasks = computed(() =>
        tasks.value.filter((task) => !task.is_completed),
    );
    const completedTasks = computed(() =>
        tasks.value.filter((task) => task.is_completed),
    );

    function fieldError(
        errors: Record<string, string[]>,
        field: string,
    ): string | null {
        return errors[field]?.[0] ?? null;
    }

    function friendlyError(error: unknown, fallback: string): string {
        if (error instanceof ApiError) {
            return error.message;
        }

        return error instanceof Error ? error.message : fallback;
    }

    async function loadTasks(): Promise<void> {
        tasksLoading.value = true;

        try {
            tasks.value = await fetchTasks();
        } catch (error) {
            if (error instanceof ApiError && error.status === 401) {
                user.value = null;

                return;
            }

            toast.error(friendlyError(error, 'Could not load tasks.'));
        } finally {
            tasksLoading.value = false;
        }
    }

    async function authenticate(): Promise<void> {
        authBusy.value = true;
        authErrors.value = {};

        try {
            const { email, password, name } = authForm.value;

            const result =
                authMode.value === 'login'
                    ? await login(email, password)
                    : await register(name, email, password);

            user.value = result.user;
            authForm.value = { name: '', email: '', password: '' };
            toast.success(
                authMode.value === 'login'
                    ? 'Welcome back!'
                    : 'Account created. Welcome!',
            );
            await loadTasks();
        } catch (error) {
            if (error instanceof ApiError) {
                authErrors.value = error.errors;
            }

            toast.error(friendlyError(error, 'Authentication failed.'));
        } finally {
            authBusy.value = false;
        }
    }

    async function signOut(): Promise<void> {
        try {
            await logout();
        } finally {
            user.value = null;
            tasks.value = [];
            toast.success('Logged out.');
        }
    }

    async function submitNewTask(): Promise<void> {
        if (!newTitle.value.trim()) {
            taskErrors.value = { title: ['Please enter a task title.'] };

            return;
        }

        saving.value = true;
        taskErrors.value = {};

        try {
            const task = await createTask({
                title: newTitle.value.trim(),
                description: newDescription.value.trim() || null,
            });

            tasks.value = [task, ...tasks.value];
            newTitle.value = '';
            newDescription.value = '';
            toast.success('Task created.');
        } catch (error) {
            if (error instanceof ApiError) {
                taskErrors.value = error.errors;
            }

            toast.error(friendlyError(error, 'Could not create the task.'));
        } finally {
            saving.value = false;
        }
    }

    async function runMutation(
        id: number,
        action: () => Promise<void>,
        failureMessage: string,
    ): Promise<void> {
        mutatingIds.value = new Set(mutatingIds.value).add(id);

        try {
            await action();
        } catch (error) {
            toast.error(friendlyError(error, failureMessage));
        } finally {
            const ids = new Set(mutatingIds.value);
            ids.delete(id);
            mutatingIds.value = ids;
        }
    }

    async function toggleComplete(task: ApiTask): Promise<void> {
        await runMutation(
            task.id,
            async () => {
                const updated = await updateTask(task.id, {
                    is_completed: !task.is_completed,
                });

                tasks.value = tasks.value.map((item) =>
                    item.id === updated.id ? updated : item,
                );
            },
            'Could not update the task.',
        );
    }

    function startEditing(task: ApiTask): void {
        editingId.value = task.id;
        editTitle.value = task.title;
        editDescription.value = task.description ?? '';
    }

    function cancelEditing(): void {
        editingId.value = null;
        editTitle.value = '';
        editDescription.value = '';
    }

    async function saveEditing(task: ApiTask): Promise<void> {
        if (!editTitle.value.trim()) {
            toast.error('Please enter a task title.');

            return;
        }

        await runMutation(
            task.id,
            async () => {
                const updated = await updateTask(task.id, {
                    title: editTitle.value.trim(),
                    description: editDescription.value.trim() || null,
                });

                tasks.value = tasks.value.map((item) =>
                    item.id === updated.id ? updated : item,
                );
                cancelEditing();
                toast.success('Task updated.');
            },
            'Could not update the task.',
        );
    }

    async function removeTask(task: ApiTask): Promise<void> {
        if (!confirm(`Delete "${task.title}"?`)) {
            return;
        }

        await runMutation(
            task.id,
            async () => {
                await deleteTask(task.id);
                tasks.value = tasks.value.filter((item) => item.id !== task.id);
                toast.success('Task deleted.');
            },
            'Could not delete the task.',
        );
    }

    function isMutating(id: number): boolean {
        return mutatingIds.value.has(id);
    }

    onMounted(async () => {
        bootstrapping.value = true;

        try {
            if (getToken()) {
                user.value = await fetchUser();
            }

            if (!user.value && sessionUser.value) {
                // Already logged in through the web app: mint an API token
                // silently so the user does not need to log in twice.
                try {
                    await exchangeSessionForToken();
                    user.value = await fetchUser();
                } catch {
                    user.value = null;
                }
            }

            if (user.value) {
                await loadTasks();
            }
        } finally {
            bootstrapping.value = false;
        }
    });

    return {
        sessionUser,
        bootstrapping,
        authMode,
        authBusy,
        authForm,
        authErrors,
        user,
        tasks,
        tasksLoading,
        saving,
        newTitle,
        newDescription,
        taskErrors,
        editingId,
        editTitle,
        editDescription,
        openTasks,
        completedTasks,
        fieldError,
        authenticate,
        signOut,
        submitNewTask,
        toggleComplete,
        startEditing,
        cancelEditing,
        saveEditing,
        removeTask,
        isMutating,
    };
}
