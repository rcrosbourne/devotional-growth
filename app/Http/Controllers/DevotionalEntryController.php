<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CompleteDevotionalEntry;
use App\Enums\ContentStatus;
use App\Models\Bookmark;
use App\Models\DevotionalEntry;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DevotionalEntryController
{
    public function show(Theme $theme, DevotionalEntry $entry, #[CurrentUser] User $user): Response
    {
        abort_unless($theme->status === ContentStatus::Published, 404);
        abort_unless($entry->theme_id === $theme->id && $entry->status === ContentStatus::Published, 404);

        $entry->load([
            'scriptureReferences',
            'generatedImage',
            'completions' => fn (Relation $query) => $query->where('user_id', $user->id),
            'observations' => fn (Relation $query) => $user->hasPartner()
                ? $query->whereIn('user_id', [$user->id, $user->partner_id])->with('user')->oldest()
                : $query->where('user_id', $user->id)->with('user')->oldest(),
        ]);

        $publishedEntries = $theme->entries()
            ->published()
            ->orderBy('display_order')
            ->get(['id', 'title']);

        /** @var int|false $currentIndex */
        $currentIndex = $publishedEntries->search(fn (DevotionalEntry $e): bool => $e->id === $entry->id);
        $previousEntry = is_int($currentIndex) && $currentIndex > 0 ? $publishedEntries[$currentIndex - 1] : null;
        $nextEntry = is_int($currentIndex) && $currentIndex < $publishedEntries->count() - 1 ? $publishedEntries[$currentIndex + 1] : null;

        $isCompleted = $entry->completions->isNotEmpty();

        /** @var Bookmark|null $bookmark */
        $bookmark = $user->bookmarks()
            ->where('bookmarkable_type', DevotionalEntry::class)
            ->where('bookmarkable_id', $entry->id)
            ->first();

        return Inertia::render('devotional-entries/show', [
            'theme' => $theme,
            'entry' => $entry,
            'isCompleted' => $isCompleted,
            'previousEntry' => $previousEntry ? ['id' => $previousEntry->id, 'title' => $previousEntry->title] : null,
            'nextEntry' => $nextEntry ? ['id' => $nextEntry->id, 'title' => $nextEntry->title] : null,
            'hasPartner' => $user->hasPartner(),
            'isBookmarked' => $bookmark !== null,
            'bookmarkId' => $bookmark?->id,
            'entryPosition' => is_int($currentIndex) ? $currentIndex + 1 : 1,
            'totalEntries' => $publishedEntries->count(),
        ]);
    }

    public function complete(Theme $theme, DevotionalEntry $entry, #[CurrentUser] User $user, CompleteDevotionalEntry $action): RedirectResponse
    {
        abort_unless($theme->status === ContentStatus::Published, 404);
        abort_unless($entry->theme_id === $theme->id && $entry->status === ContentStatus::Published, 404);

        $action->handle($user, $entry);

        return back();
    }
}
