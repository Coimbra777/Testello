<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\FreightTable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FreightTable>
 */
class FreightTableFactory extends Factory
{
    protected $model = FreightTable::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'version' => $this->faker->numberBetween(1, 10),
            'file_name' => $this->faker->bothify('freight_table_####.csv'),
            'checksum' => $this->faker->sha256(),
            'status' => $this->faker->randomElement(['pending', 'processing', 'completed', 'failed']),
            'total_rows' => $this->faker->numberBetween(0, 300000),
            'total_errors' => $this->faker->numberBetween(0, 100),
            'started_at' => $this->faker->optional()->dateTime(),
            'finished_at' => $this->faker->optional()->dateTime(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'pending',
            'started_at' => null,
            'finished_at' => null,
            'total_rows' => 0,
            'total_errors' => 0,
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'processing',
            'started_at' => $this->faker->dateTime(),
            'finished_at' => null,
        ]);
    }


    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'completed',
            'started_at' => $this->faker->dateTime(),
            'finished_at' => $this->faker->dateTime(),
        ]);
    }


    public function failed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'failed',
            'started_at' => $this->faker->dateTime(),
            'finished_at' => $this->faker->dateTime(),
            'total_errors' => $this->faker->numberBetween(1, 100),
        ]);
    }
}
