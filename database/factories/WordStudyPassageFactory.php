<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WordStudy;
use App\Models\WordStudyPassage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WordStudyPassage>
 */
final class WordStudyPassageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'word_study_id' => WordStudy::factory(),
            'book' => fake()->randomElement(['Matthew', 'John', 'Romans', '1 Corinthians', 'Galatians', '1 John']),
            'chapter' => fake()->numberBetween(1, 28),
            'verse' => fake()->numberBetween(1, 40),
            'english_word' => fake()->randomElement(['love', 'faith', 'grace', 'peace', 'hope']),
        ];
    }
}
