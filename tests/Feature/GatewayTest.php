<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Gateway;

class GatewayTest extends TestCase
{
    public function test_list_all_gateways()
    {
        $user = User::factory()->create(['role' => 'user']);
        Gateway::factory()->create(['priority' => 10, 'name' => 'Gateway 3']);
        Gateway::factory()->create(['priority' => 1, 'name' => 'Gateway 1']);
        Gateway::factory()->create(['priority' => 5, 'name' => 'Gateway 2']);

        $response = $this->actingAs($user)->getJson('/api/gateways');

        $response->assertStatus(200);
        $response->assertJsonCount(3);

        $this->assertEquals(1, $response->json('0.priority'));
        $this->assertEquals('Gateway 1', $response->json('0.name'));
    }

    public function test_can_update_gateway_priority()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $gateway = Gateway::factory()->create(['priority' => 1]);

        $response = $this->actingAs($admin)->patchJson("/api/gateways/{$gateway->id}/priority", [
            'priority' => 5
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('gateways', [
            'id' => $gateway->id,
            'priority' => 5
        ]);
    }

    public function test_cannot_set_invalid_priority()
    {
        $finance = User::factory()->create(['role' => 'finance']);
        $gateway = Gateway::factory()->create();

        $response = $this->actingAs($finance)->patchJson("/api/gateways/{$gateway->id}/priority", [
            'priority' => -1
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['priority']);
    }

    public function test_toggle_gateway_active_status()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $gateway = Gateway::factory()->create(['is_active' => true]);

        $response = $this->actingAs($manager)->patchJson("/api/gateways/{$gateway->id}/change-status");
        $response->assertStatus(200);

        $this->assertDatabaseHas('gateways', [
            'id' => $gateway->id,
            'is_active' => false
        ]);

        $response = $this->actingAs($manager)->patchJson("/api/gateways/{$gateway->id}/change-status");
        $response->assertStatus(200);

        $this->assertDatabaseHas('gateways', [
            'id' => $gateway->id,
            'is_active' => true
        ]);
    }
}
