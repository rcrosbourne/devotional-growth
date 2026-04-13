<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Quarterly;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quarterly>
 */
final class QuarterlyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = fake()->unique()->numberBetween(2020, 2040);
        $quarterNumber = fake()->numberBetween(1, 4);
        $quarterLetter = ['a', 'b', 'c', 'd'][$quarterNumber - 1];
        $yearShort = $year % 100;

        return [
            'title' => fake()->unique()->sentence(4),
            'quarter_code' => sprintf('%02d%s', $yearShort, $quarterLetter),
            'year' => $year,
            'quarter_number' => $quarterNumber,
            'is_active' => false,
            'description' => fake()->paragraph(),
            'source_url' => sprintf('https://ssnet.org/lessons/%02d%s/', $yearShort, $quarterLetter),
            'last_synced_at' => null,
        ];
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => true,
        ]);
    }

    public function synced(): self
    {
        return $this->state(fn (array $attributes): array => [
            'last_synced_at' => now(),
        ]);
    }
}
