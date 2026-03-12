<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

use Tests\TestCase;

use App\Models\Product;
use App\Models\Gateway;

use App\Rules\LuhnRule;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
        {
            return array_merge([
                'name'       => 'Valid User',
                'email'      => 'valid@example.com',
                'cardNumber' => '4242424242424242', // Valid Luhn card
                'cvv'        => '010',
            ], $overrides);
        }

    protected function setUp(): void
    {
        parent::setUp();
        
        config(['gateways.gateway_1.name' => 'Gateway 1']);
        config(['gateways.gateway_2.name' => 'Gateway 2']);
    }

    public function test_calculate_price_on_backend(){
        $product = Product::factory()->create(['amount' => 5000]); // AGAIN, IN CENTS
        $gateway = Gateway::factory()->gateway1()->create();

        Http::fake([
            '*' => Http::response(['id' => 'ext_123'], 201)
        ]);

        $response = $this->postJson('/api/buy', $this->validPayload([
            'product_id' => $product->id,
            'quantity'   => 3,
        ]));

        $response->assertStatus(201);
        $this->assertDatabaseHas('transactions',[
            'amount' => 15000, // 5000 * 3 (amount * quantity)
            'product_id' => $product->id,
            'client_email' => 'valid@example.com'
        ]);
    }

    public function test_gateway_fallback_mechanism(){
        $product = Product::factory()->create();
        $g1 = Gateway::factory()->gateway1()->create();
        $g2 = Gateway::factory()->gateway2()->create();

        Http::fake([
            'http://localhost:3001/*' => Http::response([], 500),
            'http://localhost:3002/*' => Http::response(['id' => 'ext_success'], 201),
        ]);

        $response = $this->postJson('/api/buy', $this->validPayload([
            'product_id' => $product->id,
        ]));

        $response->assertStatus(201);
        $this->assertDatabaseHas('transactions', [
            'gateway_id' => $g2->id,
            'external_id' => 'ext_success'
        ]);
    }

    public function test_products_table_pivot_record_creation(){
        $product = Product::factory()->create();
        Gateway::factory()->gateway1()->create();

        Http::fake(['*' => Http::response(['id' => '1'], 201)]);

        $this->postJson('/api/buy', $this->validPayload([
            'product_id' => $product->id,
            'quantity'   => 5
        ]));

        $this->assertDatabaseHas('transaction_products', [
            'product_id' => $product->id,
            'quantity'   => 5
        ]);
    }

    public function test_find_a_product_by_normalized_name(){

        $product = Product::factory()->create([
            'name' => 'Câmera Fotográfica',
            'amount' => 1000
        ]);

        Gateway::factory()->gateway1()->create();
        Http::fake(['*' => Http::response(['id' => 'ext_123'], 201)]);

        $response = $this->postJson('/api/buy', $this->validPayload([
            'product_id' => 'Câmera Fotográfica',
        ]));

        $response->assertStatus(201);
        $this->assertDatabaseHas('transactions', [
            'product_id' => $product->id,
            'amount' => 1000
        ]);
    }

    public function test_return_500_if_all_gateways_fail()
    {
        $product = Product::factory()->create();
        Gateway::factory()->gateway1()->create();
        Gateway::factory()->gateway2()->create();

        Http::fake([
            'http://localhost:3001/*' => Http::response([], 500),
            'http://localhost:3002/*' => Http::response([], 500),
        ]);

        $response = $this->postJson('/api/buy', $this->validPayload([
            'product_id' => $product->id,
        ]));

        $response->assertStatus(502);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_luhn_validation_on_card_number()
    {
        $product = Product::factory()->create();
        Gateway::factory()->gateway1()->create();

        //WRONG CARD SIZE
        $response = $this->postJson('/api/buy', $this->validPayload([
            'product_id' => $product->id,
            'cardNumber' => '1234', 
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['cardNumber']);

        //INVALID CARD NUMBER
        $response = $this->postJson('/api/buy', $this->validPayload([
            'product_id' => $product->id,
            'cardNumber' => '5569000000006064', // One digit off
        ]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['cardNumber' => ['Please insert a valid card number.']]);
    }

    public function test_it_rejects_invalid_cvv_formats()
    {
        $product = Product::factory()->create();
        
        $response = $this->postJson('/api/buy', $this->validPayload([
            'product_id' => $product->id,
            'cvv' => '12', // Too short
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['cvv']);
    }
}
