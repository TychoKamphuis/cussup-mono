<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a default tenant
        $tenant = Tenant::create([
            'uuid' => Str::uuid(),
            'name' => 'Default Tenant',
            'domain' => 'localhost',
        ]);

        // Call the UserSeeder to create users and associate them with tenants
        $this->call([
            UserSeeder::class,
        ]);
    }
}
