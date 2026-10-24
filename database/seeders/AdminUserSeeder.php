<?php

namespace Database\Seeders;

use App\Enums\AdminRole;
use App\Models\User;
use Illuminate\Database\Seeder;

// Creates the first Owner from SEED_ADMIN_EMAIL / SEED_ADMIN_PASSWORD (skipped when not set)
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email    = config('admin.seed_email');
        $password = config('admin.seed_password');

        if (! $email || ! $password) {
            return;
        }

        User::firstOrCreate(['email' => $email], [
            'name'      => 'Store Owner',
            'password'  => $password,
            'role'      => AdminRole::Owner,
            'is_active' => true,
        ]);
    }
}
