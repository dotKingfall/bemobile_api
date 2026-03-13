<?php

namespace Database\Factories;

use App\Models\Gateway;
use Illuminate\Database\Eloquent\Factories\Factory;

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

        // HERE LIES WHAT MADE ME LOSE 2H DEBUGGING, RANDOM GATEWAY NAMES WERE GENERATED AS FORCE OF HABIT WHEN USING FACTORIES
        // SO NOW WE'LL USE VALID GATEWAYS TO MAKE SURE I WON'T EVER NEED TO GO THROUGH THAT AGAIN :v
        $validGateways = [
            config('gateways.gateway_1.name'),
            config('gateways.gateway_2.name'),
        ];

        return [
            'name' => fake()->randomElement($validGateways),
            'is_active' => true,
            'priority' => fake()->unique()->numberBetween(1, 10),
        ];
    }

    public function gateway1(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => config('gateways.gateway_1.name'),
            'priority' => 1,
        ]);
    }

    public function gateway2(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => config('gateways.gateway_2.name'),
            'priority' => 2,
        ]);
    }
}
