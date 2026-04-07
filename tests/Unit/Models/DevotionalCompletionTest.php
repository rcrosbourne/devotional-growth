<?php

declare(strict_types=1);

use App\Models\DevotionalCompletion;
use App\Models\DevotionalEntry;
use App\Models\User;

test('to array', function (): void {
    $completion = DevotionalCompletion::factory()->create()->refresh();

    expect(array_keys($completion->toArray()))
        ->toBe([
            'id',
            'user_id',
            'devotional_entry_id',
            'completed_at',
            'created_at',
            'updated_at',
        ]);
});

test('user returns belongs to relationship', function (): void {
    $user = User::factory()->create();
    $completion = DevotionalCompletion::factory()->for($user)->create();

    expect($completion->user)
        ->toBeInstanceOf(User::class)
        ->id->toBe($user->id);
});

test('devotional entry returns belongs to relationship', function (): void {
    $entry = DevotionalEntry::factory()->create();
    $completion = DevotionalCompletion::factory()->for($entry, 'devotionalEntry')->create();

    expect($completion->devotionalEntry)
        ->toBeInstanceOf(DevotionalEntry::class)
        ->id->toBe($entry->id);
});
