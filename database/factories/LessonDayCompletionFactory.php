<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LessonDay;
use App\Models\LessonDayCompletion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonDayCompletion>
 */
final class LessonDayCompletionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'lesson_day_id' => LessonDay::factory(),
            'completed_at' => now(),
        ];
    }
}
