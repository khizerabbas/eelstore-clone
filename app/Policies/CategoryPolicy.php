<?php

namespace App\Policies;

use App\Models\User;

class CategoryPolicy extends AdminPolicy
{
    // Deleting a category would also delete its products (FK cascade), so only empty ones can go
    public function delete(User $user, $model): bool
    {
        return parent::delete($user, $model)
            && $model->products()->doesntExist()
            && $model->children()->doesntExist();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
