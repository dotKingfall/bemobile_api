<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Product;
use App\Models\Gateway;
use Illuminate\Support\Facades\Http;
use App\Models\Transaction;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        config(['gateways.gateway_1.name' => 'Gateway 1']);
        config(['gateways.gateway_2.name' => 'Gateway 2']);
    }

    public function test_calculate_price_on_backend(){
        $product = Product::factory()->create(['amount' => 5000]); // AGAIN, IN CENTS
        $gateway = Gateway::factory()->create(['name' => 'Gateway 1', 'is_active' => true]);

        Http::fake([
            '*' => Http::response(['id' => 'ext_123'], 201)
        ]);

        $response = $this->postJson('/api/buy', [
            'product_id' => $product->id,
            'name'       => 'Unauthenticated User',
            'email'      => 'test@example.com',
            'quantity'   => 3,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('transactions', [
            'amount' => 15000, // 5000 * 3 (amount * quantity)
            'product_id' => $product->id
        ]);
    }

    public function test_gateway_fallback_mechanism(){
        $product = Product::factory()->create();
        $g1 = Gateway::factory()->create(['name' => 'Gateway 1', 'priority' => 1, 'is_active' => true]);
        $g2 = Gateway::factory()->create(['name' => 'Gateway 2', 'priority' => 2, 'is_active' => true]);

        Http::fake([
            'http://localhost:3001/*' => Http::response([], 500),
            'http://localhost:3002/*' => Http::response(['id' => 'ext_success'], 201),
        ]);

        $response = $this->postJson('/api/buy', [
            'product_id' => $product->id,
            'name'       => 'Failover User',
            'email'      => 'fail@example.com',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('transactions', [
            'gateway_id' => $g2->id,
            'external_id' => 'ext_success'
        ]);
    }

    public function test_products_table_pivot_record_creation(){
        $product = Product::factory()->create();
        Gateway::factory()->create(['name' => 'Gateway 1', 'is_active' => true, 'priority' => 1]);

        Http::fake(['*' => Http::response(['id' => '1'], 201)]);

        $this->postJson('/api/buy', [
            'product_id' => $product->id,
            'name'       => 'Pivot Tester',
            'email'      => 'pivot@example.com',
            'quantity'   => 5
        ]);

        $this->assertDatabaseHas('transaction_products', [
            'product_id' => $product->id,
            'quantity'   => 5
        ]);

        //$response->dump();
    }

    public function test_find_a_product_by_normalized_name(){

        $product = Product::factory()->create([
            'name' => 'Câmera Fotográfica',
            'amount' => 1000
        ]);

        Gateway::factory()->create(['name' => 'Gateway 1', 'is_active' => true, 'priority' => 1]);
        Http::fake(['*' => Http::response(['id' => 'ext_123'], 201)]);

        $response = $this->postJson('/api/buy', [
            'product_id' => 'Câmera Fotográfica',
            'name'       => 'User Normalized',
            'email'      => 'usernormalized@example.com',
        ]);

        dump($response->json());

        $response->assertStatus(201);
        $this->assertDatabaseHas('transactions', [
            'product_id' => $product->id,
            'amount' => 1000
        ]);
    }

    public function test_return_500_if_all_gateways_fail()
    {
        $product = Product::factory()->create();
        Gateway::factory()->create(['name' => 'Gateway 1', 'is_active' => true, 'priority' => 1]);
        Gateway::factory()->create(['name' => 'Gateway 2', 'is_active' => true, 'priority' => 2]);

        Http::fake([
            'http://localhost:3001/*' => Http::response([], 500),
            'http://localhost:3002/*' => Http::response([], 500),
        ]);

        $response = $this->postJson('/api/buy', [
            'product_id' => $product->id,
            'name'       => 'Unlucky Buyer',
            'email'      => 'unlucky@example.com',
        ]);

        $response->assertStatus(500);
        $this->assertDatabaseCount('transactions', 0);
    }
}
