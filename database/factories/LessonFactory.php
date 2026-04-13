<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\Quarterly;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lesson>
 */
final class LessonFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $lessonNumber = fake()->unique()->numberBetween(1, 13);
        $dateStart = fake()->date();

        return [
            'quarterly_id' => Quarterly::factory(),
            'lesson_number' => $lessonNumber,
            'title' => fake()->sentence(3),
            'date_start' => $dateStart,
            'date_end' => now()->parse($dateStart)->addDays(6)->toDateString(),
            'memory_text' => fake()->sentence(10),
            'memory_text_reference' => fake()->randomElement(['John 3:16', 'Psalm 23:1', 'Romans 8:28', 'Luke 14:11']),
            'image_path' => null,
            'image_prompt' => null,
            'has_parse_warnings' => false,
        ];
    }

    public function withImage(): self
    {
        return $this->state(fn (array $attributes): array => [
            'image_path' => 'images/sabbath-school/test/lesson-1.png',
            'image_prompt' => fake()->sentence(),
        ]);
    }

    public function withParseWarnings(): self
    {
        return $this->state(fn (array $attributes): array => [
            'has_parse_warnings' => true,
        ]);
    }
}
