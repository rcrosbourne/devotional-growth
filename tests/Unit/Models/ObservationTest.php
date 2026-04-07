<?php

declare(strict_types=1);

use App\Models\DevotionalEntry;
use App\Models\Observation;
use App\Models\User;

test('to array', function (): void {
    $observation = Observation::factory()->create()->refresh();

    expect(array_keys($observation->toArray()))
        ->toBe([
            'id',
            'user_id',
            'devotional_entry_id',
            'body',
            'edited_at',
            'created_at',
            'updated_at',
        ]);
});

test('user returns belongs to relationship', function (): void {
    $user = User::factory()->create();
    $observation = Observation::factory()->for($user)->create();

    expect($observation->user)
        ->toBeInstanceOf(User::class)
        ->id->toBe($user->id);
});

test('devotional entry returns belongs to relationship', function (): void {
    $entry = DevotionalEntry::factory()->create();
    $observation = Observation::factory()->for($entry, 'devotionalEntry')->create();

    expect($observation->devotionalEntry)
        ->toBeInstanceOf(DevotionalEntry::class)
        ->id->toBe($entry->id);
});

test('factory defaults edited_at to null', function (): void {
    $observation = Observation::factory()->create();

    expect($observation->edited_at)->toBeNull();
});

test('factory edited state sets edited_at', function (): void {
    $observation = Observation::factory()->edited()->create();

    expect($observation->edited_at)->not->toBeNull();
});
