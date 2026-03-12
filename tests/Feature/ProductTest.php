<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Product;
use App\Models\User;

class ProductTest extends TestCase
{
    use RefreshDatabase;
    
    //REPEATING ROLES TESTS JUST TO MAKE SURE
    public function test_authorized_roles_can_manage_products()
    {
        $roles = ['admin', 'manager', 'finance'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);

            $response = $this->actingAs($user)->postJson('/api/products', [
                'name' => "Product for $role",
                'amount' => 1000
            ]);

            $response->assertStatus(201);
            $this->assertDatabaseHas('products', ['name' => "Product for $role"]);
        }
    }

    public function test_can_update_product()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['name' => 'Old Name', 'amount' => 100]);

        $response = $this->actingAs($admin)->putJson("/api/products/{$product->id}", [
            'name' => 'New Name'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'New Name']);
    }

    public function test_unauthorized_roles_cannot_manage_products()
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->postJson('/api/products', [
            'name' => 'Should fail',
            'amount' => 1000
        ]);

        $response->assertStatus(403);
    }
}
