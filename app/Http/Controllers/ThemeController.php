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
use Illuminate\Support\Facades\Storage;
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

        /** @var list<int> $themeIds */
        $themeIds = $themes->pluck('id')->all();
        $coverImages = $this->loadCoverImages($themeIds);

        return Inertia::render('themes/index', [
            'themes' => $themes->map(fn (Theme $theme): array => [
                'id' => $theme->id,
                'name' => $theme->name,
                'description' => $theme->description,
                'status' => $theme->status,
                'entries_count' => $theme->entries_count,
                'completed_entries_count' => $theme->completed_entries_count,
                'cover_image_path' => $this->resolveImagePath($theme, $coverImages),
            ]),
        ]);
    }

    public function show(Theme $theme, #[CurrentUser] User $user): Response
    {
        abort_unless($theme->status === ContentStatus::Published, 404);

        $entries = $theme->entries()
            ->published()
            ->orderBy('display_order')
            ->with([
                'scriptureReferences',
                'generatedImage',
                'completions' => fn (Relation $query) => $query->where('user_id', $user->id),
            ])
            ->get();

        $totalEntries = $entries->count();
        $completedEntries = $entries->filter(fn (DevotionalEntry $entry) => $entry->completions->isNotEmpty())->count();

        $coverImage = $this->loadCoverImages([$theme->id]);

        return Inertia::render('themes/show', [
            'theme' => $theme,
            'entries' => $entries->map(fn (DevotionalEntry $entry): array => [
                'id' => $entry->id,
                'title' => $entry->title,
                'body' => $entry->body,
                'display_order' => $entry->display_order,
                'scripture_references' => $entry->scriptureReferences,
                'completions' => $entry->completions,
                'image_path' => $entry->generatedImage?->path !== null && Storage::disk('public')->exists($entry->generatedImage->path)
                    ? $entry->generatedImage->path
                    : null,
            ]),
            'coverImagePath' => $this->resolveImagePath($theme, $coverImage),
            'progress' => [
                'total' => $totalEntries,
                'completed' => $completedEntries,
                'percentage' => $totalEntries > 0 ? round(($completedEntries / $totalEntries) * 100) : 0,
            ],
        ]);
    }

    /**
     * @param  list<int>  $themeIds
     * @return array<int, string>
     */
    private function loadCoverImages(array $themeIds): array
    {
        if ($themeIds === []) {
            return [];
        }

        $firstEntryIds = DevotionalEntry::query()
            ->selectRaw('MIN(id) as id')
            ->whereIn('theme_id', $themeIds)
            ->where('status', ContentStatus::Published)
            ->whereHas('generatedImage')
            ->groupBy('theme_id')
            ->pluck('id');

        $entries = DevotionalEntry::query()
            ->whereIn('id', $firstEntryIds)
            ->with('generatedImage')
            ->get();

        $result = [];
        foreach ($entries as $entry) {
            $image = $entry->generatedImage;
            if ($image !== null && Storage::disk('public')->exists($image->path)) {
                $result[$entry->theme_id] = $image->path;
            }
        }

        return $result;
    }

    /**
     * @param  array<int, string>  $entryCoverImages
     */
    private function resolveImagePath(Theme $theme, array $entryCoverImages): ?string
    {
        if ($theme->image_path !== null && Storage::disk('public')->exists($theme->image_path)) {
            return $theme->image_path;
        }

        return $entryCoverImages[$theme->id] ?? null;
    }
}
