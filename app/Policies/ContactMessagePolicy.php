<?php

namespace App\Policies;

use App\Models\User;

// Contact messages come from the storefront form – read-only inbox in the admin
class ContactMessagePolicy extends AdminPolicy
{
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, $model): bool
    {
        return false;
    }
}
