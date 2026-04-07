<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\DevotionalEntry;
use App\Models\Theme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DevotionalEntry>
 */
final class DevotionalEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'theme_id' => Theme::factory(),
            'title' => fake()->sentence(4),
            'body' => fake()->paragraphs(3, true),
            'reflection_prompts' => fake()->sentence(),
            'adventist_insights' => fake()->sentence(),
            'display_order' => 0,
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
