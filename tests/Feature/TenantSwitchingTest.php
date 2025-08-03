<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantSwitchingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_switch_tenants()
    {
        // Create a user
        $user = User::factory()->create();

        // Create multiple tenants
        $tenant1 = Tenant::factory()->create(['name' => 'Tenant 1']);
        $tenant2 = Tenant::factory()->create(['name' => 'Tenant 2']);

        // Attach tenants to user
        $user->tenants()->attach($tenant1, ['is_active' => true]);
        $user->tenants()->attach($tenant2, ['is_active' => true]);

        $this->actingAs($user);

        // Test switching to tenant 2
        $response = $this->post('/tenants/switch', [
            'tenant_id' => $tenant2->id
        ]);

        $response->assertRedirect();
        $this->assertEquals($tenant2->id, session('active_tenant_id'));

        // Test getting available tenants
        $response = $this->get('/tenants/available');
        $response->assertOk();
        $response->assertJsonCount(2, 'tenants');
    }

    public function test_user_cannot_switch_to_unauthorized_tenant()
    {
        // Create a user
        $user = User::factory()->create();

        // Create a tenant that the user doesn't have access to
        $unauthorizedTenant = Tenant::factory()->create(['name' => 'Unauthorized Tenant']);

        $this->actingAs($user);

        // Test switching to unauthorized tenant
        $response = $this->post('/tenants/switch', [
            'tenant_id' => $unauthorizedTenant->id
        ]);

        $response->assertStatus(404);
    }
} 