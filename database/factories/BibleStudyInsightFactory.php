<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BibleStudyInsight;
use App\Models\BibleStudyThemePassage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BibleStudyInsight>
 */
final class BibleStudyInsightFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bible_study_theme_passage_id' => BibleStudyThemePassage::factory(),
            'interpretation' => fake()->paragraph(),
            'application' => fake()->paragraph(),
            'cross_references' => [
                ['book' => 'Romans', 'chapter' => 8, 'verse_start' => 18, 'verse_end' => 30, 'note' => fake()->sentence()],
            ],
            'literary_context' => fake()->paragraph(),
        ];
    }
}
