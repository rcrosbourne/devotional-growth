<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ReadingPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReadingPlan>
 */
final class ReadingPlanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->sentence(3),
            'description' => fake()->sentence(),
            'total_days' => 365,
            'is_default' => false,
        ];
    }

    public function default(): self
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => true,
        ]);
    }
}
