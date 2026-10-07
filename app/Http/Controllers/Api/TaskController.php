<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tasks\StoreTaskRequest;
use App\Http\Requests\Tasks\UpdateTaskRequest;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    /**
     * Display a listing of the authenticated user's tasks.
     */
    public function index(): JsonResponse
    {
        $tasks = $this->ownedTasks()->latest()->get();

        return response()->json(['data' => $tasks]);
    }

    /**
     * Store a newly created task for the authenticated user.
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $request->user()->tasks()->create($request->validated());

        return response()->json(['data' => $task->fresh()], 201);
    }

    /**
     * Display the specified task.
     */
    public function show(Task $task): JsonResponse
    {
        $this->ensureOwnership($task);

        return response()->json(['data' => $task]);
    }

    /**
     * Update the specified task.
     */
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $this->ensureOwnership($task);

        $task->update($request->validated());

        return response()->json(['data' => $task->refresh()]);
    }

    /**
     * Remove the specified task.
     */
    public function destroy(Task $task): JsonResponse
    {
        $this->ensureOwnership($task);

        $task->delete();

        return response()->json(null, 204);
    }

    /**
     * Get the base query scoped to the authenticated user's tasks.
     *
     * @return Builder<Task>
     */
    private function ownedTasks()
    {
        /** @var User $user */
        $user = request()->user();

        return $user->tasks()->getQuery();
    }

    /**
     * Abort with a 404 if the task does not belong to the current user.
     */
    private function ensureOwnership(Task $task): void
    {
        abort_unless((int) $task->user_id === (int) request()->user()->id, 404);
    }
}
