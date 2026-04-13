<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\LessonDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonDay>
 */
final class LessonDayFactory extends Factory
{
    private const array DAY_NAMES = ['Sabbath', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dayPosition = fake()->numberBetween(0, 6);

        return [
            'lesson_id' => Lesson::factory(),
            'day_position' => $dayPosition,
            'day_name' => self::DAY_NAMES[$dayPosition],
            'title' => fake()->sentence(3),
            'body' => fake()->paragraphs(3, true),
            'discussion_questions' => null,
            'has_parse_warning' => false,
        ];
    }

    public function sabbath(): self
    {
        return $this->state(fn (array $attributes): array => [
            'day_position' => 0,
            'day_name' => 'Sabbath',
        ]);
    }

    public function friday(): self
    {
        return $this->state(fn (array $attributes): array => [
            'day_position' => 6,
            'day_name' => 'Friday',
            'discussion_questions' => [
                fake()->sentence(),
                fake()->sentence(),
                fake()->sentence(),
            ],
        ]);
    }

    public function forDay(int $position): self
    {
        return $this->state(fn (array $attributes): array => [
            'day_position' => $position,
            'day_name' => self::DAY_NAMES[$position],
        ]);
    }

    public function withParseWarning(): self
    {
        return $this->state(fn (array $attributes): array => [
            'has_parse_warning' => true,
        ]);
    }
}
