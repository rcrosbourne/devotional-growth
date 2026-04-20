<?php

declare(strict_types=1);

use App\Models\BibleStudyTheme;
use App\Models\BibleStudyThemeRequest;
use App\Models\User;

it('stores a normalized query', function (): void {
    $request = BibleStudyThemeRequest::factory()->create([
        'search_query' => 'Forgiveness ',
        'normalized_query' => 'forgiveness',
    ]);

    expect($request->normalized_query)->toBe('forgiveness');
});

it('optionally links to a generated draft theme', function (): void {
    $user = User::factory()->create();
    $theme = BibleStudyTheme::factory()->draft()->create();

    $request = BibleStudyThemeRequest::factory()
        ->for($user)
        ->for($theme, 'generatedTheme')
        ->create();

    expect($request->generatedTheme->is($theme))->toBeTrue()
        ->and($request->user->is($user))->toBeTrue();
});
