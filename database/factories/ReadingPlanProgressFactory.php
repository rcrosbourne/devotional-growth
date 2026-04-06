<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ReadingPlan;
use App\Models\ReadingPlanDay;
use App\Models\ReadingPlanProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReadingPlanProgress>
 */
final class ReadingPlanProgressFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plan = ReadingPlan::factory();

        return [
            'user_id' => User::factory(),
            'reading_plan_id' => $plan,
            'reading_plan_day_id' => ReadingPlanDay::factory()->for($plan, 'readingPlan'),
            'started_at' => now()->toDateString(),
            'completed_at' => null,
        ];
    }

    public function completed(): self
    {
        return $this->state(fn (array $attributes): array => [
            'completed_at' => now()->toDateString(),
        ]);
    }
}
