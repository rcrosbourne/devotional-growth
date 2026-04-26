<?php

declare(strict_types=1);

use App\Models\BibleStudyTheme;
use App\Models\BibleStudyThemePassage;
use App\Models\User;

it('lists only approved themes on the index endpoint', function (): void {
    $user = User::factory()->create();
    $approved = BibleStudyTheme::factory()->approved()->create(['title' => 'Resilience']);
    BibleStudyTheme::factory()->draft()->create(['title' => 'Patience']);

    $response = $this->actingAs($user)->get(route('bible-study.themes.index'));

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->component('bible-study/themes/index')
        ->has('themes', 1)
        ->where('themes.0.id', $approved->id)
    );
});

it('renders a theme detail with passages', function (): void {
    $user = User::factory()->create();
    $theme = BibleStudyTheme::factory()->approved()->create(['slug' => 'resilience']);
    BibleStudyThemePassage::factory()->for($theme, 'theme')->create(['position' => 1, 'is_guided_path' => true]);
    BibleStudyThemePassage::factory()->for($theme, 'theme')->create(['position' => 2, 'is_guided_path' => false]);

    $response = $this->actingAs($user)->get(route('bible-study.themes.show', $theme->slug));

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->component('bible-study/themes/show')
        ->where('theme.slug', 'resilience')
        ->has('theme.passages', 2)
        ->where('theme.passages.0.is_guided_path', true)
    );
});

it('404s when looking up a draft theme by slug', function (): void {
    $user = User::factory()->create();
    $draft = BibleStudyTheme::factory()->draft()->create(['slug' => 'forgiveness']);

    $this->actingAs($user)->get(route('bible-study.themes.show', $draft->slug))->assertNotFound();
});

it('redirects unauthenticated users from the index', function (): void {
    $this->get(route('bible-study.themes.index'))->assertRedirectToRoute('login');
});
