<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('tasks.viewAny') || $user->can('tasks.view');
    }

    public function view(User $user, Task $task): bool
    {
        if ($user->can('tasks.viewAny')) {
            return true;
        }

        return $user->id === $task->user_id;
    }

    public function create(User $user): bool
    {
        return $user->can('tasks.create');
    }

    public function update(User $user, Task $task): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->id === $task->user_id && $user->can('tasks.update');
    }

    public function delete(User $user, Task $task): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->id === $task->user_id && $user->can('tasks.delete');
    }
}
