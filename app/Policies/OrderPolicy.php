<?php

namespace App\Policies;

use App\Models\User;

// Orders only come from the storefront checkout – admins can't create them
class OrderPolicy extends AdminPolicy
{
    public function create(User $user): bool
    {
        return false;
    }
}
