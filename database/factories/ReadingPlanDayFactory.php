<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ReadingPlan;
use App\Models\ReadingPlanDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReadingPlanDay>
 */
final class ReadingPlanDayFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reading_plan_id' => ReadingPlan::factory(),
            'day_number' => fake()->numberBetween(1, 365),
            'passages' => ['Genesis 1:1-31', 'Psalm 1:1-6'],
        ];
    }
}
