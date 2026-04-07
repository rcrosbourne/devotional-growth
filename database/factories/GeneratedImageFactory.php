<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DevotionalEntry;
use App\Models\GeneratedImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GeneratedImage>
 */
final class GeneratedImageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'devotional_entry_id' => DevotionalEntry::factory(),
            'path' => 'images/devotionals/'.fake()->uuid().'.png',
            'prompt' => fake()->sentence(),
        ];
    }
}
