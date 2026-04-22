<?php

declare(strict_types=1);

use App\Enums\BibleStudyThemeStatus;
use App\Jobs\DraftBibleStudyThemeJob;
use App\Models\BibleStudyTheme;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('dispatches a draft job and redirects to the index', function (): void {
    Queue::fake();

    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('admin.bible-study.themes.store'), [
        'title' => 'Resilience',
    ]);

    $response->assertRedirect(route('admin.bible-study.themes.index', absolute: false));

    Queue::assertPushed(
        DraftBibleStudyThemeJob::class,
        fn (DraftBibleStudyThemeJob $job): bool => $job->admin->is($admin) && $job->themeTitle === 'Resilience',
    );
});

it('updates theme meta', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = BibleStudyTheme::factory()->draft()->create();

    $response = $this->actingAs($admin)->put(route('admin.bible-study.themes.update', $theme), [
        'title' => 'Wisdom (edited)',
        'short_description' => 'New',
        'long_intro' => 'New intro.',
    ]);

    $response->assertRedirect();

    expect($theme->fresh()->title)->toBe('Wisdom (edited)');
});

it('publishes a draft', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = BibleStudyTheme::factory()->draft()->create();

    $response = $this->actingAs($admin)->put(route('admin.bible-study.themes.publish', $theme));

    $response->assertRedirect();

    expect($theme->fresh()->status)->toBe(BibleStudyThemeStatus::Approved);
});

it('rejects publishing a non-draft', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = BibleStudyTheme::factory()->approved()->create();

    $response = $this->actingAs($admin)->put(route('admin.bible-study.themes.publish', $theme));

    $response->assertSessionHasErrors();
});

it('deletes a theme', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = BibleStudyTheme::factory()->create();

    $response = $this->actingAs($admin)->delete(route('admin.bible-study.themes.destroy', $theme));

    $response->assertRedirect();

    expect(BibleStudyTheme::query()->find($theme->id))->toBeNull();
});
