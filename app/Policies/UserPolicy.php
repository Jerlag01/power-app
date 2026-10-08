<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserPolicy extends AreaPolicy
{
    protected function area(): string
    {
        return 'users';
    }

    /**
     * Only super administrators may change a super administrator's account.
     *
     * @param  User  $model
     */
    public function update(User $user, Model $model): bool
    {
        return parent::update($user, $model) && ($user->isSuperAdmin() || ! $model->isSuperAdmin());
    }

    /**
     * Nobody can delete their own account, and only super administrators can
     * delete another super administrator.
     *
     * @param  User  $model
     */
    public function delete(User $user, Model $model): bool
    {
        return parent::delete($user, $model)
            && $user->isNot($model)
            && ($user->isSuperAdmin() || ! $model->isSuperAdmin());
    }
}
