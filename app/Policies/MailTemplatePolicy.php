<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Templates are edited (and reset), never created or deleted: every mail has one per language.
 */
class MailTemplatePolicy extends AreaPolicy
{
    protected function area(): string
    {
        return 'mail_templates';
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, Model $model): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
