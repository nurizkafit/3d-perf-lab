<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = config('lab.admin');

        if (blank($admin['email']) || blank($admin['password'])) {
            $this->command?->warn('ADMIN_EMAIL or ADMIN_PASSWORD is not set. First admin not created.');

            return;
        }

        // Never overwrite an existing account (its password may have been changed).
        $user = User::firstOrCreate(
            ['email' => $admin['email']],
            [
                'name' => $admin['name'],
                'password' => $admin['password'],
                'role' => 'admin',
                'is_active' => true,
            ],
        );

        if (! $user->wasRecentlyCreated) {
            $this->command?->info("Admin {$admin['email']} already exists. Not changed.");
        }
    }
}
