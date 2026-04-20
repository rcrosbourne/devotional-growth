<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BibleStudyThemeStatus;
use App\Models\BibleStudyTheme;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BibleStudyTheme>
 */
final class BibleStudyThemeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(2, true);

        return [
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 99999),
            'title' => ucfirst($title),
            'short_description' => fake()->sentence(),
            'long_intro' => fake()->paragraphs(2, true),
            'status' => BibleStudyThemeStatus::Draft,
            'requested_count' => 0,
            'approved_at' => null,
            'approved_by_user_id' => null,
        ];
    }

    public function draft(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BibleStudyThemeStatus::Draft,
            'approved_at' => null,
            'approved_by_user_id' => null,
        ]);
    }

    public function approved(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BibleStudyThemeStatus::Approved,
            'approved_at' => now(),
            'approved_by_user_id' => User::factory(),
        ]);
    }

    public function archived(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BibleStudyThemeStatus::Archived,
        ]);
    }
}
