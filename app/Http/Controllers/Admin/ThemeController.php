<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\CreateTheme;
use App\Actions\DeleteTheme;
use App\Actions\GenerateThemeImage;
use App\Actions\PublishTheme;
use App\Actions\UnpublishTheme;
use App\Actions\UpdateTheme;
use App\Http\Requests\CreateThemeRequest;
use App\Http\Requests\UpdateThemeRequest;
use App\Models\DevotionalEntry;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

final readonly class ThemeController
{
    public function index(): Response
    {
        $themes = Theme::query()
            ->withCount('entries')
            ->latest()
            ->get();

        /** @var list<int> $themeIds */
        $themeIds = $themes->pluck('id')->all();
        $coverImages = $this->loadCoverImages($themeIds);

        return Inertia::render('admin/themes/index', [
            'themes' => $themes->map(fn (Theme $theme): array => [
                'id' => $theme->id,
                'name' => $theme->name,
                'description' => $theme->description,
                'status' => $theme->status,
                'entries_count' => $theme->entries_count,
                'created_at' => $theme->created_at,
                'cover_image_path' => $this->resolveImagePath($theme, $coverImages),
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/themes/create');
    }

    public function store(CreateThemeRequest $request, #[CurrentUser] User $user, CreateTheme $action): RedirectResponse
    {
        $action->handle(
            $user,
            $request->string('name')->value(),
            $request->string('description')->value() ?: null,
        );

        return to_route('admin.themes.index');
    }

    public function edit(Theme $theme): Response
    {
        return Inertia::render('admin/themes/edit', [
            'theme' => $theme,
        ]);
    }

    public function update(UpdateThemeRequest $request, Theme $theme, UpdateTheme $action): RedirectResponse
    {
        $action->handle(
            $theme,
            $request->string('name')->value(),
            $request->string('description')->value() ?: null,
        );

        return to_route('admin.themes.index');
    }

    public function destroy(Theme $theme, DeleteTheme $action): RedirectResponse
    {
        $action->handle($theme);

        return to_route('admin.themes.index');
    }

    public function publish(Theme $theme, PublishTheme $action): RedirectResponse
    {
        $action->handle($theme);

        return to_route('admin.themes.index');
    }

    public function unpublish(Theme $theme, UnpublishTheme $action): RedirectResponse
    {
        $action->handle($theme);

        return to_route('admin.themes.index');
    }

    public function generateImage(Request $request, Theme $theme, GenerateThemeImage $action): RedirectResponse
    {
        set_time_limit(120);

        $replace = $request->boolean('replace');

        try {
            $action->handle($theme, $replace);
        } catch (Throwable $throwable) {
            Log::error('Theme image generation failed', ['error' => $throwable->getMessage(), 'theme_id' => $theme->id]);

            return back()->with('error', 'Image generation is currently unavailable. Please try again later.');
        }

        return back();
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
