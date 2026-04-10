<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\CreateTheme;
use App\Actions\DeleteTheme;
use App\Actions\PublishTheme;
use App\Actions\UpdateTheme;
use App\Http\Requests\CreateThemeRequest;
use App\Http\Requests\UpdateThemeRequest;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ThemeController
{
    public function index(): Response
    {
        $themes = Theme::query()
            ->withCount('entries')
            ->latest()
            ->get();

        return Inertia::render('admin/themes/index', [
            'themes' => $themes,
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
}
