<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BibleStudyTheme;
use App\Models\BibleStudyThemePassage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BibleStudyThemePassage>
 */
final class BibleStudyThemePassageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->numberBetween(1, 20);

        return [
            'bible_study_theme_id' => BibleStudyTheme::factory(),
            'position' => fake()->unique()->numberBetween(1, 10000),
            'is_guided_path' => false,
            'book' => fake()->randomElement(['Job', 'Psalms', 'Proverbs', 'Matthew', 'Romans']),
            'chapter' => fake()->numberBetween(1, 50),
            'verse_start' => $start,
            'verse_end' => $start + fake()->numberBetween(0, 10),
            'passage_intro' => fake()->sentence(),
        ];
    }

    public function guided(): self
    {
        return $this->state(fn (array $attributes): array => ['is_guided_path' => true]);
    }
}
