<?php

namespace App\Policies;

use App\Models\User;

// Quote requests only come from the storefront form (/bulk-quote)
class QuoteRequestPolicy extends AdminPolicy
{
    public function create(User $user): bool
    {
        return false;
    }
}
