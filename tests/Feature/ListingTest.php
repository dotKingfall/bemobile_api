<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Product;
use App\Models\Client;

class ListingTest extends TestCase
{
    use RefreshDatabase;
    
    //TRANSACTION TESTS ==============================================================================
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

    //END TRANSACTION TESTS ==========================================================================


    //CLIENTS TESTS ==================================================================================
    public function test_can_list_all_clients()
    {
        $user = User::factory()->create(['role' => 'user']);
        Client::factory()->count(5)->create();

        $response = $this->actingAs($user)->getJson('/api/clients');

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');
    }

    public function test_can_show_client_with_purchase_history()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = Client::factory()->create();
        
        Transaction::factory()->create(['client_id' => $client->id]);

        $response = $this->actingAs($admin)->getJson("/api/clients/{$client->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('id', $client->id);
        $response->assertJsonCount(1, 'transactions');
    }
    //END CLIENTS TESTS ==============================================================================
    
}
