<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationPreference>
 */
final class NotificationPreferenceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'completion_notifications' => true,
            'observation_notifications' => true,
            'new_theme_notifications' => true,
            'reminder_notifications' => true,
            'bible_study_partner_share_notifications' => true,
        ];
    }

    public function allDisabled(): self
    {
        return $this->state(fn (array $attributes): array => [
            'completion_notifications' => false,
            'observation_notifications' => false,
            'new_theme_notifications' => false,
            'reminder_notifications' => false,
            'bible_study_partner_share_notifications' => false,
        ]);
    }
}
