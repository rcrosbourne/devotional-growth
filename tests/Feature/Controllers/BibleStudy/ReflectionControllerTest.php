<?php

declare(strict_types=1);

use App\Models\BibleStudyReflection;
use App\Models\BibleStudyTheme;
use App\Models\User;

it('stores a passage-level reflection', function (): void {
    $user = User::factory()->create();
    $theme = BibleStudyTheme::factory()->approved()->create();

    $response = $this->actingAs($user)->post(route('bible-study.reflections.store'), [
        'theme_id' => $theme->id,
        'book' => 'Job',
        'chapter' => 1,
        'verse_start' => 13,
        'verse_end' => 22,
        'verse_number' => null,
        'body' => 'Worship before understanding.',
        'is_shared_with_partner' => false,
    ]);

    $response->assertRedirect();

    expect(BibleStudyReflection::query()->where('user_id', $user->id)->count())->toBe(1);
});

it("updates the user's own reflection", function (): void {
    $user = User::factory()->create();
    $reflection = BibleStudyReflection::factory()->for($user)->create(['body' => 'old']);

    $this->actingAs($user)->put(route('bible-study.reflections.update', $reflection), [
        'body' => 'new',
        'is_shared_with_partner' => true,
    ])->assertRedirect();

    expect($reflection->fresh()->body)->toBe('new')
        ->and($reflection->fresh()->is_shared_with_partner)->toBeTrue();
});

it("forbids updating someone else's reflection", function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $reflection = BibleStudyReflection::factory()->for($other)->create();

    $this->actingAs($user)->put(route('bible-study.reflections.update', $reflection), [
        'body' => 'hijack', 'is_shared_with_partner' => false,
    ])->assertForbidden();
});

it("destroys the user's own reflection", function (): void {
    $user = User::factory()->create();
    $reflection = BibleStudyReflection::factory()->for($user)->create();

    $this->actingAs($user)->delete(route('bible-study.reflections.destroy', $reflection))->assertRedirect();

    expect(BibleStudyReflection::query()->find($reflection->id))->toBeNull();
});

it("forbids destroying someone else's reflection", function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $reflection = BibleStudyReflection::factory()->for($other)->create();

    $this->actingAs($user)->delete(route('bible-study.reflections.destroy', $reflection))->assertForbidden();
});
