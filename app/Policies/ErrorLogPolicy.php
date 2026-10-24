<?php

namespace App\Policies;

use App\Models\User;

// Error log: Owner can read, resolve and clear it; entries are only ever written by the app
class ErrorLogPolicy extends AdminPolicy
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
        return false;
    }

    public function update(User $user, $model): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, $model): bool
    {
        return $this->viewAny($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->viewAny($user);
    }
}
