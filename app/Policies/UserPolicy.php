<?php

namespace App\Policies;

use App\Models\User;

// Admin users: managed by the Owner only. Nobody can delete or deactivate themselves.
class UserPolicy extends AdminPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) && $user->isOwner();
    }

    public function view(User $user, $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, $model): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, $model): bool
    {
        return $this->viewAny($user) && $user->isNot($model);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
