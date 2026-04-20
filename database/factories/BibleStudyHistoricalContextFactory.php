<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BibleStudyHistoricalContext;
use App\Models\BibleStudyThemePassage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BibleStudyHistoricalContext>
 */
final class BibleStudyHistoricalContextFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bible_study_theme_passage_id' => BibleStudyThemePassage::factory(),
            'setting' => fake()->sentence(),
            'author' => fake()->name(),
            'date_range' => 'ca. '.fake()->numberBetween(100, 2000).' BC',
            'audience' => fake()->sentence(),
            'historical_events' => fake()->paragraph(),
        ];
    }
}
