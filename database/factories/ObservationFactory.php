<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DevotionalEntry;
use App\Models\Observation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Observation>
 */
final class ObservationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'devotional_entry_id' => DevotionalEntry::factory(),
            'body' => fake()->paragraph(),
            'edited_at' => null,
        ];
    }

    public function edited(): self
    {
        return $this->state(fn (array $attributes): array => [
            'edited_at' => now(),
        ]);
    }
}
