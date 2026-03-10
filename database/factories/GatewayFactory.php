<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Gateway;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Gateway>
 */
class GatewayFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Gateway::class;
    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' Gateway',
            'is_active' => true,
            'priority' => 1,
        ];
    }
}
