<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Bookmark;
use App\Models\DevotionalEntry;
use App\Models\ScriptureReference;
use App\Models\User;
use App\Models\WordStudy;
use InvalidArgumentException;

final readonly class CreateBookmark
{
    /**
     * @var list<class-string>
     */
    private const array ALLOWED_TYPES = [
        DevotionalEntry::class,
        ScriptureReference::class,
        WordStudy::class,
    ];

    public function handle(User $user, string $bookmarkableType, int $bookmarkableId): Bookmark
    {
        throw_unless(in_array($bookmarkableType, self::ALLOWED_TYPES, true), InvalidArgumentException::class, 'Invalid bookmarkable type: '.$bookmarkableType);

        $bookmarkableType::query()->findOrFail($bookmarkableId);

        return Bookmark::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'bookmarkable_type' => $bookmarkableType,
                'bookmarkable_id' => $bookmarkableId,
            ],
        );
    }
}
