/**
 * Minimal typed fetch client for the Sanctum token API (/api/*).
 *
 * - Attaches the stored Bearer token (localStorage `tasks_api_token`).
 * - Sends the X-XSRF-TOKEN header when a CSRF cookie is present (needed for
 *   the session-based `POST /api/token` bridge endpoint).
 * - Throws {@link ApiError} with validation errors on 422 and on other
 *   non-2xx responses; clears the stored token on 401.
 */

export const TOKEN_KEY = 'tasks_api_token';

export type ApiUser = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
};

export type ApiTask = {
    id: number;
    user_id: number;
    title: string;
    description: string | null;
    is_completed: boolean;
    created_at: string;
    updated_at: string;
};

export function getToken(): string | null {
    try {
        return localStorage.getItem(TOKEN_KEY);
    } catch {
        return null;
    }
}

export function setToken(token: string): void {
    localStorage.setItem(TOKEN_KEY, token);
}

export function clearToken(): void {
    try {
        localStorage.removeItem(TOKEN_KEY);
    } catch {
        // Storage unavailable (SSR, private mode) — ignore.
    }
}

export class ApiError extends Error {
    status: number;
    errors: Record<string, string[]>;

    constructor(
        status: number,
        message: string,
        errors: Record<string, string[]> = {},
    ) {
        super(message);
        this.status = status;
        this.errors = errors;
    }
}

function readXsrfToken(): string | null {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);

    if (!match) {
        return null;
    }

    try {
        return decodeURIComponent(match[1]);
    } catch {
        return null;
    }
}

export async function apiFetch<T>(
    path: string,
    init: RequestInit = {},
): Promise<T> {
    const token = getToken();

    const headers = new Headers(init.headers ?? {});
    headers.set('Accept', 'application/json');

    if (init.body !== undefined && !headers.has('Content-Type')) {
        headers.set('Content-Type', 'application/json');
    }

    if (token) {
        headers.set('Authorization', `Bearer ${token}`);
    }

    // The session-based POST /api/token bridge requires CSRF protection.
    const xsrf = readXsrfToken();

    if (xsrf && !headers.has('X-XSRF-TOKEN')) {
        headers.set('X-XSRF-TOKEN', xsrf);
    }

    const response = await fetch(`/api${path}`, {
        ...init,
        headers,
        credentials: 'same-origin',
    });

    if (response.status === 401) {
        clearToken();
        throw new ApiError(401, 'Unauthenticated. Please log in again.');
    }

    if (response.status === 204) {
        return undefined as T;
    }

    const payload = (await response.json().catch(() => null)) as {
        message?: string;
        errors?: Record<string, string[]>;
    } | null;

    if (!response.ok) {
        throw new ApiError(
            response.status,
            payload?.message ??
                `Request failed with status ${response.status}.`,
            payload?.errors ?? {},
        );
    }

    return payload as T;
}

export async function login(
    email: string,
    password: string,
): Promise<{ user: ApiUser; token: string }> {
    const result = await apiFetch<{ user: ApiUser; token: string }>('/login', {
        method: 'POST',
        body: JSON.stringify({ email, password }),
    });

    setToken(result.token);

    return result;
}

export async function register(
    name: string,
    email: string,
    password: string,
): Promise<{ user: ApiUser; token: string }> {
    const result = await apiFetch<{ user: ApiUser; token: string }>(
        '/register',
        {
            method: 'POST',
            body: JSON.stringify({
                name,
                email,
                password,
                password_confirmation: password,
            }),
        },
    );

    setToken(result.token);

    return result;
}

export async function logout(): Promise<void> {
    try {
        await apiFetch<{ message: string }>('/logout', { method: 'POST' });
    } finally {
        clearToken();
    }
}

export async function fetchUser(): Promise<ApiUser | null> {
    try {
        const result = await apiFetch<{ data: ApiUser }>('/user');

        return result.data;
    } catch (error) {
        if (error instanceof ApiError && error.status === 401) {
            return null;
        }

        throw error;
    }
}

/**
 * Exchange the Inertia session login for an API token so users who are
 * already logged in through the web app do not need to log in twice.
 */
export async function exchangeSessionForToken(): Promise<string> {
    const response = await fetch('/api/token', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            ...(readXsrfToken()
                ? { 'X-XSRF-TOKEN': readXsrfToken() as string }
                : {}),
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new ApiError(
            response.status,
            'Could not issue an API token for this session.',
        );
    }

    const payload = (await response.json()) as { token: string };

    setToken(payload.token);

    return payload.token;
}

export async function fetchTasks(): Promise<ApiTask[]> {
    const result = await apiFetch<{ data: ApiTask[] }>('/tasks');

    return result.data;
}

export async function createTask(input: {
    title: string;
    description?: string | null;
}): Promise<ApiTask> {
    const result = await apiFetch<{ data: ApiTask }>('/tasks', {
        method: 'POST',
        body: JSON.stringify(input),
    });

    return result.data;
}

export async function updateTask(
    id: number,
    input: Partial<Pick<ApiTask, 'title' | 'description' | 'is_completed'>>,
): Promise<ApiTask> {
    const result = await apiFetch<{ data: ApiTask }>(`/tasks/${id}`, {
        method: 'PUT',
        body: JSON.stringify(input),
    });

    return result.data;
}

export async function deleteTask(id: number): Promise<void> {
    await apiFetch<void>(`/tasks/${id}`, { method: 'DELETE' });
}
