<?php

declare(strict_types=1);

use App\Actions\CreateTheme;
use App\Enums\ContentStatus;
use App\Models\Theme;
use App\Models\User;

it('creates a theme with draft status', function (): void {
    $admin = User::factory()->admin()->create();
    $action = resolve(CreateTheme::class);

    $theme = $action->handle($admin, 'Faith', 'A theme about faith');

    expect($theme)->toBeInstanceOf(Theme::class)
        ->and($theme->name)->toBe('Faith')
        ->and($theme->description)->toBe('A theme about faith')
        ->and($theme->status)->toBe(ContentStatus::Draft)
        ->and($theme->created_by)->toBe($admin->id);
});

it('creates a theme with null description', function (): void {
    $admin = User::factory()->admin()->create();
    $action = resolve(CreateTheme::class);

    $theme = $action->handle($admin, 'Forgiveness');

    expect($theme->description)->toBeNull();
});
