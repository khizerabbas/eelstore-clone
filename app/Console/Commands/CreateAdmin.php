<?php

namespace App\Console\Commands;

use App\Enums\AdminRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

// php artisan app:create-admin  – creates an admin panel user (no public sign-up exists)
class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin';

    protected $description = 'Create an admin panel user (Owner or Staff)';

    public function handle(): int
    {
        $name  = text('Name', required: true);
        $email = text('Email', required: true, validate: fn ($value) => Validator::make(
            ['email' => $value],
            ['email' => ['email', 'unique:users,email']]
        )->errors()->first('email') ?: null);

        $password = password(
            'Password (min 12 characters, letters, numbers and symbols)',
            required: true,
            validate: fn ($value) => Validator::make(
                ['password' => $value],
                ['password' => [Password::min(12)->letters()->mixedCase()->numbers()->symbols()]]
            )->errors()->first('password') ?: null,
        );

        $role = select('Role', [
            AdminRole::Owner->value => 'Owner – full access (settings, admin users)',
            AdminRole::Staff->value => 'Staff – orders, catalog, homepage, messages',
        ], default: AdminRole::Owner->value);

        User::create([
            'name'      => $name,
            'email'     => $email,
            'password'  => $password,
            'role'      => $role,
            'is_active' => true,
        ]);

        $this->info("Admin {$email} created. Log in at " . url('/admin'));

        return self::SUCCESS;
    }
}
