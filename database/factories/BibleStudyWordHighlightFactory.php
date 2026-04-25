<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BibleStudyThemePassage;
use App\Models\BibleStudyWordHighlight;
use App\Models\WordStudy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BibleStudyWordHighlight>
 */
final class BibleStudyWordHighlightFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bible_study_theme_passage_id' => BibleStudyThemePassage::factory(),
            'word_study_id' => WordStudy::factory(),
            'verse_number' => fake()->numberBetween(1, 40),
            'word_index_in_verse' => fake()->numberBetween(0, 20),
            'display_word' => fake()->word(),
        ];
    }
}
