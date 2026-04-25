<?php

declare(strict_types=1);

use App\Models\BibleStudyTheme;
use App\Models\User;

it('renders the admin bible-study themes index', function (): void {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('admin.bible-study.themes.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/bible-study/themes/index')
            ->has('statuses', 3)
        );
});

it('shows drafts ordered by requested_count desc then created_at asc', function (): void {
    $admin = User::factory()->admin()->create();
    $old = BibleStudyTheme::factory()->draft()->create(['requested_count' => 5, 'created_at' => now()->subDay()]);
    $recent = BibleStudyTheme::factory()->draft()->create(['requested_count' => 5, 'created_at' => now()]);
    $hot = BibleStudyTheme::factory()->draft()->create(['requested_count' => 10]);

    $response = $this->actingAs($admin)->get(route('admin.bible-study.themes.index'));

    $response->assertInertia(fn ($page) => $page
        ->component('admin/bible-study/themes/index')
        ->where('themes.0.id', $hot->id)
        ->where('themes.1.id', $old->id)
        ->where('themes.2.id', $recent->id)
    );
});

it('denies non-admin access', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.bible-study.themes.index'));

    $response->assertForbidden();
});

it('redirects unauthenticated users', function (): void {
    $response = $this->get(route('admin.bible-study.themes.index'));

    $response->assertRedirectToRoute('login');
});
