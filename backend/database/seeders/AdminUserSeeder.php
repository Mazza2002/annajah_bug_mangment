<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'Admin')->first();

        if ($adminRole) {
            User::firstOrCreate(
                ['email' => 'admin@annajah.com'],
                [
                    'name' => 'System Admin',
                    'password' => Hash::make('password'), // Default password
                    'role_id' => $adminRole->id,
                    'email_verified_at' => now(),
                    'notification_prefs' => ['email' => true, 'in_app' => true],
                ]
            );
        }
    }
}
