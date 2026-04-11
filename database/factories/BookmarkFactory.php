<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Bookmark;
use App\Models\DevotionalEntry;
use App\Models\ScriptureReference;
use App\Models\User;
use App\Models\WordStudy;
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

    public function forScriptureReference(ScriptureReference $reference): self
    {
        return $this->state(fn (array $attributes): array => [
            'bookmarkable_type' => ScriptureReference::class,
            'bookmarkable_id' => $reference->id,
        ]);
    }

    public function forWordStudy(WordStudy $wordStudy): self
    {
        return $this->state(fn (array $attributes): array => [
            'bookmarkable_type' => WordStudy::class,
            'bookmarkable_id' => $wordStudy->id,
        ]);
    }
}
