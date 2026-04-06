<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ScriptureCache;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScriptureCache>
 */
final class ScriptureCacheFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'book' => fake()->randomElement(['Genesis', 'Psalms', 'Isaiah', 'Matthew', 'John', 'Romans', 'Revelation']),
            'chapter' => fake()->numberBetween(1, 50),
            'verse_start' => fake()->numberBetween(1, 30),
            'verse_end' => null,
            'bible_version' => 'KJV',
            'text' => fake()->paragraph(),
        ];
    }

    public function withVerseRange(): self
    {
        return $this->state(function (array $attributes): array {
            return [
                'verse_end' => $attributes['verse_start'] + fake()->numberBetween(1, 5),
            ];
        });
    }

    public function withVersion(string $version): self
    {
        return $this->state(fn (array $attributes): array => [
            'bible_version' => $version,
        ]);
    }
}
