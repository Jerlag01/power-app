<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RolePolicy extends AreaPolicy
{
    protected function area(): string
    {
        return 'roles';
    }

    /**
     * Only super administrators may change a super role or their own role,
     * so nobody can grant themselves extra permissions.
     *
     * @param  Role  $model
     */
    public function update(User $user, Model $model): bool
    {
        return parent::update($user, $model)
            && ($user->isSuperAdmin() || (! $model->is_super && $user->role_id !== $model->getKey()));
    }

    /**
     * Super roles and roles that still have users cannot be deleted.
     *
     * @param  Role  $model
     */
    public function delete(User $user, Model $model): bool
    {
        return parent::delete($user, $model)
            && ! $model->is_super
            && ! $model->users()->exists();
    }
}
