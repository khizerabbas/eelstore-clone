<?php

namespace App\Policies;

use App\Models\User;

// Activity log: Owner can read it, nobody can change or delete entries from the admin
class AdminActivityPolicy extends AdminPolicy
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
        return false;
    }

    public function delete(User $user, $model): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
