<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WordStudy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WordStudy>
 */
final class WordStudyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $language = fake()->randomElement(['greek', 'hebrew']);
        $prefix = $language === 'greek' ? 'G' : 'H';

        return [
            'original_word' => fake()->randomElement(['ἀγάπη', 'πίστις', 'χάρις', 'εἰρήνη', 'אֱמוּנָה']),
            'transliteration' => fake()->randomElement(['agape', 'pistis', 'charis', 'eirene', 'emunah']),
            'language' => $language,
            'definition' => fake()->sentence(),
            'strongs_number' => $prefix.fake()->unique()->numberBetween(1, 9999),
        ];
    }

    public function greek(): self
    {
        return $this->state(fn (array $attributes): array => [
            'language' => 'greek',
            'strongs_number' => 'G'.fake()->unique()->numberBetween(1, 9999),
        ]);
    }

    public function hebrew(): self
    {
        return $this->state(fn (array $attributes): array => [
            'language' => 'hebrew',
            'strongs_number' => 'H'.fake()->unique()->numberBetween(1, 9999),
        ]);
    }
}
