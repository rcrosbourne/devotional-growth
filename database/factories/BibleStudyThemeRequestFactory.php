<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BibleStudyThemeRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BibleStudyThemeRequest>
 */
final class BibleStudyThemeRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $query = fake()->word();

        return [
            'user_id' => User::factory(),
            'search_query' => ucfirst($query),
            'normalized_query' => mb_strtolower(mb_trim($query)),
            'generated_bible_study_theme_id' => null,
        ];
    }
}
