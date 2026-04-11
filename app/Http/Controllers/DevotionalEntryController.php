<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CompleteDevotionalEntry;
use App\Enums\ContentStatus;
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

        $publishedEntryIds = $theme->entries()
            ->published()
            ->orderBy('display_order')
            ->pluck('id');

        /** @var int|false $currentIndex */
        $currentIndex = $publishedEntryIds->search($entry->id);
        $previousEntryId = is_int($currentIndex) && $currentIndex > 0 ? $publishedEntryIds[$currentIndex - 1] : null;
        $nextEntryId = is_int($currentIndex) && $currentIndex < $publishedEntryIds->count() - 1 ? $publishedEntryIds[$currentIndex + 1] : null;

        $isCompleted = $entry->completions->isNotEmpty();

        return Inertia::render('devotional-entries/show', [
            'theme' => $theme,
            'entry' => $entry,
            'isCompleted' => $isCompleted,
            'previousEntryId' => $previousEntryId,
            'nextEntryId' => $nextEntryId,
            'hasPartner' => $user->hasPartner(),
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
