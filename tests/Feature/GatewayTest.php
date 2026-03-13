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
        Gateway::factory()->count(3)->create();

        $response = $this->actingAs($user)->getJson('/api/gateways');

        $response->assertStatus(200);
        $response->assertJsonCount(3);

        // Verify they are ordered by priority
        $this->assertEquals(1, $response->json('0.priority'));
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
}
