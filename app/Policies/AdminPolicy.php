<?php

namespace App\Policies;

use App\Models\User;

/**
 * Base policy for admin panel resources.
 * Any active admin may view/create/edit; only the Owner may delete.
 * (Filament checks these automatically; customers never have accounts.)
 */
abstract class AdminPolicy
{
    protected function isAdmin(User $user): bool
    {
        return $user->is_active && $user->role !== null;
    }

    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(User $user, $model): bool
    {
        return $this->isAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, $model): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, $model): bool
    {
        return $this->isAdmin($user) && $user->isOwner();
    }

    public function deleteAny(User $user): bool
    {
        return $this->isAdmin($user) && $user->isOwner();
    }

    public function restore(User $user, $model): bool
    {
        return false;
    }

    public function forceDelete(User $user, $model): bool
    {
        return false;
    }

    public function reorder(User $user): bool
    {
        return $this->isAdmin($user);
    }
}
