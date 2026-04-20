<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BibleStudyReflection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BibleStudyReflection>
 */
final class BibleStudyReflectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'bible_study_theme_id' => null,
            'book' => 'Job',
            'chapter' => 1,
            'verse_start' => 13,
            'verse_end' => 22,
            'verse_number' => null,
            'body' => fake()->paragraph(),
            'is_shared_with_partner' => false,
        ];
    }

    public function shared(): self
    {
        return $this->state(fn (array $attributes): array => ['is_shared_with_partner' => true]);
    }
}
