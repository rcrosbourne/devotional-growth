<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DevotionalCompletion;
use App\Models\DevotionalEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DevotionalCompletion>
 */
final class DevotionalCompletionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'devotional_entry_id' => DevotionalEntry::factory(),
            'completed_at' => now(),
        ];
    }
}
