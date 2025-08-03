<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first tenant (or create one if none exists)
        $tenant = Tenant::first();
        
        if (!$tenant) {
            $tenant = Tenant::create([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'name' => 'Default Tenant',
                'domain' => 'localhost',
            ]);
        }

        // Create a default user
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        // Associate the user with the tenant
        $user->tenants()->attach($tenant->id, [
            'role' => 'admin',
            'permissions' => json_encode(['*']), // All permissions
            'is_active' => true,
        ]);

        // Create additional users if needed
        $user2 = User::create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
        ]);

        $user2->tenants()->attach($tenant->id, [
            'role' => 'member',
            'permissions' => json_encode(['read', 'write']),
            'is_active' => true,
        ]);
    }
} 