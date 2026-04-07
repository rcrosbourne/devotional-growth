<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Theme>
 */
final class ThemeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'status' => ContentStatus::Draft,
        ];
    }

    public function published(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ContentStatus::Published,
        ]);
    }

    public function draft(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ContentStatus::Draft,
        ]);
    }
}
