<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Product;

class ListingTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_can_list_transactions_with_relations()
    {
        $user = User::factory()->create(['role' => 'admin']);
        Transaction::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/transactions');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'amount', 'status', 'gateway', 'products']
            ]
        ]);
    }


    public function test_can_show_transaction_detail()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();
        $transaction = Transaction::factory()->create();

        $transaction->products()->attach($product->id, ['quantity' => 1]);
        $response = $this->actingAs($user)->getJson("/api/transactions/{$transaction->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('id', $transaction->id);
        $this->assertEquals($product->name, $response->json('products.0.name'));
    }
}
