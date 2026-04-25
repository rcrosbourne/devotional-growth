<?php

declare(strict_types=1);

use App\Actions\BibleStudy\PublishBibleStudyTheme;
use App\Enums\BibleStudyThemeStatus;
use App\Models\BibleStudyTheme;
use App\Models\User;

it('flips status to approved and stamps metadata', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = BibleStudyTheme::factory()->draft()->create();

    resolve(PublishBibleStudyTheme::class)->handle($admin, $theme);

    $theme->refresh();
    expect($theme->status)->toBe(BibleStudyThemeStatus::Approved)
        ->and($theme->approved_at)->not->toBeNull()
        ->and($theme->approved_by_user_id)->toBe($admin->id);
});

it('throws when the theme is not a draft', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = BibleStudyTheme::factory()->approved()->create();

    expect(fn () => resolve(PublishBibleStudyTheme::class)->handle($admin, $theme))
        ->toThrow(DomainException::class);
});
