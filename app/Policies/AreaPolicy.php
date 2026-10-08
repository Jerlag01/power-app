<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Maps the admin panel's abilities onto the role permissions of one area,
 * e.g. "posts.view", "posts.update".
 */
abstract class AreaPolicy
{
    abstract protected function area(): string;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission($this->area().'.view');
    }

    public function view(User $user, Model $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission($this->area().'.create');
    }

    public function update(User $user, Model $model): bool
    {
        return $user->hasPermission($this->area().'.update');
    }

    public function reorder(User $user): bool
    {
        return $user->hasPermission($this->area().'.update');
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->hasPermission($this->area().'.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermission($this->area().'.delete');
    }
}
