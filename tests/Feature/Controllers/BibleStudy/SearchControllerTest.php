<?php

declare(strict_types=1);

use App\Models\BibleStudyTheme;
use App\Models\User;

it('returns matching approved themes as JSON', function (): void {
    $user = User::factory()->create();
    BibleStudyTheme::factory()->approved()->create(['slug' => 'wisdom', 'title' => 'Wisdom']);

    $response = $this->actingAs($user)->getJson(route('bible-study.search', ['q' => 'wisdom']));

    $response->assertOk()->assertJsonCount(1, 'themes');
});

it('returns an empty list on a miss-match', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson(route('bible-study.search', ['q' => 'forgiveness']));

    $response->assertOk()->assertJsonCount(0, 'themes');
});

it('rejects empty queries', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson(route('bible-study.search'))->assertUnprocessable();
});
