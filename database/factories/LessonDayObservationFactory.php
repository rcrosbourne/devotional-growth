<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LessonDay;
use App\Models\LessonDayObservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonDayObservation>
 */
final class LessonDayObservationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'lesson_day_id' => LessonDay::factory(),
            'body' => fake()->paragraph(),
            'edited_at' => null,
        ];
    }

    public function edited(): self
    {
        return $this->state(fn (array $attributes): array => [
            'edited_at' => now(),
        ]);
    }
}
