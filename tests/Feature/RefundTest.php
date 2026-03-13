<?php

namespace Tests\Feature;

use App\Models\Gateway;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RefundTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_cannot_refund_transaction()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $transaction = Transaction::factory()->create(['status' => '03 - completed']);

        $response = $this->actingAs($manager)
            ->postJson("/api/transactions/{$transaction->id}/refund");

        $response->assertStatus(403);
    }

    public function test_cannot_refund_already_refunded_transaction()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $transaction = Transaction::factory()->create(['status' => '06 - refunded']);

        $response = $this->actingAs($admin)
            ->postJson("/api/transactions/{$transaction->id}/refund");

        $response->assertStatus(409);
        $response->assertJson(['message' => 'This transaction has already been refunded.']);
    }

    public function test_finance_can_refund_completed_transaction()
    {
        $finance = User::factory()->create(['role' => 'finance']);
        $transaction = Transaction::factory()->create([
            'status' => '03 - completed',
            'external_id' => 'ext_123',
        ]);

        // Mock the Gateway response
        Http::fake(['*' => Http::response(['status' => 'success'], 200)]);

        $response = $this->actingAs($finance)
            ->postJson("/api/transactions/{$transaction->id}/refund");

        $response->assertStatus(200);
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => '06 - refunded',
        ]);
    }

    public function test_cannot_refund_incomplete_transaction()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $transaction = Transaction::factory()->create(['status' => '01 - pending']);

        $response = $this->actingAs($admin)
            ->postJson("/api/transactions/{$transaction->id}/refund");

        $response->assertStatus(422);
    }
}
