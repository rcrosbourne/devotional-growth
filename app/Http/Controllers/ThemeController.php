<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ContentStatus;
use App\Models\DevotionalEntry;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ThemeController
{
    public function index(#[CurrentUser] User $user): Response
    {
        $themes = Theme::query()
            ->published()
            ->withCount(['entries' => fn (Builder $query) => $query->where('status', ContentStatus::Published)])
            ->withCount(['entries as completed_entries_count' => fn (Builder $query) => $query
                ->where('status', ContentStatus::Published)
                ->whereHas('completions', fn (Builder $q) => $q->where('user_id', $user->id)),
            ])
            ->latest()
            ->get();

        return Inertia::render('themes/index', [
            'themes' => $themes,
        ]);
    }

    public function show(Theme $theme, #[CurrentUser] User $user): Response
    {
        abort_unless($theme->status === ContentStatus::Published, 404);

        $entries = $theme->entries()
            ->published()
            ->orderBy('display_order')
            ->with(['completions' => fn (Relation $query) => $query->where('user_id', $user->id)])
            ->get();

        $totalEntries = $entries->count();
        $completedEntries = $entries->filter(fn (DevotionalEntry $entry) => $entry->completions->isNotEmpty())->count();

        return Inertia::render('themes/show', [
            'theme' => $theme,
            'entries' => $entries,
            'progress' => [
                'total' => $totalEntries,
                'completed' => $completedEntries,
                'percentage' => $totalEntries > 0 ? round(($completedEntries / $totalEntries) * 100) : 0,
            ],
        ]);
    }
}
