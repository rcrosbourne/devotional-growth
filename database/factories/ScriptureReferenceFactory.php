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
        /** @var string $book */
        $book = fake()->randomElement(['Genesis', 'Psalms', 'Isaiah', 'Matthew', 'John', 'Romans', 'Revelation']);
        $chapter = fake()->numberBetween(1, 50);
        $verseStart = fake()->numberBetween(1, 30);

        return [
            'devotional_entry_id' => DevotionalEntry::factory(),
            'book' => $book,
            'chapter' => $chapter,
            'verse_start' => $verseStart,
            'verse_end' => null,
            'raw_reference' => sprintf('%s %d:%d', $book, $chapter, $verseStart),
        ];
    }

    public function withVerseRange(): self
    {
        return $this->state(function (array $attributes): array {
            /** @var string $book */
            $book = $attributes['book'];
            /** @var int $chapter */
            $chapter = $attributes['chapter'];
            /** @var int $verseStart */
            $verseStart = $attributes['verse_start'];
            $verseEnd = $verseStart + fake()->numberBetween(1, 5);

            return [
                'verse_end' => $verseEnd,
                'raw_reference' => sprintf('%s %d:%d-%d', $book, $chapter, $verseStart, $verseEnd),
            ];
        });
    }
}
