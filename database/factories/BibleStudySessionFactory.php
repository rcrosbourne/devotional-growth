<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BibleStudySession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BibleStudySession>
 */
final class BibleStudySessionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'bible_study_theme_id' => null,
            'current_book' => 'Job',
            'current_chapter' => 1,
            'current_verse_start' => 13,
            'current_verse_end' => 22,
            'started_at' => now(),
            'last_accessed_at' => now(),
        ];
    }
}
