<?php

namespace Database\Factories;

use App\Models\FreightRate;
use App\Models\FreightTable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FreightRate>
 */
class FreightRateFactory extends Factory
{
    protected $model = FreightRate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $minWeight = $this->faker->randomFloat(2, 0.1, 50);
        $maxWeight = $this->faker->randomFloat(2, $minWeight, $minWeight + 50);

        return [
            'freight_table_id' => FreightTable::factory(),
            'min_weight' => $minWeight,
            'max_weight' => $maxWeight,
            'price' => $this->faker->randomFloat(2, 5, 200),
        ];
    }
}
