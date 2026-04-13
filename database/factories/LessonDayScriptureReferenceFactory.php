<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LessonDay;
use App\Models\LessonDayScriptureReference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonDayScriptureReference>
 */
final class LessonDayScriptureReferenceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lesson_day_id' => LessonDay::factory(),
            'book' => fake()->randomElement(['John', 'Psalm', 'Romans', 'Luke', 'Genesis', '1 John']),
            'chapter' => fake()->numberBetween(1, 50),
            'verse_start' => fake()->numberBetween(1, 30),
            'verse_end' => fake()->optional(0.5)->numberBetween(5, 40),
            'raw_reference' => 'John 3:16',
        ];
    }
}
