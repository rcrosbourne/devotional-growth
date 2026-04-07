<?php

declare(strict_types=1);

use App\Models\Theme;
use App\Models\User;

test('to array', function (): void {
    $theme = Theme::factory()->create()->refresh();

    expect(array_keys($theme->toArray()))
        ->toBe([
            'id',
            'created_by',
            'name',
            'description',
            'status',
            'created_at',
            'updated_at',
        ]);
});

test('creator returns belongs to relationship', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::factory()->create(['created_by' => $admin->id]);

    expect($theme->creator)
        ->toBeInstanceOf(User::class)
        ->id->toBe($admin->id);
});

test('scope published filters to published themes', function (): void {
    Theme::factory()->draft()->create();
    Theme::factory()->published()->create();
    Theme::factory()->published()->create();

    expect(Theme::query()->published()->count())->toBe(2);
});
