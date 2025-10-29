<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'document' => $this->faker->randomElement([
                $this->faker->numerify('###########'),
                $this->faker->numerify('##############'),
            ]),
        ];
    }

    /**
     * Indicate that the client is a person (CPF).
     */
    public function person(): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => $this->faker->name(),
            'document' => $this->faker->numerify('###########'),
        ]);
    }

    /**
     * Indicate that the client is a company (CNPJ).
     */
    public function company(): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => $this->faker->company(),
            'document' => $this->faker->numerify('##############'),
        ]);
    }
}
