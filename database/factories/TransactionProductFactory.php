<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\TransactionProduct;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TransactionProduct>
 */
class TransactionProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = TransactionProduct::class;

    public function definition(): array
    {
        return [
            //
        ];
    }
}
