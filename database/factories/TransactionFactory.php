<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Client;
use App\Models\Gateway;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Transaction::class;
    public function definition(): array
    {
        $statusList = [
            '01 - pending', '02 - processing', '03 - completed', 
            '04 - failed', '05 - chargeback', '06 - refunded', '07 - partially refunded'
        ];

        return [
            'client_id' => Client::factory(),
            'client_email' => fake()->unique()->safeEmail(),

            'gateway_id' => fn () => Gateway::inRandomOrder()->first()->id ?? Gateway::factory(),
            'external_id' => 'ref_' . str()->random(10),
            'status' => fake()->randomElement($statusList),

            'card_last_numbers' => fake()->numerify('####'),
            'product_id'   => fn () => Product::inRandomOrder()->first()->id ?? Product::factory(),
            'quantity' => fake()->numberBetween(1, 10),

            'amount' => function (array $attributes) {
                $product = Product::find($attributes['product_id']);
                return ($product->amount ?? 1000) * $attributes['quantity'];
            },
        ];
    }
}
