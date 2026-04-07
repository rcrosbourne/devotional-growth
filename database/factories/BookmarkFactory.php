<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Bookmark;
use App\Models\DevotionalEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bookmark>
 */
final class BookmarkFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'bookmarkable_type' => DevotionalEntry::class,
            'bookmarkable_id' => DevotionalEntry::factory(),
        ];
    }

    public function forDevotionalEntry(DevotionalEntry $entry): self
    {
        return $this->state(fn (array $attributes): array => [
            'bookmarkable_type' => DevotionalEntry::class,
            'bookmarkable_id' => $entry->id,
        ]);
    }
}
