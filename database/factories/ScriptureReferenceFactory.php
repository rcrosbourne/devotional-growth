<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DevotionalEntry;
use App\Models\ScriptureReference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScriptureReference>
 */
final class ScriptureReferenceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $book = fake()->randomElement(['Genesis', 'Psalms', 'Isaiah', 'Matthew', 'John', 'Romans', 'Revelation']);
        $chapter = fake()->numberBetween(1, 50);
        $verseStart = fake()->numberBetween(1, 30);

        return [
            'devotional_entry_id' => DevotionalEntry::factory(),
            'book' => $book,
            'chapter' => $chapter,
            'verse_start' => $verseStart,
            'verse_end' => null,
            'raw_reference' => "{$book} {$chapter}:{$verseStart}",
        ];
    }

    public function withVerseRange(): self
    {
        return $this->state(function (array $attributes): array {
            $verseEnd = $attributes['verse_start'] + fake()->numberBetween(1, 5);

            return [
                'verse_end' => $verseEnd,
                'raw_reference' => "{$attributes['book']} {$attributes['chapter']}:{$attributes['verse_start']}-{$verseEnd}",
            ];
        });
    }
}
